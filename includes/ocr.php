<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Process ID image with OCR using Python
 * Now includes auto-approval for high confidence scores
 * @param string $image_path Path to uploaded ID image
 * @param int $profile_id User profile ID
 * @return array ['success' => bool, 'confidence' => float, 'message' => string, 'auto_approved' => bool]
 */
function processIDWithOCR($image_path, $profile_id) {
    global $pdo;
    
    try {
        // Get user profile data
        $stmt = $pdo->prepare("
            SELECT full_name, id_number, user_id
            FROM user_profiles 
            WHERE profile_id = ?
        ");
        $stmt->execute([$profile_id]);
        $user_data = $stmt->fetch();
        
        if (!$user_data) {
            return [
                'success' => false, 
                'confidence' => 0, 
                'message' => 'User profile not found',
                'auto_approved' => false
            ];
        }
        
        // Full path to image
        $full_path = UPLOAD_PATH . 'ids/' . $image_path;
        
        if (!file_exists($full_path)) {
            return [
                'success' => false, 
                'confidence' => 0, 
                'message' => 'Image file not found',
                'auto_approved' => false
            ];
        }
        
        // Call Python OCR script
        $result = callPythonOCR(
            $full_path, 
            $user_data['full_name'] ?? '', 
            $user_data['id_number'] ?? ''
        );
        
        if (!$result['success']) {
            error_log("Python OCR failed: " . ($result['error'] ?? 'Unknown error'));
            
            // OCR failed - send to manual review
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET verification_status = 'pending_verification'
                WHERE profile_id = ?
            ");
            $stmt->execute([$profile_id]);
            
            return [
                'success' => false,
                'confidence' => 0,
                'message' => $result['error'] ?? 'OCR processing failed',
                'auto_approved' => false
            ];
        }
        
        $confidence = $result['confidence'] ?? 0;
        
        // Save OCR log to database
        $stmt = $pdo->prepare("
            INSERT INTO ocr_verification_logs 
            (profile_id, extracted_text, normalized_text, confidence_score, processed_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $profile_id,
            $result['extracted_text'] ?? '',
            $result['normalized_text'] ?? '',
            $confidence
        ]);
        
        $ocr_log_id = $pdo->lastInsertId();
        
        // AUTO-APPROVAL LOGIC
        $auto_approved = false;
        $verification_status = 'pending_verification';
        
        if ($confidence >= 50) {
            // High confidence - AUTO APPROVE
            $verification_status = 'verified';
            $auto_approved = true;
            
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET verification_status = 'verified',
                    verified_by = NULL,
                    verified_at = NOW(),
                    admin_remarks = ?
                WHERE profile_id = ?
            ");
            
            $auto_remark = "Auto-approved by AI system (Confidence: {$confidence}%)";
            $stmt->execute([$auto_remark, $profile_id]);
            
            // Log the auto-approval
            logAction($user_data['user_id'], 'auto_verified', 'user_profiles', $profile_id);
            
            $message = "Identity verified automatically! (Confidence: {$confidence}%)";
            
        } else {
            // Low confidence - MANUAL REVIEW REQUIRED
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET verification_status = 'pending_verification'
                WHERE profile_id = ?
            ");
            $stmt->execute([$profile_id]);
            
            // Log the manual review requirement
            logAction($user_data['user_id'], 'manual_review_required', 'user_profiles', $profile_id);
            
            $message = "Submitted for manual verification (Confidence: {$confidence}%)";
        }
        
        return [
            'success' => true,
            'confidence' => $confidence,
            'message' => $message,
            'auto_approved' => $auto_approved
        ];
        
    } catch (Exception $e) {
        error_log("OCR Error: " . $e->getMessage());
        return [
            'success' => false,
            'confidence' => 0,
            'message' => 'OCR processing failed: ' . $e->getMessage(),
            'auto_approved' => false
        ];
    }
}

/**
 * Call Python OCR script and return result
 */
function callPythonOCR($image_path, $user_name, $user_id_number) {
    // Path to Python script
    $script_path = __DIR__ . '/../ocr_service/ocr_processor.py';
    
    // Check if script exists
    if (!file_exists($script_path)) {
        return [
            'success' => false,
            'error' => 'Python OCR script not found'
        ];
    }
    
    // Escape arguments for shell
    $image_arg = escapeshellarg($image_path);
    $name_arg = escapeshellarg($user_name);
    $id_arg = escapeshellarg($user_id_number);
    
    // Build command
    $command = "python3 " . escapeshellarg($script_path) . " $image_arg $name_arg $id_arg 2>&1";
    
    // Execute command
    exec($command, $output, $return_code);
    
    // Join output
    $json_output = implode("\n", $output);
    
    // Parse JSON result
    $result = json_decode($json_output, true);
    
    if (!$result) {
        error_log("Python OCR output: " . $json_output);
        return [
            'success' => false,
            'error' => 'Failed to parse OCR result'
        ];
    }
    
    return $result;
}

/**
 * Test Python OCR installation
 */
function testPythonOCR() {
    // Check if Python is available
    exec('which python3 2>&1', $output, $return_code);
    
    if ($return_code !== 0) {
        return [
            'success' => false,
            'message' => 'Python 3 not found. Install with: pkg install python'
        ];
    }
    
    // Check if pytesseract is installed
    exec('python3 -c "import pytesseract" 2>&1', $output, $return_code);
    
    if ($return_code !== 0) {
        return [
            'success' => false,
            'message' => 'pytesseract not installed. Install with: pip install pytesseract pillow'
        ];
    }
    
    // Check if Tesseract is installed
    exec('tesseract --version 2>&1', $output, $return_code);
    
    if ($return_code !== 0) {
        return [
            'success' => false,
            'message' => 'Tesseract not installed. Install with: pkg install tesseract'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Python OCR environment is ready',
        'python_version' => exec('python3 --version'),
        'tesseract_version' => exec('tesseract --version 2>&1 | head -n 1')
    ];
}

/**
 * Get OCR log for a profile
 */
function getOCRLog($profile_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM ocr_verification_logs
        WHERE profile_id = ?
        ORDER BY processed_at DESC
        LIMIT 1
    ");
    $stmt->execute([$profile_id]);
    
    return $stmt->fetch();
}

/**
 * Get confidence level description
 */
function getConfidenceLevel($score) {
    if ($score >= 80) {
        return ['level' => 'High', 'color' => '#28a745', 'icon' => '✓'];
    } elseif ($score >= 50) {
        return ['level' => 'Medium', 'color' => '#ffc107', 'icon' => '⚠'];
    } else {
        return ['level' => 'Low', 'color' => '#dc3545', 'icon' => '✗'];
    }
}
?>