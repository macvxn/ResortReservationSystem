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
    'redirect' => null,
    'reservation_id' => null
];

// Check if user is logged in
if (!isLoggedIn()) {
    $response['errors']['general'] = 'Please log in to make reservations';
    echo json_encode($response);
    exit();
}

// Check if user is verified
$user_profile = getUserProfile($_SESSION['user_id']);
if ($user_profile['verification_status'] !== 'verified') {
    $response['errors']['general'] = "You need to be verified to make reservations. Please complete ID verification first.";
    echo json_encode($response);
    exit();
}

// Get cottage ID
$cottage_id = $_POST['cottage_id'] ?? $_GET['cottage_id'] ?? 0;
if (!$cottage_id) {
    $response['errors']['general'] = "Invalid cottage selection";
    echo json_encode($response);
    exit();
}

// Get cottage details
$stmt = $pdo->prepare("SELECT * FROM cottages WHERE cottage_id = ? AND is_active = TRUE");
$stmt->execute([$cottage_id]);
$cottage = $stmt->fetch();

if (!$cottage) {
    $response['errors']['general'] = "Cottage not found or inactive";
    echo json_encode($response);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $guests = $_POST['guests'] ?? 1;
    
    // Validate dates
    if (empty($check_in) || empty($check_out)) {
        $response['errors']['check_in'] = "Please select both check-in and check-out dates.";
    }
    
    if ($check_in >= $check_out) {
        $response['errors']['check_out'] = "Check-out date must be after check-in date.";
    }
    
    // Check if dates are in the past
    $today = date('Y-m-d');
    if ($check_in < $today) {
        $response['errors']['check_in'] = "Check-in date cannot be in the past.";
    }
    
    // Validate guest count
    $guests = intval($guests);
    if ($guests < 1) {
        $response['errors']['guests'] = "Number of guests must be at least 1.";
    }
    
    if ($guests > $cottage['capacity']) {
        $response['errors']['guests'] = "This cottage can only accommodate up to {$cottage['capacity']} guests.";
    }
    
    // Check availability
    if (empty($response['errors']) && !isDateAvailable($cottage_id, $check_in, $check_out)) {
        $response['errors']['check_in'] = "The selected dates are not available. Please choose different dates.";
    }
    
    // Calculate total
    if (empty($response['errors'])) {
        $total_nights = calculateNights($check_in, $check_out);
        $total_price = $total_nights * $cottage['price_per_night'];
        
        // Create reservation
        try {
            $pdo->beginTransaction();
            
            // Insert reservation
            $stmt = $pdo->prepare("
                INSERT INTO reservations 
                (user_id, cottage_id, check_in_date, check_out_date, total_nights, total_price, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_admin_review')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $cottage_id,
                $check_in,
                $check_out,
                $total_nights,
                $total_price
            ]);
            
            $reservation_id = $pdo->lastInsertId();
            
            // Log the action
            logAction($_SESSION['user_id'], 'CREATE_RESERVATION', 'reservations', $reservation_id);
            
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = "Reservation created successfully! Please upload payment proof.";
            $response['reservation_id'] = $reservation_id;
            $response['redirect'] = "upload-payment.php?reservation_id=" . $reservation_id;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $response['errors']['general'] = "Failed to create reservation. Please try again.";
        }
    }
} else {
    $response['errors']['general'] = "Invalid request method";
}

echo json_encode($response);
exit();