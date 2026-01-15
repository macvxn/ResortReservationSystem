<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
require_once '../config/database.php';
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
            up2.full_name as reviewed_by_name,
            ci.image_path as cottage_image
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.cottage_id
        LEFT JOIN payment_proofs pp ON r.reservation_id = pp.reservation_id
        JOIN users u ON r.user_id = u.user_id
        JOIN user_profiles up ON u.user_id = up.user_id
        LEFT JOIN users u2 ON r.reviewed_by = u2.user_id
        LEFT JOIN user_profiles up2 ON u2.user_id = up2.user_id
        LEFT JOIN cottage_images ci ON c.cottage_id = ci.cottage_id AND ci.is_primary = TRUE
        WHERE r.reservation_id = ? AND r.user_id = ?
        LIMIT 1
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

// Set AdminLTE page variables
$page_title = "Reservation Details - Aura Luxe Resort";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.min.css">
    
    <!-- Custom Resort Colors for AdminLTE -->
    <style>
        :root {
            --primary-turquoise: #40E0D0;
            --secondary-aqua: #00FFFF;
            --background-cream: #FFF5E1;
            --accent-coral: #FF7F50;
            --accent-yellow: #FFD300;
            --accent-watermelon: #FC6C85;
            --light-turquoise: rgba(64, 224, 208, 0.1);
            --light-aqua: rgba(0, 255, 255, 0.1);
        }
        
        /* Override AdminLTE primary color */
        .bg-primary, .btn-primary {
            background-color: var(--primary-turquoise) !important;
            border-color: var(--primary-turquoise) !important;
        }
        
        .text-primary {
            color: var(--primary-turquoise) !important;
        }
        
        .bg-secondary {
            background-color: var(--secondary-aqua) !important;
        }
        
        .bg-warning {
            background-color: var(--accent-yellow) !important;
        }
        
        .bg-danger {
            background-color: var(--accent-watermelon) !important;
        }
        
        .bg-success {
            background-color: #28a745 !important;
        }
        
        /* Card styling with resort theme */
        .card {
            border: 1px solid rgba(64, 224, 208, 0.15);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            border-bottom: none;
            padding: 18px 25px;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            background-color: white;
            padding: 25px;
        }
        
        /* Page header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(64, 224, 208, 0.2);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        
        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-link:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        /* Button customization */
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 224, 208, 0.3);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary-turquoise);
            border: 2px solid var(--primary-turquoise);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary-turquoise);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(64, 224, 208, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--accent-coral), #ff9a80);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #ff6b3d, var(--accent-coral));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 127, 80, 0.3);
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 6px;
        }
        
        /* Reservation header */
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 1px solid rgba(64, 224, 208, 0.15);
            box-shadow: 0 3px 10px rgba(0,0,0,0.04);
        }
        
        .reservation-id {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-turquoise);
            margin-bottom: 5px;
        }
        
        .reservation-date {
            color: #666;
            font-size: 14px;
        }
        
        /* Status badges */
        .status-badge {
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 140px;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, rgba(255, 211, 0, 0.15), rgba(255, 211, 0, 0.05));
            color: #b08d00;
            border: 1px solid rgba(255, 211, 0, 0.3);
        }
        
        .badge-approved {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.05));
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .badge-rejected {
            background: linear-gradient(135deg, rgba(252, 108, 133, 0.15), rgba(252, 108, 133, 0.05));
            color: var(--accent-watermelon);
            border: 1px solid rgba(252, 108, 133, 0.3);
        }
        
        .badge-completed {
            background: linear-gradient(135deg, rgba(108, 117, 125, 0.15), rgba(108, 117, 125, 0.05));
            color: #6c757d;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }
        
        /* Info sections */
        .info-section {
            background: white;
            border-radius: 10px;
            padding: 0;
            margin-bottom: 25px;
            border: 1px solid rgba(64, 224, 208, 0.15);
            overflow: hidden;
        }
        
        .info-section-header {
            background: linear-gradient(135deg, rgba(64, 224, 208, 0.1), rgba(0, 255, 255, 0.05));
            padding: 18px 25px;
            border-bottom: 1px solid rgba(64, 224, 208, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-section-header h3 {
            margin: 0;
            color: var(--primary-turquoise);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .info-section-body {
            padding: 25px;
        }
        
        /* Info rows */
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(64, 224, 208, 0.08);
            align-items: flex-start;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-label i {
            color: var(--primary-turquoise);
            width: 16px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
            text-align: right;
            font-weight: 500;
        }
        
        .info-value.price {
            color: var(--primary-turquoise);
            font-weight: 700;
            font-size: 16px;
        }
        
        /* Cottage image */
        .cottage-image-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .cottage-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(64, 224, 208, 0.2);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        /* Price breakdown */
        .price-breakdown {
            background: linear-gradient(135deg, rgba(64, 224, 208, 0.05), rgba(0, 255, 255, 0.02));
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(64, 224, 208, 0.2);
        }
        
        .price-row:last-child {
            border-bottom: none;
        }
        
        .price-row.total {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary-turquoise);
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid rgba(64, 224, 208, 0.3);
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-turquoise), var(--secondary-aqua));
            opacity: 0.3;
        }
        
        .timeline-step {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 30px;
        }
        
        .timeline-step:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .step-icon {
            position: absolute;
            left: -40px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: var(--primary-turquoise);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(64, 224, 208, 0.3);
            z-index: 2;
        }
        
        .timeline-step.completed .step-icon {
            background: var(--primary-turquoise);
            color: white;
            border-color: var(--primary-turquoise);
        }
        
        .timeline-step.current .step-icon {
            background: var(--accent-yellow);
            color: white;
            border-color: var(--accent-yellow);
            animation: pulse 2s infinite;
        }
        
        .timeline-step.rejected .step-icon {
            background: var(--accent-watermelon);
            color: white;
            border-color: var(--accent-watermelon);
        }
        
        .step-content {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            border: 1px solid rgba(64, 224, 208, 0.15);
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        
        .step-content h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        
        .step-content p {
            margin: 0;
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .step-content .step-detail {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 211, 0, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 211, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 211, 0, 0); }
        }
        
        /* Payment info */
        .payment-info {
            background: linear-gradient(135deg, rgba(0, 191, 255, 0.05), rgba(0, 255, 255, 0.02));
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
        }
        
        .payment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        /* Alert boxes */
        .alert-box {
            background: linear-gradient(135deg, rgba(255, 211, 0, 0.1), rgba(255, 211, 0, 0.05));
            border: 1px solid rgba(255, 211, 0, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .alert-box h4 {
            color: #b08d00;
            margin: 0 0 10px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alert-box p {
            color: #666;
            margin: 0 0 15px 0;
            line-height: 1.5;
        }
        
        /* Admin remarks */
        .remarks-box {
            background: linear-gradient(135deg, rgba(255, 127, 80, 0.05), rgba(255, 127, 80, 0.02));
            border: 1px solid rgba(255, 127, 80, 0.2);
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
        }
        
        .remarks-box h4 {
            color: var(--accent-coral);
            margin: 0 0 10px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remarks-content {
            color: #666;
            line-height: 1.6;
            white-space: pre-line;
        }
        
        /* Notes list */
        .notes-list {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }
        
        .notes-list li {
            padding: 10px 0;
            color: #555;
            border-bottom: 1px solid rgba(64, 224, 208, 0.1);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .notes-list li:last-child {
            border-bottom: none;
        }
        
        .notes-list li i {
            color: var(--primary-turquoise);
            margin-top: 2px;
        }
        
        /* Grid layout */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 992px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(64, 224, 208, 0.1);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-value {
                text-align: left;
            }
            
            .timeline {
                padding-left: 30px;
            }
            
            .step-icon {
                left: -30px;
                width: 30px;
                height: 30px;
            }
            
            .status-badge {
                min-width: 120px;
                padding: 6px 12px;
                font-size: 12px;
            }
            
            .card-body {
                padding: 20px 15px;
            }
            
            .info-section-body {
                padding: 20px 15px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
        }
        
        /* Print styles */
        @media print {
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .btn {
                display: none;
            }
            
            .page-header {
                background: white !important;
                color: black !important;
                box-shadow: none;
            }
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12">
                        <div class="page-header">
                            <h1>
                                <i class="fas fa-calendar-check mr-2"></i>Reservation Details
                            </h1>
                            <p class="mb-0 mt-2">
                                <a href="my-reservations.php" class="back-link">
                                    <i class="fas fa-arrow-left mr-1"></i>Back to My Reservations
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Reservation Header -->
                <div class="reservation-header">
                    <div>
                        <div class="reservation-id">
                            Reservation #<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="reservation-date">
                            Created on <?= formatDateTime($reservation['created_at']) ?>
                        </div>
                    </div>
                    <div>
                        <?php if ($reservation['status'] === 'pending_admin_review'): ?>
                            <span class="status-badge badge-pending">
                                <i class="fas fa-clock"></i> Pending Review
                            </span>
                        <?php elseif ($reservation['status'] === 'approved'): ?>
                            <?php if ($reservation['check_in_date'] >= date('Y-m-d')): ?>
                                <span class="status-badge badge-approved">
                                    <i class="fas fa-check-circle"></i> Approved - Upcoming
                                </span>
                            <?php else: ?>
                                <span class="status-badge badge-completed">
                                    <i class="fas fa-calendar-check"></i> Completed
                                </span>
                            <?php endif; ?>
                        <?php elseif ($reservation['status'] === 'rejected'): ?>
                            <span class="status-badge badge-rejected">
                                <i class="fas fa-times-circle"></i> Rejected
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="details-grid">
                    <!-- Left Column: Reservation Information -->
                    <div class="details-left">
                        <!-- Cottage Information Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-home"></i> Cottage Information</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($reservation['cottage_image'])): ?>
                                <div class="cottage-image-container">
                                    <img src="../uploads/cottages/<?= htmlspecialchars($reservation['cottage_image']) ?>" 
                                         alt="<?= htmlspecialchars($reservation['cottage_name']) ?>" 
                                         class="cottage-image">
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-section">
                                    <div class="info-section-header">
                                        <i class="fas fa-info-circle"></i>
                                        <h3>Details</h3>
                                    </div>
                                    <div class="info-section-body">
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-sign"></i>
                                                <span>Cottage Name:</span>
                                            </div>
                                            <div class="info-value">
                                                <?= htmlspecialchars($reservation['cottage_name']) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-users"></i>
                                                <span>Capacity:</span>
                                            </div>
                                            <div class="info-value">
                                                <?= $reservation['capacity'] ?> guests
                                            </div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-tag"></i>
                                                <span>Price per Night:</span>
                                            </div>
                                            <div class="info-value price">
                                                ₱<?= number_format($reservation['price_per_night'], 2) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dates & Timeline Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-calendar-alt"></i> Reservation Dates</h3>
                            </div>
                            <div class="card-body">
                                <div class="info-section">
                                    <div class="info-section-header">
                                        <i class="fas fa-calendar-day"></i>
                                        <h3>Stay Period</h3>
                                    </div>
                                    <div class="info-section-body">
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-sign-in-alt"></i>
                                                <span>Check-in Date:</span>
                                            </div>
                                            <div class="info-value">
                                                <?= formatDate($reservation['check_in_date']) ?>
                                                <div class="step-detail">2:00 PM</div>
                                            </div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-sign-out-alt"></i>
                                                <span>Check-out Date:</span>
                                            </div>
                                            <div class="info-value">
                                                <?= formatDate($reservation['check_out_date']) ?>
                                                <div class="step-detail">12:00 PM</div>
                                            </div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-moon"></i>
                                                <span>Total Nights:</span>
                                            </div>
                                            <div class="info-value">
                                                <?= $reservation['total_nights'] ?> night(s)
                                            </div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">
                                                <i class="fas fa-clock"></i>
                                                <span>Reservation Made:</span>
                                            </div>
                                            <div class="info-value">
                                                <?= formatDateTime($reservation['created_at']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Status & Payment -->
                    <div class="details-right">
                        <!-- Status Timeline Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-history"></i> Status Timeline</h3>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <!-- Step 1: Reservation Created -->
                                    <div class="timeline-step completed">
                                        <div class="step-icon">
                                            <i class="fas fa-calendar-plus"></i>
                                        </div>
                                        <div class="step-content">
                                            <h4>Reservation Created</h4>
                                            <p>Your reservation was successfully created</p>
                                            <div class="step-detail">
                                                <?= formatDateTime($reservation['created_at']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Step 2: Payment Proof Uploaded (if applicable) -->
                                    <?php if (!empty($reservation['receipt_image_path'])): ?>
                                    <div class="timeline-step completed">
                                        <div class="step-icon">
                                            <i class="fas fa-file-upload"></i>
                                        </div>
                                        <div class="step-content">
                                            <h4>Payment Proof Uploaded</h4>
                                            <p>Payment proof has been submitted for review</p>
                                            <div class="step-detail">
                                                <?= formatDateTime($reservation['payment_uploaded_at']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Step 3: Admin Review -->
                                    <div class="timeline-step <?= 
                                        $reservation['status'] === 'approved' ? 'completed' : 
                                        ($reservation['status'] === 'rejected' ? 'rejected' : 'current') 
                                    ?>">
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
                                            <p>
                                                <?php if ($reservation['status'] === 'approved'): ?>
                                                    Your reservation has been approved
                                                <?php elseif ($reservation['status'] === 'rejected'): ?>
                                                    Your reservation was rejected
                                                <?php else: ?>
                                                    Under review by resort admin
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($reservation['status'] !== 'pending_admin_review' && !empty($reservation['reviewed_at'])): ?>
                                            <div class="step-detail">
                                                Reviewed by: <?= htmlspecialchars($reservation['reviewed_by_name'] ?? 'Admin') ?>
                                                <br>
                                                <?= formatDateTime($reservation['reviewed_at']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment & Price Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-money-bill-wave"></i> Payment & Pricing</h3>
                            </div>
                            <div class="card-body">
                                <!-- Price Breakdown -->
                                <div class="info-section">
                                    <div class="info-section-header">
                                        <i class="fas fa-calculator"></i>
                                        <h3>Price Breakdown</h3>
                                    </div>
                                    <div class="info-section-body">
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
                                                <span>Total Amount:</span>
                                                <span>₱<?= number_format($reservation['total_price'], 2) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Information -->
                                <?php if (!empty($reservation['receipt_image_path'])): ?>
                                <div class="info-section">
                                    <div class="info-section-header">
                                        <i class="fas fa-credit-card"></i>
                                        <h3>Payment Information</h3>
                                    </div>
                                    <div class="info-section-body">
                                        <div class="payment-info">
                                            <div class="info-row">
                                                <div class="info-label">
                                                    <i class="fas fa-hashtag"></i>
                                                    <span>Reference Number:</span>
                                                </div>
                                                <div class="info-value">
                                                    <?= htmlspecialchars($reservation['reference_number']) ?>
                                                </div>
                                            </div>
                                            
                                            <div class="info-row">
                                                <div class="info-label">
                                                    <i class="fas fa-receipt"></i>
                                                    <span>Payment Proof:</span>
                                                </div>
                                                <div class="info-value">
                                                    <div class="payment-actions">
                                                        <a href="../uploads/payments/<?= htmlspecialchars($reservation['receipt_image_path']) ?>" 
                                                           target="_blank" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fas fa-eye mr-1"></i> View Receipt
                                                        </a>
                                                        <button onclick="window.print()" class="btn btn-secondary btn-sm">
                                                            <i class="fas fa-print mr-1"></i> Print
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="info-row">
                                                <div class="info-label">
                                                    <i class="fas fa-clock"></i>
                                                    <span>Uploaded:</span>
                                                </div>
                                                <div class="info-value">
                                                    <?= formatDateTime($reservation['payment_uploaded_at']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif ($reservation['status'] === 'pending_admin_review'): ?>
                                <!-- Payment Required Alert -->
                                <div class="alert-box">
                                    <h4>
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Action Required
                                    </h4>
                                    <p>
                                        <strong>Payment proof not uploaded yet.</strong>
                                        Please upload payment proof within 24 hours to avoid automatic cancellation of your reservation.
                                    </p>
                                    <div class="payment-actions">
                                        <a href="upload-payment.php?reservation_id=<?= $reservation['reservation_id'] ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-upload mr-1"></i> Upload Payment Now
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Additional Information Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                            </div>
                            <div class="card-body">
                                <!-- Admin Remarks -->
                                <?php if (!empty($reservation['admin_remarks'])): ?>
                                <div class="remarks-box">
                                    <h4>
                                        <i class="fas fa-comment-alt"></i>
                                        Admin Remarks
                                    </h4>
                                    <div class="remarks-content">
                                        <?= nl2br(htmlspecialchars($reservation['admin_remarks'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Important Notes -->
                                <div class="info-section">
                                    <div class="info-section-header">
                                        <i class="fas fa-clipboard-check"></i>
                                        <h3>Important Notes</h3>
                                    </div>
                                    <div class="info-section-body">
                                        <ul class="notes-list">
                                            <li>
                                                <i class="fas fa-clock"></i>
                                                <div>
                                                    <strong>Check-in time:</strong> 2:00 PM
                                                </div>
                                            </li>
                                            <li>
                                                <i class="fas fa-clock"></i>
                                                <div>
                                                    <strong>Check-out time:</strong> 12:00 PM
                                                </div>
                                            </li>
                                            <li>
                                                <i class="fas fa-id-card"></i>
                                                <div>
                                                    <strong>Required:</strong> Bring valid government-issued ID
                                                </div>
                                            </li>
                                            <li>
                                                <i class="fas fa-phone-alt"></i>
                                                <div>
                                                    <strong>Contact:</strong> (02) 1234-5678 for assistance
                                                </div>
                                            </li>
                                            <li>
                                                <i class="fas fa-calendar-times"></i>
                                                <div>
                                                    <strong>Cancellation:</strong> No cancellations after submission
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <a href="my-reservations.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i> Back to List
                                    </a>
                                    
                                    <?php if ($reservation['status'] === 'pending_admin_review' && empty($reservation['receipt_image_path'])): ?>
                                    <a href="upload-payment.php?reservation_id=<?= $reservation['reservation_id'] ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-upload mr-1"></i> Upload Payment
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($reservation['status'] === 'approved' && $reservation['check_in_date'] >= date('Y-m-d')): ?>
                                    <a href="cottage-details.php?id=<?= $reservation['cottage_id'] ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-home mr-1"></i> View Cottage
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="window.print()" class="btn btn-secondary">
                                        <i class="fas fa-print mr-1"></i> Print Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            <span style="color: var(--primary-turquoise);">Aura Luxe Resort</span>
        </div>
        <strong>Copyright &copy; <?php echo date('Y'); ?> Aura Luxe Resort.</strong> All rights reserved.
    </footer>
</div>

<!-- AdminLTE Scripts -->
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>
<script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../adminlte/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Print functionality
    $('.print-btn').on('click', function() {
        window.print();
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // Calculate days until check-in for upcoming reservations
    <?php if ($reservation['status'] === 'approved' && $reservation['check_in_date'] >= date('Y-m-d')): ?>
    const checkInDate = new Date('<?= $reservation['check_in_date'] ?>');
    const today = new Date();
    const diffTime = checkInDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 0) {
        // Add countdown badge
        $('.status-badge').after(`
            <div class="countdown-badge" style="margin-top: 10px; padding: 6px 12px; background: linear-gradient(135deg, rgba(255, 127, 80, 0.15), rgba(255, 127, 80, 0.05)); color: var(--accent-coral); border-radius: 20px; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                <i class="fas fa-hourglass-half"></i>
                Check-in in ${diffDays} day${diffDays !== 1 ? 's' : ''}
            </div>
        `);
    }
    <?php endif; ?>
});
</script>
</body>
</html>