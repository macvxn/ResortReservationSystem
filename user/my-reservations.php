<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireLogin();

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user's reservations
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               c.cottage_name,
               c.price_per_night,
               pp.receipt_image_path,
               pp.reference_number
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.cottage_id
        LEFT JOIN payment_proofs pp ON r.reservation_id = pp.reservation_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $reservations = $stmt->fetchAll();
} catch (Exception $e) {
    $reservations = [];
    $error = "Failed to load reservations.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Resort Reservation System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2><i class="fas fa-calendar-alt"></i> My Reservations</h2>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'payment_uploaded'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <strong>Success!</strong> Payment proof uploaded successfully.
        </div>
        <?php endif; ?>
        
        <?php if (empty($reservations)): ?>
        <div class="no-results">
            <i class="fas fa-calendar-times fa-3x"></i>
            <h3>No Reservations Yet</h3>
            <p>You haven't made any reservations yet.</p>
            <a href="cottages.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Browse Cottages
            </a>
        </div>
        <?php else: ?>
        
        <div class="reservations-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cottage</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): 
                        $hasPayment = !empty($reservation['receipt_image_path']);
                    ?>
                    <tr>
                        <td>
                            <strong>#<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?></strong>
                            <br>
                            <small><?= formatDateTime($reservation['created_at']) ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($reservation['cottage_name']) ?></strong>
                            <br>
                            <small><?= $reservation['total_nights'] ?> night(s)</small>
                        </td>
                        <td>
                            <strong><?= formatDate($reservation['check_in_date']) ?></strong>
                            <br>
                            <small>to <?= formatDate($reservation['check_out_date']) ?></small>
                        </td>
                        <td>
                            <strong class="price">₱<?= number_format($reservation['total_price'], 2) ?></strong>
                        </td>
                        <td>
                            <?= getReservationBadge($reservation['status']) ?>
                        </td>
                        <td>
                            <?php if ($hasPayment): ?>
                                <span style="background: #17a2b8; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px;">
                                    <i class="fas fa-check-circle"></i> Uploaded
                                </span>
                            <?php elseif ($reservation['status'] === 'pending_admin_review'): ?>
                                <span style="background: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px; font-size: 12px;">
                                    <i class="fas fa-clock"></i> Awaiting
                                </span>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($reservation['status'] === 'pending_admin_review' && !$hasPayment): ?>
                            <a href="upload-payment.php?reservation_id=<?= $reservation['reservation_id'] ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-upload"></i> Pay Now
                            </a>
                            <?php endif; ?>
                            
                            <a href="reservation-details.php?id=<?= $reservation['reservation_id'] ?>" 
   class="btn btn-sm btn-secondary" 
   title="View Details">
    <i class="fas fa-eye"></i> View
</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <p>Showing <?= count($reservations) ?> reservation(s)</p>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>