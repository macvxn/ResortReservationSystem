<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/ocr.php';
require_once '../includes/email.php';

requireLogin();

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'errors' => [],
    'message' => '',
    'redirect' => null,
    'ocr_result' => null
];

// Check if profile is filled
$profile = getUserProfile($_SESSION['user_id']);
if (empty($profile['full_name']) || empty($profile['id_number'])) {
    $response['errors']['general'] = "Please complete your profile first";
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_FILES['id_image']) || $_FILES['id_image']['error'] == UPLOAD_ERR_NO_FILE) {
        $response['errors']['id_image'] = "Please select an ID image to upload";
    } else {
        $file = $_FILES['id_image'];
        
        // File validation
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $response['errors']['id_image'] = "Invalid file type. Please upload JPG, PNG, or PDF";
        } elseif ($file['size'] > $max_size) {
            $response['errors']['id_image'] = "File too large. Maximum size is 5MB";
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $response['errors']['id_image'] = "Upload error: " . $file['error'];
        } else {
            // Upload file
            $upload_result = uploadFile($file, 'ids');
            
            if ($upload_result['success']) {
                $filename = $upload_result['filename'];
                
                // Update profile with ID image
                $stmt = $pdo->prepare("
                    UPDATE user_profiles 
                    SET id_image_path = ?, 
                        updated_at = NOW()
                    WHERE user_id = ?
                ");
                
                if ($stmt->execute([$filename, $_SESSION['user_id']])) {
                    logAction($_SESSION['user_id'], 'id_uploaded', 'user_profiles', $profile['profile_id']);
                    
                    // Process OCR (with auto-approval)
                    try {
                        $ocr_result = processIDWithOCR($filename, $profile['profile_id']);
                        $response['ocr_result'] = $ocr_result;
                        
                        if ($ocr_result['success']) {
                            // Check if auto-approved
                            if ($ocr_result['auto_approved']) {
                                // Send verification success email
                                $email_result = sendWelcomeEmail(
                                    $_SESSION['email'], 
                                    $profile['full_name']
                                );
                                
                                $response['success'] = true;
                                $response['message'] = "ID uploaded successfully! Your identity has been automatically verified.";
                                $response['redirect'] = "dashboard.php?auto_verified=1&confidence=" . $ocr_result['confidence'];
                            } else {
                                // Manual review required
                                $response['success'] = true;
                                $response['message'] = "ID uploaded successfully! Your ID is now under manual review.";
                                $response['redirect'] = "dashboard.php?manual_review=1&confidence=" . $ocr_result['confidence'];
                            }
                        } else {
                            // OCR failed - still goes to manual review
                            error_log("OCR processing failed: " . $ocr_result['message']);
                            $response['success'] = true;
                            $response['message'] = "ID uploaded! Your ID will be manually reviewed.";
                            $response['redirect'] = "dashboard.php?manual_review=1";
                        }
                    } catch (Exception $e) {
                        error_log("OCR exception: " . $e->getMessage());
                        $response['success'] = true;
                        $response['message'] = "ID uploaded! Your ID will be manually reviewed.";
                        $response['redirect'] = "dashboard.php?manual_review=1";
                    }
                } else {
                    $response['errors']['general'] = "Failed to save ID information";
                }
            } else {
                $response['errors']['id_image'] = $upload_result['message'];
            }
        }
    }
} else {
    $response['errors']['general'] = "Invalid request method";
}

echo json_encode($response);
exit();