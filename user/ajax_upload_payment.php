<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'redirect' => null
];

// Check if user is logged in
if (!isLoggedIn()) {
    $response['errors']['general'] = 'Please log in to upload payment';
    echo json_encode($response);
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get reservation ID
$reservation_id = $_POST['reservation_id'] ?? $_GET['reservation_id'] ?? 0;
if (!$reservation_id) {
    $response['errors']['general'] = "Invalid reservation";
    echo json_encode($response);
    exit();
}

// Check if reservation belongs to user
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.cottage_name 
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.cottage_id
        WHERE r.reservation_id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        $response['errors']['general'] = "Reservation not found";
        echo json_encode($response);
        exit();
    }
    
    // Check if already has payment
    $stmt = $pdo->prepare("SELECT * FROM payment_proofs WHERE reservation_id = ?");
    $stmt->execute([$reservation_id]);
    $existing_payment = $stmt->fetch();
    
    if ($existing_payment) {
        $response['errors']['general'] = "Payment already uploaded for this reservation";
        echo json_encode($response);
        exit();
    }
    
} catch (Exception $e) {
    $response['errors']['general'] = "System error";
    echo json_encode($response);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_number = $_POST['reference_number'] ?? '';
    
    // Validation
    if (empty($reference_number)) {
        $response['errors']['reference_number'] = "Reference number is required.";
    }
    
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        $response['errors']['payment_proof'] = "Please upload payment proof.";
    } else {
        $file = $_FILES['payment_proof'];
        
        // File validation
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $response['errors']['payment_proof'] = "Invalid file type. Please upload JPG, PNG, or PDF";
        } elseif ($file['size'] > $max_size) {
            $response['errors']['payment_proof'] = "File too large. Maximum size is 5MB";
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $response['errors']['payment_proof'] = "Upload error: " . $file['error'];
        }
    }
    
    // If no errors, process
    if (empty($response['errors'])) {
        // Upload file
        $upload_result = uploadFile($file, 'payments');
        
        if ($upload_result['success']) {
            try {
                // Save to database
                $stmt = $pdo->prepare("
                    INSERT INTO payment_proofs 
                    (reservation_id, receipt_image_path, reference_number)
                    VALUES (?, ?, ?)
                ");
                
                $stmt->execute([
                    $reservation_id,
                    $upload_result['filename'],
                    $reference_number
                ]);
                
                // Log action
                logAction($user_id, 'UPLOAD_PAYMENT', 'payment_proofs', $pdo->lastInsertId());
                
                $response['success'] = true;
                $response['message'] = "Payment proof uploaded successfully!";
                $response['redirect'] = "my-reservations.php?success=payment_uploaded";
                
            } catch (Exception $e) {
                $response['errors']['general'] = "Failed to save payment. Please try again.";
            }
        } else {
            $response['errors']['payment_proof'] = $upload_result['message'];
        }
    }
} else {
    $response['errors']['general'] = "Invalid request method";
}

echo json_encode($response);
exit();