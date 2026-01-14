<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Get stats
$pending_verifications = getPendingVerificationsCount();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM reservations 
    WHERE status = 'pending_admin_review'
");
$stmt->execute();
$pending_reservations = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stmt->execute();
$total_users = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cottages WHERE is_active = 1");
$stmt->execute();
$active_cottages = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            color: white;
        }
        .admin-nav h2 { color: white; margin: 0 0 10px 0; }
        .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #eee;
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        .quick-action {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-nav">
            <h2>👨‍💼 Admin Dashboard</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="verify-users.php">Verify Users</a>
                <a href="reservations.php">Reservations</a>
                <a href="cottages.php">Manage Cottages</a>
                <a href="reports.php">reports</a>
                <a href="audit-logs.php">logs</a>
                <a href="users.php">user management</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <h3>System Overview</h3>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_verifications; ?></div>
                <div class="stat-label">Pending Verifications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_reservations; ?></div>
                <div class="stat-label">Pending Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_cottages; ?></div>
                <div class="stat-label">Active Cottages</div>
            </div>
        </div>

        <h3>Quick Actions</h3>
        
        <?php if ($pending_verifications > 0): ?>
            <div class="quick-action">
                <strong>🔍 <?php echo $pending_verifications; ?> User(s) Awaiting Verification</strong>
                <p style="margin: 5px 0; color: #666;">Review and approve user ID submissions</p>
                <a href="verify-users.php" class="btn" style="width: auto; display: inline-block; margin-top: 10px;">
                    Review Now
                </a>
            </div>
        <?php endif; ?>

        <?php if ($pending_reservations > 0): ?>
            <div class="quick-action">
                <strong>📋 <?php echo $pending_reservations; ?> Reservation(s) Pending Review</strong>
                <p style="margin: 5px 0; color: #666;">Approve or reject reservation requests</p>
                <a href="reservations.php" class="btn" style="width: auto; display: inline-block; margin-top: 10px;">
                    Review Now
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>