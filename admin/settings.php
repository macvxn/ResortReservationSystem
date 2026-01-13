<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// For now, we'll hardcode the threshold at 50%
// Later you can make this configurable in database

$current_threshold = 50;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="admin-nav">
            <h2>⚙️ System Settings</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="verify-users.php">Verify Users</a>
                <a href="settings.php">Settings</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <h3>AI Auto-Approval Settings</h3>

        <div class="card">
            <h4>Current Configuration</h4>
            <p><strong>Auto-Approval Threshold:</strong> <?php echo $current_threshold; ?>%</p>
            <p style="color: #666; font-size: 14px;">
                Users with AI confidence score ≥<?php echo $current_threshold; ?>% are automatically verified.
            </p>

            <div class="alert alert-info">
                <strong>How it works:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>AI analyzes uploaded ID and compares with user input</li>
                    <li>Confidence score is calculated (0-100%)</li>
                    <li>Score ≥50%: Auto-approved ✓</li>
                    <li>Score <50%: Manual review required 🔍</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>