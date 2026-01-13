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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Resort Reservation</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .nav-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            color: white;
        }
        .nav-bar h2 {
            color: white;
            margin: 0;
        }
        .nav-links {
            display: flex;
            gap: 10px;
            margin-top: 10px;
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
        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #eee;
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .status-pending { color: #ffc107; }
        .status-approved { color: #28a745; }
        .status-rejected { color: #dc3545; }
        .verification-alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .verification-alert.success {
            background: #d4edda;
            border-color: #28a745;
        }
        .verification-alert.danger {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .reservation-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .reservation-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .reservation-detail {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <h2>🏖️ My Dashboard</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">Profile</a>
                <a href="cottages.php">Browse Cottages</a>
                <a href="my-reservations.php">My Reservations</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>

<?php if (isset($_GET['auto_verified'])): ?>
    <div class="alert alert-success" style="border-left: 4px solid #28a745;">
        <strong>🎉 Automatically Verified!</strong>
        <p>
            Your identity has been verified automatically by our AI system with 
            <strong><?php echo htmlspecialchars($_GET['confidence']); ?>% confidence</strong>.
        </p>
        <p style="margin: 10px 0 0 0;">
            You can now browse cottages and make reservations!
        </p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['manual_review'])): ?>
    <div class="alert alert-info" style="border-left: 4px solid #17a2b8;">
        <strong>🔍 Manual Review Required</strong>
        <p>
            Your ID has been submitted for manual verification by our admin team.
            <?php if (isset($_GET['confidence'])): ?>
                <br>AI Confidence Score: <strong><?php echo htmlspecialchars($_GET['confidence']); ?>%</strong>
            <?php endif; ?>
        </p>
        <p style="margin: 10px 0 0 0;">
            You'll be notified once your verification is complete. This usually takes 24-48 hours.
        </p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['id_uploaded'])): ?>
    <div class="alert alert-success">
        <strong>✓ ID Uploaded Successfully</strong>
        <p>Your ID is being processed...</p>
    </div>
<?php endif; ?>

        <?php if (isset($_GET['verified'])): ?>
            <div class="alert alert-success">
                ✓ Email verified successfully! Welcome to Resort Reservation System.
            </div>
        <?php endif; ?>

        <h3>Welcome, <?php echo htmlspecialchars($profile['email']); ?>!</h3>

        <!-- Verification Status Alert -->
        <?php if ($profile['verification_status'] == 'unverified'): ?>
            <div class="verification-alert">
                <strong>⚠️ Account Not Verified</strong>
                <p>Please complete your profile and upload your government ID to start making reservations.</p>
                <a href="profile.php" class="btn" style="width: auto; display: inline-block; margin-top: 10px;">
                    Complete Profile Now
                </a>
            </div>
        <?php elseif ($profile['verification_status'] == 'pending_verification'): ?>
            <div class="verification-alert">
                <strong>🕐 Verification Pending</strong>
                <p>Your ID is under review. You'll be notified once verified.</p>
            </div>
        <?php elseif ($profile['verification_status'] == 'verified'): ?>
            <div class="verification-alert success">
                <strong>✓ Account Verified</strong>
                <p>You can now browse cottages and make reservations!</p>
                <a href="cottages.php" class="btn btn-success" style="width: auto; display: inline-block; margin-top: 10px;">
                    Browse Cottages
                </a>
            </div>
        <?php elseif ($profile['verification_status'] == 'rejected'): ?>
            <div class="verification-alert danger">
                <strong>✗ Verification Rejected</strong>
                <p><strong>Reason:</strong> <?php echo htmlspecialchars($profile['admin_remarks'] ?? 'No reason provided'); ?></p>
                <a href="upload-id.php" class="btn btn-danger" style="width: auto; display: inline-block; margin-top: 10px;">
                    Re-upload ID
                </a>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <h3>Reservation Statistics</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_reservations'] ?? 0; ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number status-pending"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number status-approved"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number status-rejected"><?php echo $stats['rejected'] ?? 0; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- Upcoming Reservations -->
        <?php if (!empty($upcoming)): ?>
            <h3>Upcoming Reservations</h3>
            <?php foreach ($upcoming as $res): ?>
                <div class="reservation-card">
                    <h4>🏠 <?php echo htmlspecialchars($res['cottage_name']); ?></h4>
                    <div class="reservation-detail">
                        <span>Check-in:</span>
                        <strong><?php echo formatDate($res['check_in_date']); ?></strong>
                    </div>
                    <div class="reservation-detail">
                        <span>Check-out:</span>
                        <strong><?php echo formatDate($res['check_out_date']); ?></strong>
                    </div>
                    <div class="reservation-detail">
                        <span>Nights:</span>
                        <strong><?php echo $res['total_nights']; ?></strong>
                    </div>
                    <div class="reservation-detail">
                        <span>Total:</span>
                        <strong>₱<?php echo number_format($res['total_price'], 2); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <p style="text-align: center; color: #666;">No upcoming reservations</p>
                <?php if ($profile['verification_status'] == 'verified'): ?>
                    <a href="cottages.php" class="btn">Browse Cottages</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <h3>Quick Actions</h3>
        <div style="display: grid; gap: 10px;">
            <a href="profile.php" class="btn btn-secondary">
                📝 Update Profile
            </a>
            
            <?php if ($profile['verification_status'] == 'verified'): ?>
                <a href="cottages.php" class="btn">
                    🏠 Browse Cottages
                </a>
            <?php endif; ?>
            
            <a href="my-reservations.php" class="btn btn-secondary">
                📋 View All Reservations
            </a>
        </div>
    </div>
</body>
</html>