<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Redirect if already logged in (based on role) - PRESERVED EXACTLY
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();
}

// Initialize variables
$error = '';
$email = '';
$show_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // PRESERVED BUSINESS LOGIC - DO NOT MODIFY
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Get user (can be admin or regular user)
    $stmt = $pdo->prepare("
        SELECT user_id, email, password_hash, role, is_email_verified 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Log login attempt
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (email, ip_address, is_successful, attempted_at)
        VALUES (?, ?, ?, NOW())
    ");
    
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // Check if email is verified (only for regular users, admins skip this)
        if ($user['role'] !== 'admin' && $user['is_email_verified'] == 0) {
            $stmt->execute([$email, $ip_address, 0]);
            $error = "Please verify your email before logging in.";
        } else {
            // Successful login
            $stmt->execute([$email, $ip_address, 1]);
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Log the action
            logAction($user['user_id'], 'login', 'users', $user['user_id']);
            
            // Check for AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                
                // AJAX response
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful! Redirecting...',
                    'redirect' => $user['role'] === 'admin' ? '../admin/dashboard.php' : '../user/dashboard.php'
                ]);
                exit();
            } else {
                // Traditional redirect for non-JS users
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../user/dashboard.php");
                }
                exit();
            }
        }
        
    } else {
        // Failed login
        $stmt->execute([$email, $ip_address, 0]);
        $error = "Invalid email or password";
        
        // Check for too many failed attempts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_count 
            FROM login_attempts 
            WHERE email = ? 
            AND is_successful = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        if ($result['failed_count'] >= 5) {
            $error = "Too many failed attempts. Please try again in 15 minutes.";
        }
        
        // AJAX response for failed login
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $error,
                'errors' => ['general' => $error]
            ]);
            exit();
        }
    }
}

// Check for success messages from GET parameters
if (isset($_GET['registered'])) {
    $show_success = true;
    $success_message = "Registration successful! Please check your email for verification.";
}

if (isset($_GET['verified'])) {
    $show_success = true;
    $success_message = "Email verified! You can now login.";
}

if (isset($_GET['logged_out'])) {
    $show_success = true;
    $success_message = "You have been logged out successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aura Luxe Resort</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.min.css">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-turquoise: #40E0D0;
            --primary-turquoise-dark: #36c9b9;
            --accent-coral: #FF6F61;
            --accent-coral-dark: #ff5a4d;
            --accent-yellow: #FFD700;
            --background-cream: #FFF5E1;
            --text-dark: #333333;
            --text-light: #666666;
        }
        
        .login-page {
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
        
        .login-box {
            width: 100%;
            max-width: 420px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo a {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .login-logo a i {
            color: var(--accent-yellow);
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .login-card-header {
            background: var(--primary-turquoise);
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-bottom: none;
        }
        
        .login-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .login-card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 10px;
        }
        
        .login-card-body {
            padding: 30px;
        }
        
        .input-group .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            color: var(--primary-turquoise);
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
        
        .btn-login-custom {
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
        
        .btn-login-custom:hover {
            background: var(--primary-turquoise-dark);
            border-color: var(--primary-turquoise-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .btn-login-custom:active {
            transform: translateY(0);
        }
        
        .login-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-links a {
            color: var(--primary-turquoise);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-links a:hover {
            color: var(--primary-turquoise-dark);
            text-decoration: underline;
        }
        
        .login-note {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: var(--text-light);
            text-align: center;
            border-left: 4px solid var(--accent-yellow);
        }
        
        .login-note strong {
            color: var(--text-dark);
        }
        
        /* Custom checkbox */
        .icheck-primary input:checked ~ label::before {
            background-color: var(--primary-turquoise);
            border-color: var(--primary-turquoise);
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
        
        .alert-info {
            background-color: rgba(255, 215, 0, 0.1);
            border-left: 4px solid var(--accent-yellow);
            color: #856404;
        }
        
        /* Loading animation */
        .btn-loading {
            position: relative;
            color: transparent !important;
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
        
        /* Forgot password link */
        .forgot-password {
            display: block;
            text-align: right;
            font-size: 0.9rem;
            margin-top: 5px;
            color: var(--accent-coral);
        }
        
        .forgot-password:hover {
            color: var(--accent-coral-dark);
            text-decoration: underline;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <!-- Login Logo -->
        <div class="login-logo">
            <a href="../index.php">
                <i class="fas fa-umbrella-beach"></i>
                <span>Aura Luxe Resort</span>
            </a>
        </div>
        
        <!-- Login Card -->
        <div class="card login-card">
            <div class="card-header login-card-header">
                <h1><i class="fas fa-sign-in-alt mr-2"></i>Welcome Back</h1>
                <p>Sign in to your account</p>
                <div class="role-badge">For both Guests & Administrators</div>
            </div>
            
            <div class="card-body login-card-body">
                <!-- Success Messages -->
                <?php if ($show_success): ?>
                    <div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Error Messages -->
                <?php if ($error && !isset($_POST['ajax'])): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['blocked'])): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-ban mr-2"></i>
                        <strong>Account Blocked</strong>
                        <p class="mb-0" style="font-size: 0.95rem; margin-top: 5px;">
                            Your account has been blocked by the administrator. Please contact support for more information.
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" class="ajax-form" id="loginForm">
                    <!-- Email Field -->
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                        </div>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control" 
                            placeholder="Email Address"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                            autofocus
                        >
                    </div>
                    
                    <!-- Password Field -->
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Password"
                            required
                        >
                    </div>
                    
                    <!-- Forgot Password Link -->
                    <div class="mb-3">
                        <a href="forgot-password.php" class="forgot-password">
                            <i class="fas fa-key mr-1"></i>Forgot your password?
                        </a>
                    </div>
                    
                    <!-- Remember Me Checkbox -->
                    <div class="row mb-3">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember" style="color: var(--text-light);">
                                    Remember me
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-login-custom btn-block">
                                <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Fallback for non-JS users -->
                <noscript>
                    <div class="alert alert-info alert-custom mt-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        JavaScript is disabled. Form will submit normally.
                    </div>
                </noscript>
                
                <!-- Login Links -->
                <div class="login-links">
                    <p class="mb-2">Don't have an account?</p>
                    <a href="register.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-plus mr-1"></i>Create New Account
                    </a>
                    <p class="mt-3 mb-0">
                        <a href="../index.php">
                            <i class="fas fa-home mr-1"></i>Back to Home
                        </a>
                    </p>
                </div>
                
                <!-- System Note -->
                <div class="login-note">
                    <p class="mb-0">
                        <strong>Note:</strong> System automatically detects user role.<br>
                        Regular users and administrators use the same login form.
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
    
    <!-- Custom Login Script -->
    <script>
        $(document).ready(function() {
            // Initialize iCheck for checkboxes
            $('input[type="checkbox"]').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%'
            });
            
            // Custom form submission handler
            $('#loginForm').on('submit', function(e) {
                var form = $(this);
                var submitBtn = form.find('[type="submit"]');
                var originalText = submitBtn.html();
                
                // Add loading state
                submitBtn.prop('disabled', true);
                submitBtn.addClass('btn-loading');
                
                // Clear previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();
                
                // Check for AJAX handler
                if (typeof window.AjaxFormHandler !== 'undefined') {
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
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and redirect
                        showAlert(data.message, 'success');
                        setTimeout(function() {
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