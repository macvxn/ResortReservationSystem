<?php
// ajax/update-profile.php
// AJAX endpoint for profile updates - REUSES existing business logic

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Check if it's an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Start JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.',
        'redirect' => '../auth/login.php'
    ]);
    exit();
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Get POST data - PRESERVING EXACT $_POST keys
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    // Validate input (using existing validation logic)
    $errors = [];
    
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($full_name) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters';
    }
    
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]{10,20}$/', $phone)) {
        $errors['phone'] = 'Invalid phone number format';
    }
    
    if (!empty($birthdate)) {
        $birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$birthdate_obj || $birthdate_obj->format('Y-m-d') !== $birthdate) {
            $errors['birthdate'] = 'Invalid date format (YYYY-MM-DD required)';
        } elseif ($birthdate_obj > new DateTime('-18 years')) {
            $errors['birthdate'] = 'You must be at least 18 years old';
        }
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Please correct the errors below';
        echo json_encode($response);
        exit();
    }
    
    // Update profile in database - REUSING existing logic
    $stmt = $pdo->prepare("
        UPDATE user_profiles 
        SET full_name = ?, phone = ?, address = ?, birthdate = ?, gender = ?, updated_at = NOW()
        WHERE user_id = ?
    ");
    
    $success = $stmt->execute([
        $full_name,
        $phone,
        $address,
        $birthdate ?: null,
        $gender ?: null,
        $_SESSION['user_id']
    ]);
    
    if ($success) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully!';
        
        // If it's not an AJAX request (fallback), redirect
        if (!$isAjax) {
            header('Location: profile.php?success=1');
            exit();
        }
    } else {
        $response['message'] = 'Failed to update profile. Please try again.';
    }
    
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);