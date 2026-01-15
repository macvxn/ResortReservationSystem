<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Check if user is logged in
requireLogin();

// Get user profile
$profile = getUserProfile($_SESSION['user_id']);

// If profile doesn't exist, create one
if (!$profile) {
    $stmt = $pdo->prepare("
        INSERT INTO user_profiles (user_id, full_name, verification_status)
        VALUES (?, '', 'unverified')
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Fetch again
    $profile = getUserProfile($_SESSION['user_id']);
}

// Count user's reservations
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reservations,
        SUM(CASE WHEN status = 'pending_admin_review' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reservations 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get upcoming approved reservations
$stmt = $pdo->prepare("
    SELECT r.*, c.cottage_name, c.price_per_night
    FROM reservations r
    JOIN cottages c ON r.cottage_id = c.cottage_id
    WHERE r.user_id = ? 
    AND r.status = 'approved'
    AND r.check_in_date >= CURDATE()
    ORDER BY r.check_in_date ASC
    LIMIT 3
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming = $stmt->fetchAll();

// Set AdminLTE page variables
$page_title = 'Dashboard - Aura Luxe Resort';
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
            background-color: #28a745 !important; /* Keep original green for success */
        }
        
        /* Card styling with resort theme */
        .card {
            border: 1px solid rgba(64, 224, 208, 0.2);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            border-radius: 10px 10px 0 0 !important;
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            background-color: var(--background-cream);
        }
        
        /* Stats cards */
        .info-box {
            background: white;
            border-radius: 8px;
            border: 1px solid rgba(64, 224, 208, 0.3);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }
        
        .info-box-icon {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
        }
        
        .info-box-number {
            color: var(--primary-turquoise);
            font-weight: 700;
            font-size: 24px;
        }
        
        /* Alert customization */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 8px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left-color: #28a745;
        }
        
        .alert-info {
            background-color: rgba(0, 191, 255, 0.1);
            border-left-color: var(--secondary-aqua);
        }
        
        .alert-warning {
            background-color: rgba(255, 211, 0, 0.1);
            border-left-color: var(--accent-yellow);
        }
        
        .alert-danger {
            background-color: rgba(252, 108, 133, 0.1);
            border-left-color: var(--accent-watermelon);
        }
        
        /* Button customization */
        .btn {
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary-turquoise);
            border: 2px solid var(--primary-turquoise);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary-turquoise);
            color: white;
        }
        
        /* Main content background */
        .content-wrapper {
            background-color: #f8f9fa;
        }
        
        /* Reservation cards */
        .reservation-item {
            background: white;
            border: 1px solid rgba(64, 224, 208, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .reservation-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-color: var(--primary-turquoise);
        }
        
        .reservation-item h5 {
            color: var(--primary-turquoise);
            margin-bottom: 10px;
        }
        
        /* Quick actions */
        .quick-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: white;
            border: 2px solid rgba(64, 224, 208, 0.2);
            border-radius: 10px;
            color: var(--primary-turquoise);
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            text-align: center;
            height: 100%;
        }
        
        .quick-action-btn:hover {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            text-decoration: none;
            border-color: var(--primary-turquoise);
            transform: translateY(-2px);
        }
        
        .quick-action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
        
        /* Welcome header */
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .info-box {
                margin-bottom: 10px;
            }
            
            .quick-action-btn {
                margin-bottom: 10px;
            }
            
            .reservation-item {
                padding: 10px;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
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
                                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                            </h1>
                            <p class="mb-0 mt-2" style="opacity: 0.9;">
                                Welcome back, <?php echo htmlspecialchars($profile['email']); ?>!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Flash Messages -->
                <?php if (isset($_GET['auto_verified'])): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Automatically Verified!</h5>
                        Your identity has been verified automatically by our AI system with 
                        <strong><?php echo htmlspecialchars($_GET['confidence']); ?>% confidence</strong>.
                        You can now browse cottages and make reservations!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['manual_review'])): ?>
                    <div class="alert alert-info alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-info-circle"></i> Manual Review Required</h5>
                        Your ID has been submitted for manual verification by our admin team.
                        <?php if (isset($_GET['confidence'])): ?>
                            <br>AI Confidence Score: <strong><?php echo htmlspecialchars($_GET['confidence']); ?>%</strong>
                        <?php endif; ?>
                        <br>You'll be notified once your verification is complete.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['id_uploaded'])): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> ID Uploaded Successfully</h5>
                        Your ID is being processed...
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['verified'])): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Email Verified!</h5>
                        Email verified successfully! Welcome to Aura Luxe Resort.
                    </div>
                <?php endif; ?>

                <!-- Verification Status -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Account Status</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($profile['verification_status'] == 'unverified'): ?>
                                    <div class="alert alert-warning">
                                        <h5><i class="icon fas fa-exclamation-triangle"></i> Account Not Verified</h5>
                                        <p>Please complete your profile and upload your government ID to start making reservations.</p>
                                        <a href="profile.php" class="btn btn-primary">
                                            <i class="fas fa-user-edit mr-1"></i> Complete Profile Now
                                        </a>
                                    </div>
                                <?php elseif ($profile['verification_status'] == 'pending_verification'): ?>
                                    <div class="alert alert-info">
                                        <h5><i class="icon fas fa-clock"></i> Verification Pending</h5>
                                        <p>Your ID is under review. You'll be notified once verified.</p>
                                    </div>
                                <?php elseif ($profile['verification_status'] == 'verified'): ?>
                                    <div class="alert alert-success">
                                        <h5><i class="icon fas fa-check-circle"></i> Account Verified</h5>
                                        <p>You can now browse cottages and make reservations!</p>
                                        <a href="cottages.php" class="btn btn-primary">
                                            <i class="fas fa-home mr-1"></i> Browse Cottages
                                        </a>
                                    </div>
                                <?php elseif ($profile['verification_status'] == 'rejected'): ?>
                                    <div class="alert alert-danger">
                                        <h5><i class="icon fas fa-times-circle"></i> Verification Rejected</h5>
                                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($profile['admin_remarks'] ?? 'No reason provided'); ?></p>
                                        <a href="upload-id.php" class="btn btn-danger">
                                            <i class="fas fa-upload mr-1"></i> Re-upload ID
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Reservation Statistics</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-primary">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Reservations</span>
                                                <span class="info-box-number"><?php echo $stats['total_reservations'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning">
                                                <i class="fas fa-clock"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Pending</span>
                                                <span class="info-box-number"><?php echo $stats['pending'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Approved</span>
                                                <span class="info-box-number"><?php echo $stats['approved'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger">
                                                <i class="fas fa-times-circle"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Rejected</span>
                                                <span class="info-box-number"><?php echo $stats['rejected'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Reservations & Quick Actions -->
                <div class="row">
                    <!-- Upcoming Reservations -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Upcoming Reservations</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($upcoming)): ?>
                                    <?php foreach ($upcoming as $res): ?>
                                        <div class="reservation-item">
                                            <h5>
                                                <i class="fas fa-home mr-2" style="color: var(--accent-coral);"></i>
                                                <?php echo htmlspecialchars($res['cottage_name']); ?>
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1">
                                                        <i class="fas fa-calendar-check mr-2" style="color: var(--primary-turquoise);"></i>
                                                        <strong>Check-in:</strong> <?php echo formatDate($res['check_in_date']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <i class="fas fa-calendar-times mr-2" style="color: var(--primary-turquoise);"></i>
                                                        <strong>Check-out:</strong> <?php echo formatDate($res['check_out_date']); ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1">
                                                        <i class="fas fa-moon mr-2" style="color: var(--accent-yellow);"></i>
                                                        <strong>Nights:</strong> <?php echo $res['total_nights']; ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <i class="fas fa-money-bill-wave mr-2" style="color: #28a745;"></i>
                                                        <strong>Total:</strong> ₱<?php echo number_format($res['total_price'], 2); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x mb-3" style="color: var(--primary-turquoise); opacity: 0.5;"></i>
                                        <h5 class="text-muted">No upcoming reservations</h5>
                                        <?php if ($profile['verification_status'] == 'verified'): ?>
                                            <a href="cottages.php" class="btn btn-primary mt-2">
                                                <i class="fas fa-home mr-1"></i> Browse Cottages
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <a href="profile.php" class="quick-action-btn">
                                            <i class="fas fa-user-edit"></i>
                                            Update Profile
                                        </a>
                                    </div>
                                    
                                    <?php if ($profile['verification_status'] == 'verified'): ?>
                                        <div class="col-12">
                                            <a href="cottages.php" class="quick-action-btn">
                                                <i class="fas fa-home"></i>
                                                Browse Cottages
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-12">
                                        <a href="my-reservations.php" class="quick-action-btn">
                                            <i class="fas fa-list-alt"></i>
                                            View All Reservations
                                        </a>
                                    </div>
                                    
                                    <div class="col-12">
                                        <a href="../auth/logout.php" class="quick-action-btn" style="border-color: var(--accent-watermelon); color: var(--accent-watermelon);">
                                            <i class="fas fa-sign-out-alt"></i>
                                            Logout
                                        </a>
                                    </div>
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
            Aura Luxe Resort
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
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert:not(.alert-permanent)').fadeTo(500, 0).slideUp(500, function(){
                $(this).remove(); 
            });
        }, 5000);
        
        // Add active class to current nav item
        var currentPage = '<?php echo $current_page; ?>';
        $('.nav-item a[href="' + currentPage + '"]').parent().addClass('active');
    });
</script>
</body>
</html>