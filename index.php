<?php
require_once 'config/session.php';

// Redirect if logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resort Cottage Reservation System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .hero h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin: 30px 0;
        }
        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #eee;
        }
        .feature-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>🏖️ Resort Cottage Reservation</h1>
            <p>Book your perfect getaway with ease</p>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <h3>✓ Secure Booking</h3>
                <p>Safe and verified reservation process</p>
            </div>
            <div class="feature-card">
                <h3>✓ Real-time Availability</h3>
                <p>Check cottage availability instantly</p>
            </div>
            <div class="feature-card">
                <h3>✓ Easy Payment</h3>
                <p>Upload payment proof securely</p>
            </div>
        </div>
        
        <a href="auth/login.php" class="btn">Login</a>
        <a href="auth/register.php" class="btn btn-secondary">Create Account</a>
        
        <p class="text-center mt-20" style="color: #666; font-size: 14px;">
            Single login portal for both users and administrators
        </p>
    </div>
</body>
</html>