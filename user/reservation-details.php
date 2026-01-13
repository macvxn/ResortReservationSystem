<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireLogin();

// Get reservation ID
$reservation_id = $_GET['id'] ?? 0;
if (!$reservation_id) {
    header("Location: my-reservations.php");
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get reservation details with all info
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            c.cottage_name,
            c.capacity,
            c.price_per_night,
            pp.receipt_image_path,
            pp.reference_number,
            pp.uploaded_at as payment_uploaded_at,
            up.full_name,
            u.email,
            u2.email as reviewed_by_email,
            up2.full_name as reviewed_by_name
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.cottage_id
        LEFT JOIN payment_proofs pp ON r.reservation_id = pp.reservation_id
        JOIN users u ON r.user_id = u.user_id
        JOIN user_profiles up ON u.user_id = up.user_id
        LEFT JOIN users u2 ON r.reviewed_by = u2.user_id
        LEFT JOIN user_profiles up2 ON u2.user_id = up2.user_id
        WHERE r.reservation_id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        header("Location: my-reservations.php?error=not_found");
        exit();
    }
    
} catch (Exception $e) {
    header("Location: my-reservations.php?error=system");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details - Resort Reservation System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <!-- Back Button -->
        <a href="my-reservations.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to My Reservations
        </a>
        
        <div class="reservation-details">
            <!-- Header -->
            <div class="reservation-header">
                <h2>
                    <i class="fas fa-calendar-check"></i>
                    Reservation #<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?>
                </h2>
                <div class="status-badge">
                    <?= getReservationBadge($reservation['status']) ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="details-grid">
                <!-- Left Column: Reservation Info -->
                <div class="details-left">
                    <!-- Cottage Info -->
                    <div class="detail-section">
                        <h3><i class="fas fa-home"></i> Cottage Information</h3>
                        <div class="info-row">
                            <span class="label">Cottage Name:</span>
                            <span class="value"><?= htmlspecialchars($reservation['cottage_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Capacity:</span>
                            <span class="value"><?= $reservation['capacity'] ?> guests</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Price per Night:</span>
                            <span class="value">₱<?= number_format($reservation['price_per_night'], 2) ?></span>
                        </div>
                    </div>
                    
                    <!-- Dates -->
                    <div class="detail-section">
                        <h3><i class="fas fa-calendar-alt"></i> Reservation Dates</h3>
                        <div class="info-row">
                            <span class="label">Check-in Date:</span>
                            <span class="value"><?= formatDate($reservation['check_in_date']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Check-out Date:</span>
                            <span class="value"><?= formatDate($reservation['check_out_date']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Total Nights:</span>
                            <span class="value"><?= $reservation['total_nights'] ?> night(s)</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Reservation Made:</span>
                            <span class="value"><?= formatDateTime($reservation['created_at']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Price Breakdown -->
                    <div class="detail-section">
                        <h3><i class="fas fa-money-bill-wave"></i> Price Breakdown</h3>
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>Price per night:</span>
                                <span>₱<?= number_format($reservation['price_per_night'], 2) ?></span>
                            </div>
                            <div class="price-row">
                                <span>Number of nights:</span>
                                <span><?= $reservation['total_nights'] ?></span>
                            </div>
                            <div class="price-row total">
                                <span><strong>Total Amount:</strong></span>
                                <span><strong>₱<?= number_format($reservation['total_price'], 2) ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Status & Payment -->
                <div class="details-right">
                    <!-- Status Timeline -->
                    <div class="detail-section">
                        <h3><i class="fas fa-history"></i> Reservation Status</h3>
                        <div class="timeline">
                            <div class="timeline-step <?= $reservation['status'] !== 'pending_admin_review' ? 'completed' : 'current' ?>">
                                <div class="step-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="step-content">
                                    <h4>Reservation Created</h4>
                                    <p><?= formatDateTime($reservation['created_at']) ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($reservation['receipt_image_path'])): ?>
                            <div class="timeline-step <?= $reservation['status'] !== 'pending_admin_review' ? 'completed' : 'current' ?>">
                                <div class="step-icon">
                                    <i class="fas fa-file-upload"></i>
                                </div>
                                <div class="step-content">
                                    <h4>Payment Proof Uploaded</h4>
                                    <p><?= formatDateTime($reservation['payment_uploaded_at']) ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="timeline-step <?= $reservation['status'] === 'approved' ? 'completed' : ($reservation['status'] === 'rejected' ? 'rejected' : 'pending') ?>">
                                <div class="step-icon">
                                    <?php if ($reservation['status'] === 'approved'): ?>
                                    <i class="fas fa-check-circle"></i>
                                    <?php elseif ($reservation['status'] === 'rejected'): ?>
                                    <i class="fas fa-times-circle"></i>
                                    <?php else: ?>
                                    <i class="fas fa-clock"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-content">
                                    <h4>
                                        <?php if ($reservation['status'] === 'approved'): ?>
                                        Reservation Approved
                                        <?php elseif ($reservation['status'] === 'rejected'): ?>
                                        Reservation Rejected
                                        <?php else: ?>
                                        Awaiting Admin Review
                                        <?php endif; ?>
                                    </h4>
                                    <?php if ($reservation['status'] !== 'pending_admin_review'): ?>
                                    <p>Reviewed by: <?= htmlspecialchars($reservation['reviewed_by_name'] ?? 'Admin') ?></p>
                                    <p><?= formatDateTime($reservation['reviewed_at']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Information -->
                    <?php if (!empty($reservation['receipt_image_path'])): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                        <div class="payment-info">
                            <div class="info-row">
                                <span class="label">Reference Number:</span>
                                <span class="value"><?= htmlspecialchars($reservation['reference_number']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Payment Proof:</span>
                                <a href="../uploads/payments/<?= htmlspecialchars($reservation['receipt_image_path']) ?>" 
                                   target="_blank" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Receipt
                                </a>
                            </div>
                            <div class="info-row">
                                <span class="label">Uploaded:</span>
                                <span class="value"><?= formatDateTime($reservation['payment_uploaded_at']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($reservation['status'] === 'pending_admin_review'): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-exclamation-triangle"></i> Action Required</h3>
                        <div class="alert alert-warning">
                            <p><strong>Payment proof not uploaded yet.</strong></p>
                            <p>Please upload payment proof within 24 hours to avoid cancellation.</p>
                            <a href="upload-payment.php?reservation_id=<?= $reservation['reservation_id'] ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload Payment Now
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Admin Remarks -->
                    <?php if (!empty($reservation['admin_remarks'])): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-comment-alt"></i> Admin Remarks</h3>
                        <div class="remarks-box">
                            <p><?= nl2br(htmlspecialchars($reservation['admin_remarks'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Important Notes -->
                    <div class="detail-section">
                        <h3><i class="fas fa-info-circle"></i> Important Notes</h3>
                        <ul class="notes-list">
                            <li><i class="fas fa-check-circle"></i> Check-in time: 2:00 PM</li>
                            <li><i class="fas fa-check-circle"></i> Check-out time: 12:00 PM</li>
                            <li><i class="fas fa-check-circle"></i> Bring valid ID during check-in</li>
                            <li><i class="fas fa-check-circle"></i> Contact resort for any changes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Reservation Details Styles */
        .reservation-details {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .detail-section h3 {
            color: #495057;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-row .label {
            font-weight: bold;
            color: #495057;
        }
        
        .info-row .value {
            color: #6c757d;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-step {
            position: relative;
            margin-bottom: 25px;
        }
        
        .timeline-step:last-child {
            margin-bottom: 0;
        }
        
        .step-icon {
            position: absolute;
            left: -30px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timeline-step.completed .step-icon {
            background: #28a745;
        }
        
        .timeline-step.current .step-icon {
            background: #007bff;
        }
        
        .timeline-step.rejected .step-icon {
            background: #dc3545;
        }
        
        .step-content h4 {
            margin: 0 0 5px 0;
            color: #495057;
        }
        
        .step-content p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Price Breakdown */
        .price-breakdown {
            background: white;
            padding: 15px;
            border-radius: 5px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .price-row.total {
            font-weight: bold;
            font-size: 1.2em;
            color: #28a745;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
        }
        
        /* Payment Info */
        .payment-info {
            background: white;
            padding: 15px;
            border-radius: 5px;
        }
        
        /* Remarks Box */
        .remarks-box {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        
        /* Notes List */
        .notes-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .notes-list li {
            padding: 8px 0;
            color: #495057;
        }
        
        .notes-list li i {
            color: #28a745;
            margin-right: 10px;
        }
        
        /* Alert Box */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .alert p {
            margin: 0 0 10px 0;
        }
        
        .alert p:last-child {
            margin-bottom: 0;
        }
    </style>
</body>
</html>