<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
require_once '../config/database.php';
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

// Get reservation statistics
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending_admin_review' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'approved' AND check_in_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming
        FROM reservations 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'upcoming' => 0];
    }
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'upcoming' => 0];
}

// Set AdminLTE page variables
$page_title = "My Reservations - Aura Luxe Resort";
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
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    
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
            font-size: 1.4rem;
        }
        
        .card-body {
            background-color: white;
            padding: 25px;
        }
        
        /* Page header */
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(64, 224, 208, 0.2);
        }
        
        .welcome-header h1 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .welcome-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            transition: opacity 0.3s;
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
        
        .btn-group .btn {
            margin-right: 5px;
            border-radius: 6px;
        }
        
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        
        /* Stats cards - Clean Design */
        .info-box {
            background: white;
            border-radius: 10px;
            border: 1px solid rgba(64, 224, 208, 0.15);
            box-shadow: 0 3px 10px rgba(0,0,0,0.04);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .info-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .info-box-icon {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px 0 0 10px;
        }
        
        .info-box-icon i {
            font-size: 24px;
        }
        
        .info-box-content {
            padding: 15px;
            flex: 1;
        }
        
        .info-box-number {
            color: var(--primary-turquoise);
            font-weight: 700;
            font-size: 28px;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .info-box-text {
            color: #666;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Alert customization */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.08);
            border-left-color: #28a745;
        }
        
        .alert-danger {
            background-color: rgba(252, 108, 133, 0.08);
            border-left-color: var(--accent-watermelon);
        }
        
        .alert-info {
            background-color: rgba(0, 191, 255, 0.08);
            border-left-color: var(--secondary-aqua);
        }
        
        .alert-warning {
            background-color: rgba(255, 211, 0, 0.08);
            border-left-color: var(--accent-yellow);
        }
        
        /* Status badges - Clean & Consistent */
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-width: 100px;
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
        
        .badge-awaiting-payment {
            background: linear-gradient(135deg, rgba(64, 224, 208, 0.15), rgba(0, 255, 255, 0.05));
            color: var(--primary-turquoise);
            border: 1px solid rgba(64, 224, 208, 0.3);
        }
        
        .badge-payment-uploaded {
            background: linear-gradient(135deg, rgba(0, 191, 255, 0.15), rgba(0, 191, 255, 0.05));
            color: var(--secondary-aqua);
            border: 1px solid rgba(0, 191, 255, 0.3);
        }
        
        /* Payment status badges */
        .payment-status {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Quick actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: white;
            border: 2px solid rgba(64, 224, 208, 0.2);
            border-radius: 10px;
            color: var(--primary-turquoise);
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            min-width: 180px;
            justify-content: center;
        }
        
        .quick-action-btn:hover {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            border-color: var(--primary-turquoise);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 224, 208, 0.2);
            text-decoration: none;
        }
        
        /* DataTables customization - Clean Design */
        .dataTables_wrapper {
            background: white;
            border-radius: 10px;
            padding: 0;
            border: 1px solid rgba(64, 224, 208, 0.15);
            overflow: hidden;
        }
        
        table.dataTable {
            border-collapse: collapse !important;
            border-spacing: 0;
            width: 100%;
        }
        
        table.dataTable thead th {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            border: none;
            font-weight: 600;
            padding: 16px 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table.dataTable tbody td {
            padding: 16px 12px;
            border-bottom: 1px solid rgba(64, 224, 208, 0.08);
            vertical-align: middle;
        }
        
        table.dataTable tbody tr {
            transition: background-color 0.2s ease;
        }
        
        table.dataTable tbody tr:hover {
            background-color: rgba(64, 224, 208, 0.04);
        }
        
        table.dataTable tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Table cell styling */
        .table-cell-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .table-cell-primary {
            font-weight: 600;
            color: #333;
        }
        
        .table-cell-secondary {
            font-size: 13px;
            color: #666;
        }
        
        /* Price styling */
        .price-amount {
            color: var(--primary-turquoise);
            font-weight: 700;
            font-size: 16px;
        }
        
        .price-per-night {
            color: #888;
            font-size: 12px;
        }
        
        /* No results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
        }
        
        .no-results i {
            font-size: 70px;
            color: rgba(64, 224, 208, 0.2);
            margin-bottom: 25px;
        }
        
        .no-results h3 {
            color: #555;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .no-results p {
            color: #777;
            margin-bottom: 30px;
            font-size: 16px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        /* DataTables controls */
        .dataTables_length,
        .dataTables_filter {
            padding: 15px 20px;
            background: rgba(64, 224, 208, 0.03);
            border-bottom: 1px solid rgba(64, 224, 208, 0.1);
        }
        
        .dataTables_filter label {
            margin-bottom: 0;
        }
        
        .dataTables_filter input {
            border: 1px solid rgba(64, 224, 208, 0.3);
            border-radius: 6px;
            padding: 8px 12px;
            transition: all 0.3s;
        }
        
        .dataTables_filter input:focus {
            border-color: var(--primary-turquoise);
            box-shadow: 0 0 0 3px rgba(64, 224, 208, 0.1);
            outline: none;
        }
        
        .dataTables_info {
            padding: 15px 20px;
            background: rgba(64, 224, 208, 0.03);
            border-top: 1px solid rgba(64, 224, 208, 0.1);
            color: #666;
        }
        
        .dataTables_paginate {
            padding: 15px 20px;
            background: rgba(64, 224, 208, 0.03);
            border-top: 1px solid rgba(64, 224, 208, 0.1);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .card-body {
                padding: 15px;
            }
            
            .quick-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .quick-action-btn {
                width: 100%;
                max-width: 300px;
            }
            
            .info-box {
                margin-bottom: 15px;
            }
            
            table.dataTable thead th,
            table.dataTable tbody td {
                padding: 12px 8px;
                font-size: 13px;
            }
            
            .status-badge {
                min-width: 80px;
                padding: 5px 10px;
                font-size: 11px;
            }
            
            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-group .btn {
                margin-right: 0;
                width: 100%;
            }
        }
        
        /* Animation for alerts */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert {
            animation: fadeIn 0.3s ease;
        }
        
        /* Badge icons */
        .badge-icon {
            font-size: 11px;
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
                        <div class="welcome-header">
                            <h1 class="m-0">
                                <i class="fas fa-calendar-alt mr-2"></i>My Reservations
                            </h1>
                            <p class="mb-0 mt-2">
                                <a href="dashboard.php" class="back-link">
                                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
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
                <!-- Success Messages -->
                <?php if (isset($_GET['success']) && $_GET['success'] === 'payment_uploaded'): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check-circle mr-2"></i>Payment Uploaded Successfully!</h5>
                    Your payment proof has been submitted for admin review. You'll be notified once approved.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error']) && $_GET['error'] === 'already_uploaded'): ?>
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-info-circle mr-2"></i>Payment Already Submitted</h5>
                    Payment proof has already been uploaded for this reservation.
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-exclamation-triangle mr-2"></i>Error Loading Reservations</h5>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="cottages.php" class="quick-action-btn">
                        <i class="fas fa-home"></i>
                        Browse Cottages
                    </a>
                    
                    <?php if ($stats['pending'] > 0): ?>
                    <a href="#pending" class="quick-action-btn" id="pendingBtn">
                        <i class="fas fa-clock"></i>
                        Pending (<?php echo $stats['pending']; ?>)
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($stats['upcoming'] > 0): ?>
                    <a href="#upcoming" class="quick-action-btn" id="upcomingBtn">
                        <i class="fas fa-calendar-check"></i>
                        Upcoming (<?php echo $stats['upcoming']; ?>)
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="info-box">
                            <span class="info-box-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-number"><?php echo $stats['total']; ?></span>
                                <span class="info-box-text">Total Reservations</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="info-box">
                            <span class="info-box-icon">
                                <i class="fas fa-clock"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-number"><?php echo $stats['pending']; ?></span>
                                <span class="info-box-text">Pending Review</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="info-box">
                            <span class="info-box-icon">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-number"><?php echo $stats['approved']; ?></span>
                                <span class="info-box-text">Approved</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="info-box">
                            <span class="info-box-icon">
                                <i class="fas fa-times-circle"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-number"><?php echo $stats['rejected']; ?></span>
                                <span class="info-box-text">Rejected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (empty($reservations)): ?>
                <!-- No Reservations -->
                <div class="card">
                    <div class="card-body">
                        <div class="no-results">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Reservations Yet</h3>
                            <p>You haven't made any reservations yet. Explore our beautiful cottages and plan your perfect getaway!</p>
                            <a href="cottages.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-home mr-2"></i>Browse Cottages
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                
                <!-- Reservations Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list-alt mr-2"></i> Reservation History</h3>
                        <div class="card-tools">
                            <span class="badge bg-primary" style="font-size: 14px; padding: 6px 12px;"><?php echo count($reservations); ?> Reservations</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="reservationsTable" class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">Reservation ID</th>
                                        <th style="width: 20%;">Cottage</th>
                                        <th style="width: 18%;">Dates</th>
                                        <th style="width: 15%;">Total</th>
                                        <th style="width: 15%;">Status</th>
                                        <th style="width: 17%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): 
                                        $hasPayment = !empty($reservation['receipt_image_path']);
                                        $needsPayment = $reservation['status'] === 'pending_admin_review' && !$hasPayment;
                                        $isUpcoming = $reservation['status'] === 'approved' && $reservation['check_in_date'] >= date('Y-m-d');
                                        $isCompleted = $reservation['status'] === 'approved' && $reservation['check_in_date'] < date('Y-m-d');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="table-cell-content">
                                                <span class="table-cell-primary">
                                                    #<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?>
                                                </span>
                                                <span class="table-cell-secondary">
                                                    <?= formatDateTime($reservation['created_at']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-cell-content">
                                                <span class="table-cell-primary">
                                                    <?= htmlspecialchars($reservation['cottage_name']) ?>
                                                </span>
                                                <span class="table-cell-secondary">
                                                    <?= $reservation['total_nights'] ?> night(s)
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-cell-content">
                                                <span class="table-cell-primary">
                                                    <i class="fas fa-sign-in-alt mr-1" style="color: var(--primary-turquoise); font-size: 12px;"></i>
                                                    <?= formatDate($reservation['check_in_date']) ?>
                                                </span>
                                                <span class="table-cell-secondary">
                                                    <i class="fas fa-sign-out-alt mr-1" style="color: var(--primary-turquoise); font-size: 12px;"></i>
                                                    <?= formatDate($reservation['check_out_date']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-cell-content">
                                                <span class="price-amount">
                                                    ₱<?= number_format($reservation['total_price'], 2) ?>
                                                </span>
                                                <span class="price-per-night">
                                                    ₱<?= number_format($reservation['price_per_night'], 2) ?>/night
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column align-items-start gap-1">
                                                <?php if ($reservation['status'] === 'pending_admin_review'): ?>
                                                    <span class="status-badge badge-pending">
                                                        <i class="fas fa-clock badge-icon"></i> Pending
                                                    </span>
                                                    <?php if ($needsPayment): ?>
                                                        <span class="status-badge badge-awaiting-payment" style="font-size: 11px; padding: 4px 10px;">
                                                            <i class="fas fa-exclamation-circle badge-icon"></i> Payment Required
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="table-cell-secondary" style="font-size: 11px;">
                                                            Payment submitted
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif ($reservation['status'] === 'approved'): ?>
                                                    <?php if ($isUpcoming): ?>
                                                        <span class="status-badge badge-approved">
                                                            <i class="fas fa-check-circle badge-icon"></i> Approved
                                                        </span>
                                                        <span class="table-cell-secondary" style="font-size: 11px;">
                                                            Upcoming stay
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge badge-completed">
                                                            <i class="fas fa-calendar-check badge-icon"></i> Completed
                                                        </span>
                                                        <span class="table-cell-secondary" style="font-size: 11px;">
                                                            Stay completed
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif ($reservation['status'] === 'rejected'): ?>
                                                    <span class="status-badge badge-rejected">
                                                        <i class="fas fa-times-circle badge-icon"></i> Rejected
                                                    </span>
                                                    <span class="table-cell-secondary" style="font-size: 11px;">
                                                        Contact support
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($needsPayment): ?>
                                                <a href="upload-payment.php?reservation_id=<?= $reservation['reservation_id'] ?>" 
                                                   class="btn btn-primary btn-sm"
                                                   title="Upload Payment Proof"
                                                   style="min-width: 100px;">
                                                    <i class="fas fa-upload mr-1"></i> Pay Now
                                                </a>
                                                <?php endif; ?>
                                                
                                                <a href="reservation-details.php?id=<?= $reservation['reservation_id'] ?>" 
                                                   class="btn btn-secondary btn-sm"
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i> Details
                                                </a>
                                                
                                                <?php if ($reservation['status'] === 'approved' && $isUpcoming): ?>
                                                <a href="cottage-details.php?id=<?= $reservation['cottage_id'] ?>" 
                                                   class="btn btn-success btn-sm"
                                                   title="View Cottage Details">
                                                    <i class="fas fa-home"></i> Cottage
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
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

<!-- DataTables -->
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#reservationsTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[0, 'desc']],
        "pageLength": 10,
        "language": {
            "search": "Search reservations:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ reservations",
            "infoEmpty": "No reservations to show",
            "infoFiltered": "(filtered from _MAX_ total reservations)",
            "zeroRecords": "No matching reservations found",
            "paginate": {
                "previous": "Previous",
                "next": "Next"
            }
        },
        "drawCallback": function(settings) {
            // Update badge count
            var api = this.api();
            var total = api.data().count();
            $('.card-tools .badge').text(total + ' Reservations');
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 5000);
    
    // Quick action button handlers
    $('#pendingBtn').on('click', function(e) {
        e.preventDefault();
        var table = $('#reservationsTable').DataTable();
        table.search('pending').draw();
        $('html, body').animate({
            scrollTop: $('#reservationsTable').offset().top - 100
        }, 500);
    });
    
    $('#upcomingBtn').on('click', function(e) {
        e.preventDefault();
        var table = $('#reservationsTable').DataTable();
        table.search('approved').draw();
        $('html, body').animate({
            scrollTop: $('#reservationsTable').offset().top - 100
        }, 500);
    });
});
</script>
</body>
</html>