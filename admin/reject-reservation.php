<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

$reservation_id = $_GET['id'] ?? 0;
if (!$reservation_id) {
    header("Location: reservations.php");
    exit();
}

// Handle form submission for rejection remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remarks = clean($_POST['remarks'] ?? '');
    
    if (empty($remarks)) {
        $_SESSION['error'] = "Remarks are required for rejection.";
        header("Location: reject-reservation.php?id=" . $reservation_id);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update reservation status with remarks
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET status = 'rejected', 
                admin_remarks = ?,
                reviewed_by = ?, 
                reviewed_at = NOW() 
            WHERE reservation_id = ?
        ");
        $stmt->execute([$remarks, $_SESSION['user_id'], $reservation_id]);
        
        // Log action
        logAction($_SESSION['user_id'], 'REJECT_RESERVATION', 'reservations', $reservation_id);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Reservation #" . str_pad($reservation_id, 6, '0', STR_PAD_LEFT) . " rejected.";
        header("Location: reservations.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to reject reservation.";
        header("Location: reject-reservation.php?id=" . $reservation_id);
        exit();
    }
}

// Get reservation details
$stmt = $pdo->prepare("
    SELECT r.*, c.cottage_name, u.email, up.full_name 
    FROM reservations r
    JOIN cottages c ON r.cottage_id = c.cottage_id
    JOIN users u ON r.user_id = u.user_id
    JOIN user_profiles up ON u.user_id = up.user_id
    WHERE r.reservation_id = ? AND r.status = 'pending_admin_review'
");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header("Location: reservations.php?error=reservation_not_found");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Reservation - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container" style="max-width: 600px;">
        <h2><i class="fas fa-times-circle"></i> Reject Reservation</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Reservation Summary -->
        <div class="reservation-summary">
            <h4>Reservation Details</h4>
            <div class="summary-item">
                <span>ID:</span>
                <span>#<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="summary-item">
                <span>Guest:</span>
                <span><?= htmlspecialchars($reservation['full_name']) ?> (<?= htmlspecialchars($reservation['email']) ?>)</span>
            </div>
            <div class="summary-item">
                <span>Cottage:</span>
                <span><?= htmlspecialchars($reservation['cottage_name']) ?></span>
            </div>
            <div class="summary-item">
                <span>Dates:</span>
                <span><?= formatDate($reservation['check_in_date']) ?> to <?= formatDate($reservation['check_out_date']) ?></span>
            </div>
            <div class="summary-item">
                <span>Amount:</span>
                <span>₱<?= number_format($reservation['total_price'], 2) ?></span>
            </div>
        </div>
        
        <!-- Rejection Form -->
        <form method="POST" class="rejection-form">
            <div class="form-group">
                <label for="remarks">
                    <i class="fas fa-comment-alt"></i> Reason for Rejection *
                </label>
                <textarea id="remarks" 
                          name="remarks" 
                          rows="5" 
                          class="form-control"
                          placeholder="Provide specific reason for rejecting this reservation..."
                          required></textarea>
                <small>The guest will see this reason in their reservation details.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i> Confirm Rejection
                </button>
                <a href="reservations.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> This action cannot be undone. The guest will be notified.
            </div>
        </form>
    </div>
    
    <style>
        .reservation-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #dc3545;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .rejection-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }
    </style>
</body>
</html>