<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

$reservation_id = $_GET['id'] ?? 0;
if (!$reservation_id) {
    header("Location: reservations.php");
    exit();
}

// Get reservation details with all info
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        c.cottage_name,
        c.capacity,
        c.price_per_night,
        pp.receipt_image_path,
        pp.reference_number,
        pp.uploaded_at as payment_uploaded_at,
        u.email,
        up.full_name,
        up.phone_number,
        up.verification_status,
        u2.email as reviewed_by_email,
        up2.full_name as reviewed_by_name
    FROM reservations r
    JOIN cottages c ON r.cottage_id = c.cottage_id
    JOIN users u ON r.user_id = u.user_id
    JOIN user_profiles up ON u.user_id = up.user_id
    LEFT JOIN payment_proofs pp ON r.reservation_id = pp.reservation_id
    LEFT JOIN users u2 ON r.reviewed_by = u2.user_id
    LEFT JOIN user_profiles up2 ON u2.user_id = up2.user_id
    WHERE r.reservation_id = ?
");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header("Location: reservations.php?error=not_found");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <!-- Back Button -->
        <a href="reservations.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Reservations
        </a>
        
        <div class="reservation-header">
            <h2>
                <i class="fas fa-calendar-check"></i>
                Reservation #<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?>
            </h2>
            
            <div class="action-buttons">
                <?php if ($reservation['status'] === 'pending_admin_review'): ?>
                <a href="approve-reservation.php?id=<?= $reservation['reservation_id'] ?>" 
                   class="btn btn-success"
                   onclick="return confirm('Approve this reservation?')">
                    <i class="fas fa-check"></i> Approve
                </a>
                <a href="reject-reservation.php?id=<?= $reservation['reservation_id'] ?>" 
                   class="btn btn-danger">
                    <i class="fas fa-times"></i> Reject
                </a>
                <?php endif; ?>
                
                <a href="../uploads/payments/<?= $reservation['receipt_image_path'] ?>" 
                   target="_blank" 
                   class="btn btn-info <?= empty($reservation['receipt_image_path']) ? 'disabled' : '' ?>">
                    <i class="fas fa-receipt"></i> View Receipt
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="details-grid">
            <!-- Left Column: Guest & Reservation Info -->
            <div class="details-left">
                <!-- Guest Information -->
                <div class="detail-section">
                    <h3><i class="fas fa-user"></i> Guest Information</h3>
                    <div class="info-row">
                        <span class="label">Full Name:</span>
                        <span class="value"><?= htmlspecialchars($reservation['full_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value"><?= htmlspecialchars($reservation['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone:</span>
                        <span class="value"><?= htmlspecialchars($reservation['phone_number'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Verification:</span>
                        <span class="value"><?= getVerificationBadge($reservation['verification_status']) ?></span>
                    </div>
                </div>
                
                <!-- Reservation Details -->
                <div class="detail-section">
                    <h3><i class="fas fa-home"></i> Reservation Details</h3>
                    <div class="info-row">
                        <span class="label">Cottage:</span>
                        <span class="value"><?= htmlspecialchars($reservation['cottage_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Capacity:</span>
                        <span class="value"><?= $reservation['capacity'] ?> guests</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Check-in:</span>
                        <span class="value"><?= formatDate($reservation['check_in_date']) ?> (2:00 PM)</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Check-out:</span>
                        <span class="value"><?= formatDate($reservation['check_out_date']) ?> (12:00 PM)</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Total Nights:</span>
                        <span class="value"><?= $reservation['total_nights'] ?> night(s)</span>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div class="detail-section">
                    <h3><i class="fas fa-history"></i> Timeline</h3>
                    <div class="timeline">
                        <div class="timeline-step completed">
                            <div class="step-icon"><i class="fas fa-calendar-plus"></i></div>
                            <div class="step-content">
                                <h4>Reservation Created</h4>
                                <p><?= formatDateTime($reservation['created_at']) ?></p>
                            </div>
                        </div>
                        
                        <?php if ($reservation['receipt_image_path']): ?>
                        <div class="timeline-step completed">
                            <div class="step-icon"><i class="fas fa-file-upload"></i></div>
                            <div class="step-content">
                                <h4>Payment Uploaded</h4>
                                <p><?= formatDateTime($reservation['payment_uploaded_at']) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="timeline-step <?= $reservation['status'] === 'approved' ? 'completed' : ($reservation['status'] === 'rejected' ? 'rejected' : 'current') ?>">
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
                                    Approved
                                    <?php elseif ($reservation['status'] === 'rejected'): ?>
                                    Rejected
                                    <?php else: ?>
                                    Pending Review
                                    <?php endif; ?>
                                </h4>
                                <?php if ($reservation['status'] !== 'pending_admin_review'): ?>
                                <p>By: <?= $reservation['reviewed_by_name'] ?? 'Admin' ?></p>
                                <p><?= formatDateTime($reservation['reviewed_at']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Payment & Financial Info -->
            <div class="details-right">
                <!-- Payment Information -->
                <div class="detail-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <?php if ($reservation['receipt_image_path']): ?>
                    <div class="payment-info">
                        <div class="info-row">
                            <span class="label">Reference Number:</span>
                            <span class="value"><?= htmlspecialchars($reservation['reference_number']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Receipt:</span>
                            <span class="value">
                                <a href="../uploads/payments/<?= htmlspecialchars($reservation['receipt_image_path']) ?>" 
                                   target="_blank" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Receipt
                                </a>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="label">Uploaded:</span>
                            <span class="value"><?= formatDateTime($reservation['payment_uploaded_at']) ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No payment proof uploaded yet.</strong>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Financial Breakdown -->
                <div class="detail-section">
                    <h3><i class="fas fa-money-bill-wave"></i> Financial Breakdown</h3>
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
                
                <!-- Admin Remarks -->
                <?php if (!empty($reservation['admin_remarks'])): ?>
                <div class="detail-section">
                    <h3><i class="fas fa-comment-alt"></i> Admin Remarks</h3>
                    <div class="remarks-box">
                        <p><?= nl2br(htmlspecialchars($reservation['admin_remarks'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="detail-section">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        <?php if ($reservation['status'] === 'pending_admin_review'): ?>
                        <a href="approve-reservation.php?id=<?= $reservation['reservation_id'] ?>" 
                           class="btn btn-success btn-block"
                           onclick="return confirm('Approve this reservation?')">
                            <i class="fas fa-check"></i> Approve Reservation
                        </a>
                        <a href="reject-reservation.php?id=<?= $reservation['reservation_id'] ?>" 
                           class="btn btn-danger btn-block">
                            <i class="fas fa-times"></i> Reject Reservation
                        </a>
                        <?php elseif ($reservation['status'] === 'approved'): ?>
                        <a href="#" class="btn btn-info btn-block">
                            <i class="fas fa-envelope"></i> Send Confirmation Email
                        </a>
                        <?php endif; ?>
                        
                        <a href="edit-reservation.php?id=<?= $reservation['reservation_id'] ?>" 
                           class="btn btn-warning btn-block">
                            <i class="fas fa-edit"></i> Edit Reservation
                        </a>
                        
                        <a href="audit-logs.php?reservation_id=<?= $reservation['reservation_id'] ?>" 
                           class="btn btn-secondary btn-block">
                            <i class="fas fa-history"></i> View Audit Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Same styles as user reservation-details.php but with admin colors */
        .admin-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
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
            
            .reservation-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: flex-start;
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
            text-align: right;
        }
        
        .btn-block {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
    </style>
</body>
</html>