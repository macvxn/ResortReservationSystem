<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

$reservation_id = $_GET['id'] ?? 0;
if (!$reservation_id) {
    header("Location: reservations.php");
    exit();
}

// Get reservation details
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE reservation_id = ? AND status = 'pending_admin_review'
");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header("Location: reservations.php?error=reservation_not_found");
    exit();
}

// Check if payment is uploaded
$stmt = $pdo->prepare("SELECT * FROM payment_proofs WHERE reservation_id = ?");
$stmt->execute([$reservation_id]);
$payment = $stmt->fetch();

if (!$payment) {
    header("Location: reservations.php?error=payment_not_uploaded");
    exit();
}

// Check for date conflicts
if (!isDateAvailable($reservation['cottage_id'], $reservation['check_in_date'], $reservation['check_out_date'], $reservation_id)) {
    header("Location: reservations.php?error=date_conflict");
    exit();
}

// Approve reservation
try {
    $pdo->beginTransaction();
    
    // Update reservation status
    $stmt = $pdo->prepare("
        UPDATE reservations 
        SET status = 'approved', 
            reviewed_by = ?, 
            reviewed_at = NOW() 
        WHERE reservation_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $reservation_id]);
    
    // Log action
    logAction($_SESSION['user_id'], 'APPROVE_RESERVATION', 'reservations', $reservation_id);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Reservation #" . str_pad($reservation_id, 6, '0', STR_PAD_LEFT) . " approved successfully.";
    header("Location: reservations.php");
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: reservations.php?error=approval_failed");
    exit();
}
?>