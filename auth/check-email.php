<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();
}

// Get email from session if available
$email = $_SESSION['reset_email'] ?? '';
$email_sent = isset($_SESSION['reset_email_sent']) ? true : false;

// Clear session data
unset($_SESSION['reset_email']);
unset($_SESSION['reset_email_sent']);

// If no email in session, redirect to forgot password
if (!$email_sent) {
    header("Location: forgot-password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Email - Aura Luxe Resort</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.min.css">
    
    <!-- Custom Styles -->
    <style>
        :root {
            /* Resort Color Palette */
            --primary-turquoise: #40E0D0;
            --primary-turquoise-dark: #36c9b9;
            --secondary-aqua: #00BFFF;
            --accent-coral: #FF6F61;
            --accent-coral-dark: #ff5a4d;
            --accent-yellow: #FFD700;
            --background-cream: #FFF5E1;
            --text-dark: #333333;
            --text-light: #666666;
        }
        
        .check-email-page {
            background: 
    linear-gradient(135deg, rgba(64, 224, 208, 0.85) 0%, rgba(0, 191, 255, 0.85) 100%),
    url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-blend-mode: overlay;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .check-email-box {
            width: 100%;
            max-width: 500px;
        }
        
        .check-email-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .check-email-logo a {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .check-email-logo a i {
            color: var(--accent-yellow);
        }
        
        .check-email-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .check-email-card-header {
            background: var(--primary-turquoise);
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-bottom: none;
        }
        
        .check-email-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .check-email-card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .check-email-card-body {
            padding: 40px 30px;
            text-align: center;
        }
        
        .email-icon {
            font-size: 4rem;
            color: var(--primary-turquoise);
            margin-bottom: 20px;
        }
        
        .email-display {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border-left: 4px solid var(--accent-yellow);
        }
        
        .email-display p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .email-display strong {
            color: var(--primary-turquoise);
            font-size: 1.1rem;
            display: block;
            margin-top: 5px;
        }
        
        .instructions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .instructions h6 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .instructions li {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .btn-check-email-custom {
            background: var(--primary-turquoise);
            border-color: var(--primary-turquoise);
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 10px 5px;
        }
        
        .btn-check-email-custom:hover {
            background: var(--primary-turquoise-dark);
            border-color: var(--primary-turquoise-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .btn-outline-custom {
            background: transparent;
            border-color: var(--primary-turquoise);
            color: var(--primary-turquoise);
            font-weight: 600;
            padding: 12px 25px;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 10px 5px;
        }
        
        .btn-outline-custom:hover {
            background: var(--primary-turquoise);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        /* Countdown timer */
        .countdown {
            font-size: 1.1rem;
            color: var(--text-dark);
            font-weight: 600;
            margin: 15px 0;
        }
        
        .countdown-icon {
            color: var(--primary-turquoise);
            margin-right: 8px;
        }
    </style>
</head>
<body class="hold-transition check-email-page">
    <div class="check-email-box">
        <!-- Check Email Logo -->
        <div class="check-email-logo">
            <a href="../index.php">
                <i class="fas fa-umbrella-beach"></i>
                <span>Aura Luxe Resort</span>
            </a>
        </div>
        
        <!-- Check Email Card -->
        <div class="card check-email-card">
            <div class="card-header check-email-card-header">
                <h1><i class="fas fa-envelope mr-2"></i>Check Your Email</h1>
                <p>Password reset instructions sent</p>
            </div>
            
            <div class="card-body check-email-card-body">
                <!-- Email Icon -->
                <div class="email-icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                
                <!-- Success Message -->
                <h3 class="mb-3" style="color: var(--text-dark);">Password Reset Email Sent!</h3>
                <p class="text-muted mb-4">
                    We've sent password reset instructions to your email address.
                </p>
                
                <!-- Email Display -->
                <?php if ($email): ?>
                    <div class="email-display">
                        <p>Email sent to:</p>
                        <strong><?php echo htmlspecialchars($email); ?></strong>
                    </div>
                <?php endif; ?>
                
                <!-- Instructions -->
                <div class="instructions">
                    <h6><i class="fas fa-info-circle"></i>What to do next:</h6>
                    <ul>
                        <li>Check your email inbox (and spam folder)</li>
                        <li>Click the password reset link in the email</li>
                        <li>The link will expire in <strong>1 hour</strong></li>
                        <li>Create a new secure password</li>
                        <li>You'll be redirected to login automatically</li>
                    </ul>
                </div>
                
                <!-- Countdown Timer -->
                <div class="countdown">
                    <i class="fas fa-clock countdown-icon"></i>
                    <span id="redirectTimer">Redirecting in 10 seconds...</span>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4">
                    <a href="login.php" class="btn btn-check-email-custom">
                        <i class="fas fa-sign-in-alt mr-2"></i>Back to Login
                    </a>
                    <a href="forgot-password.php" class="btn btn-outline-custom">
                        <i class="fas fa-redo mr-2"></i>Resend Email
                    </a>
                </div>
                
                <!-- Trouble Section -->
                <div class="mt-4 pt-3 border-top">
                    <p class="text-muted small mb-2">Didn't receive the email?</p>
                    <div class="small">
                        <a href="forgot-password.php" class="mr-3">
                            <i class="fas fa-redo mr-1"></i>Try again
                        </a>
                        <a href="register.php" class="mr-3">
                            <i class="fas fa-user-plus mr-1"></i>Create new account
                        </a>
                        <a href="../index.php">
                            <i class="fas fa-home mr-1"></i>Back to home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Required Scripts -->
    <script src="../adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../adminlte/dist/js/adminlte.min.js"></script>
    
    <!-- Auto-redirect Script -->
    <script>
        $(document).ready(function() {
            let timeLeft = 10; // seconds
            const timerEl = $('#redirectTimer');
            const timerInterval = setInterval(function() {
                timeLeft--;
                timerEl.text(`Redirecting in ${timeLeft} second${timeLeft !== 1 ? 's' : ''}...`);
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    window.location.href = 'login.php';
                }
            }, 1000);
            
            // Cancel redirect if user clicks any button
            $('a').on('click', function() {
                clearInterval(timerInterval);
                timerEl.text('Redirect cancelled');
            });
        });
    </script>
</body>
</html>