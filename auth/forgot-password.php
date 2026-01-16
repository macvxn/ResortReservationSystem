<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();
}

$error = '';
$success = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // PRESERVED BUSINESS LOGIC - DO NOT MODIFY
    $email = clean($_POST['email']);
    $email_value = $email;
    
    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id, email, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate secure reset token
            $token = bin2hex(random_bytes(32));
            $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour UTC'));
            
            // Save token to database
            $stmt = $pdo->prepare("
                UPDATE users 
                SET reset_token = ?, reset_token_expires = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$token, $token_expires, $user['user_id']]);
            
            // Send reset email
            $reset_result = sendPasswordResetEmail($email, $token);
            
            if ($reset_result['success']) {
                logAction($user['user_id'], 'password_reset_requested', 'users', $user['user_id']);
                
                // Store email in session for the check-email page
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_email_sent'] = true;
                
                // AJAX response
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Password reset instructions have been sent to your email.',
                        'redirect' => 'check-email.php'
                    ]);
                    exit();
                } else {
                    // Traditional redirect for non-JS users
                    header("Location: check-email.php");
                    exit();
                }
            } else {
                $error = "Failed to send reset email. Please try again.";
                error_log("Password reset email failed: " . $reset_result['message']);
            }
        } else {
            // Don't reveal if email exists (security best practice)
            // Store dummy email in session
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_email_sent'] = true;
            
            // AJAX response
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'If that email exists in our system, password reset instructions have been sent.',
                    'redirect' => 'check-email.php'
                ]);
                exit();
            } else {
                // Traditional redirect for non-JS users
                header("Location: check-email.php");
                exit();
            }
        }
    }
    
    // AJAX response for errors
    if ($error && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error,
            'errors' => ['email' => $error]
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Aura Luxe Resort</title>
    
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
        
        .forgot-page {
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
        
        .forgot-box {
            width: 100%;
            max-width: 450px;
        }
        
        .forgot-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .forgot-logo a {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .forgot-logo a i {
            color: var(--accent-yellow);
        }
        
        .forgot-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .forgot-card-header {
            background: var(--primary-turquoise);
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-bottom: none;
        }
        
        .forgot-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .forgot-card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .forgot-card-body {
            padding: 30px;
        }
        
        .input-group .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            color: var(--primary-turquoise);
            min-width: 45px;
            justify-content: center;
        }
        
        .form-control {
            border-left: none;
            padding-left: 0;
            height: 45px;
        }
        
        .form-control:focus {
            border-color: #ced4da;
            box-shadow: 0 0 0 0.2rem rgba(64, 224, 208, 0.25);
        }
        
        .form-control:focus + .input-group-text {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(64, 224, 208, 0.25);
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-yellow);
        }
        
        .info-box h6 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .info-box ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .info-box li {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .btn-forgot-custom {
            background: var(--primary-turquoise);
            border-color: var(--primary-turquoise);
            color: white;
            font-weight: 600;
            padding: 12px;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-forgot-custom:hover {
            background: var(--primary-turquoise-dark);
            border-color: var(--primary-turquoise-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .forgot-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .forgot-links a {
            color: var(--primary-turquoise);
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-links a:hover {
            color: var(--primary-turquoise-dark);
            text-decoration: underline;
        }
        
        /* Alert styling */
        .alert-custom {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(64, 224, 208, 0.1);
            border-left: 4px solid var(--primary-turquoise);
            color: #155724;
        }
        
        .alert-danger {
            background-color: rgba(255, 111, 97, 0.1);
            border-left: 4px solid var(--accent-coral);
            color: #721c24;
        }
        
        /* Loading animation */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        
        .btn-loading:after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="hold-transition forgot-page">
    <div class="forgot-box">
        <!-- Forgot Logo -->
        <div class="forgot-logo">
            <a href="../index.php">
                <i class="fas fa-umbrella-beach"></i>
                <span>Aura Luxe Resort</span>
            </a>
        </div>
        
        <!-- Forgot Card -->
        <div class="card forgot-card">
            <div class="card-header forgot-card-header">
                <h1><i class="fas fa-key mr-2"></i>Forgot Password</h1>
                <p>Enter your email to reset your password</p>
            </div>
            
            <div class="card-body forgot-card-body">
                <!-- Error Messages -->
                <?php if ($error && !isset($_POST['ajax'])): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Info Box -->
                <div class="info-box">
                    <h6><i class="fas fa-info-circle mr-2"></i>How it works:</h6>
                    <ul>
                        <li>Enter your registered email address</li>
                        <li>We'll send you a secure reset link</li>
                        <li>Click the link to create a new password</li>
                        <li>The link expires in 1 hour</li>
                    </ul>
                </div>
                
                <!-- Forgot Form -->
                <form method="POST" class="ajax-form" id="forgotForm">
                    <div class="form-group">
                        <label for="email" style="font-weight: 600; color: var(--text-dark);">Email Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                            <input 
                                type="email" 
                                name="email" 
                                id="email"
                                class="form-control" 
                                placeholder="your@email.com"
                                value="<?php echo htmlspecialchars($email_value); ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-forgot-custom btn-block">
                            <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
                        </button>
                    </div>
                </form>
                
                <!-- Fallback for non-JS users -->
                <noscript>
                    <div class="alert alert-info alert-custom mt-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        JavaScript is disabled. Form will submit normally.
                    </div>
                </noscript>
                
                <!-- Forgot Links -->
                <div class="forgot-links">
                    <p class="mb-2">Remember your password?</p>
                    <a href="login.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sign-in-alt mr-1"></i>Back to Login
                    </a>
                    <p class="mt-3 mb-0">
                        Don't have an account? 
                        <a href="register.php">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Required Scripts -->
    <script src="../adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../adminlte/dist/js/adminlte.min.js"></script>
    
    <!-- AJAX Handler -->
    <script src="../assets/js/ajax-handler.js"></script>
    
    <!-- Custom Forgot Password Script -->
    <script>
        $(document).ready(function() {
            // Custom form submission handler
            $('#forgotForm').on('submit', function(e) {
                var form = $(this);
                var submitBtn = form.find('[type="submit"]');
                var originalText = submitBtn.html();
                
                // Add loading state
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Sending...');
                submitBtn.addClass('btn-loading');
                
                // Clear previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();
                
                // Check for AJAX handler
                if (typeof window.AjaxFormHandler !== 'undefined' || typeof window.ajaxHandler !== 'undefined') {
                    // Let the main handler do its work
                    return true;
                }
                
                // Fallback AJAX handling if main handler fails
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('ajax', 'true');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-RequestedWith': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showAlert(data.message, 'success');
                        
                        // Redirect to check-email page
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        // Show error
                        showAlert(data.message, 'danger');
                        
                        // Show field errors if any
                        if (data.errors) {
                            for (var field in data.errors) {
                                var input = form.find('[name="' + field + '"]');
                                if (input.length) {
                                    input.addClass('is-invalid');
                                    input.after('<div class="invalid-feedback">' + data.errors[field] + '</div>');
                                }
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                    // Fallback to normal form submission
                    form.off('submit').submit();
                })
                .finally(() => {
                    // Restore button
                    submitBtn.prop('disabled', false);
                    submitBtn.removeClass('btn-loading');
                    submitBtn.html(originalText);
                });
            });
            
            // Alert display function
            function showAlert(message, type) {
                // Remove existing alerts
                $('.ajax-alert').remove();
                
                var alertClass = 'alert-' + type;
                if (type === 'success') {
                    alertClass = 'alert-success';
                } else if (type === 'danger') {
                    alertClass = 'alert-danger';
                }
                
                var alertDiv = $('<div class="ajax-alert alert ' + alertClass + ' alert-dismissible fade show"></div>')
                    .css({
                        'position': 'fixed',
                        'top': '20px',
                        'right': '20px',
                        'z-index': '9999',
                        'min-width': '300px',
                        'box-shadow': '0 4px 12px rgba(0,0,0,0.15)'
                    })
                    .html(
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong>' + message + '</strong>'
                    );
                
                $('body').append(alertDiv);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    alertDiv.alert('close');
                }, 5000);
            }
            
            // Add smooth focus effect
            $('.form-control').on('focus', function() {
                $(this).parent().find('.input-group-text').css({
                    'border-color': '#80bdff',
                    'box-shadow': '0 0 0 0.2rem rgba(64, 224, 208, 0.25)'
                });
            }).on('blur', function() {
                $(this).parent().find('.input-group-text').css({
                    'border-color': '',
                    'box-shadow': ''
                });
            });
        });
    </script>
</body>
</html>