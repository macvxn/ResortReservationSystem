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

$error = '';
$success = '';
$token = isset($_GET['token']) ? clean($_GET['token']) : '';
$token_valid = false;
$user_data = null;

// Validate token
if (empty($token)) {
    $error = "Invalid reset link. Please request a new password reset.";
} else {
    // Check if token exists and is not expired
    $stmt = $pdo->prepare("
        SELECT user_id, email, reset_token_expires 
        FROM users 
        WHERE reset_token = ? AND reset_token_expires > UTC_TIMESTAMP()
    ");
    $stmt->execute([$token]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        $error = "This reset link has expired or is invalid. Please request a new password reset.";
    } else {
        $token_valid = true;
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid) {
    // PRESERVED BUSINESS LOGIC - DO NOT MODIFY
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password and clear reset token
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, 
                reset_token = NULL, 
                reset_token_expires = NULL,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        
        if ($stmt->execute([$password_hash, $user_data['user_id']])) {
            logAction($user_data['user_id'], 'password_reset_completed', 'users', $user_data['user_id']);
            
            // AJAX response
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Password reset successfully! Redirecting to login...',
                    'redirect' => 'login.php?password_reset=1'
                ]);
                exit();
            } else {
                // Traditional redirect for non-JS users
                header("Location: login.php?password_reset=1");
                exit();
            }
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
    
    // AJAX response for errors
    if ($error && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        $errors = [];
        if ($error == "Password must be at least 6 characters long") {
            $errors['new_password'] = $error;
        } elseif ($error == "Passwords do not match") {
            $errors['confirm_password'] = $error;
        } else {
            $errors['general'] = $error;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error,
            'errors' => $errors
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
    <title>Reset Password - Aura Luxe Resort</title>
    
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
        
        .reset-page {
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
        
        .reset-box {
            width: 100%;
            max-width: 450px;
        }
        
        .reset-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reset-logo a {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .reset-logo a i {
            color: var(--accent-yellow);
        }
        
        .reset-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .reset-card-header {
            background: var(--primary-turquoise);
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-bottom: none;
        }
        
        .reset-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .reset-card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .reset-card-body {
            padding: 30px;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-yellow);
        }
        
        .password-requirements h6 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .password-requirements li {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .password-requirements li.valid {
            color: #28a745 !important;
            font-weight: 500;
        }
        
        .password-requirements li.valid:before {
            content: "✓ ";
            font-weight: bold;
        }
        
        .password-requirements li.invalid {
            color: #dc3545 !important;
        }
        
        .password-requirements li.invalid:before {
            content: "✗ ";
            font-weight: bold;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .strength-weak {
            background-color: #dc3545 !important;
        }
        
        .strength-fair {
            background-color: #ffc107 !important;
        }
        
        .strength-good {
            background-color: #28a745 !important;
        }
        
        .strength-strong {
            background-color: #20c997 !important;
        }
        
        /* Password toggle button */
        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            z-index: 10;
            padding: 5px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        
        .password-toggle-btn:hover {
            background-color: rgba(0,0,0,0.05);
            color: var(--primary-turquoise);
        }
        
        /* Fix input group for password fields */
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper .form-control {
            padding-right: 40px;
        }
        
        .btn-reset-custom {
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
        
        .btn-reset-custom:hover {
            background: var(--primary-turquoise-dark);
            border-color: var(--primary-turquoise-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .btn-reset-custom:disabled {
            background: #cccccc;
            border-color: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .reset-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .reset-links a {
            color: var(--primary-turquoise);
            text-decoration: none;
            font-weight: 500;
        }
        
        .reset-links a:hover {
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
<body class="hold-transition reset-page">
    <div class="reset-box">
        <!-- Reset Logo -->
        <div class="reset-logo">
            <a href="../index.php">
                <i class="fas fa-umbrella-beach"></i>
                <span>Aura Luxe Resort</span>
            </a>
        </div>
        
        <!-- Reset Card -->
        <div class="card reset-card">
            <div class="card-header reset-card-header">
                <h1><i class="fas fa-key mr-2"></i>Create New Password</h1>
                <?php if ($token_valid): ?>
                    <p>Enter your new password below</p>
                <?php endif; ?>
            </div>
            
            <div class="card-body reset-card-body">
                <!-- Error Messages -->
                <?php if ($error && !isset($_POST['ajax'])): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <?php if (!$token_valid): ?>
                            <p class="mt-2 mb-0">
                                <a href="forgot-password.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-redo mr-1"></i>Request a new reset link
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($token_valid): ?>
                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <h6><i class="fas fa-shield-alt mr-2"></i>Password Requirements</h6>
                        <ul>
                            <li id="req-length" class="invalid">At least 6 characters</li>
                            <li id="req-match" class="invalid">Passwords must match</li>
                        </ul>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                    </div>
                    
                    <!-- Reset Form -->
                    <form method="POST" class="ajax-form" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <!-- New Password Field -->
                        <div class="form-group">
                            <label for="new_password" style="font-weight: 600; color: var(--text-dark);">New Password</label>
                            <div class="password-wrapper">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    </div>
                                    <input 
                                        type="password" 
                                        name="new_password" 
                                        id="new_password"
                                        class="form-control" 
                                        placeholder="Enter new password"
                                        required
                                        minlength="6"
                                        autofocus
                                    >
                                </div>
                                <button type="button" class="password-toggle-btn" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Confirm Password Field -->
                        <div class="form-group">
                            <label for="confirm_password" style="font-weight: 600; color: var(--text-dark);">Confirm Password</label>
                            <div class="password-wrapper">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    </div>
                                    <input 
                                        type="password" 
                                        name="confirm_password" 
                                        id="confirm_password"
                                        class="form-control" 
                                        placeholder="Re-enter new password"
                                        required
                                        minlength="6"
                                    >
                                </div>
                                <button type="button" class="password-toggle-btn" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-reset-custom btn-block" id="submitBtn" disabled>
                                <i class="fas fa-save mr-2"></i>Reset Password
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
                <?php endif; ?>
                
                <!-- Reset Links -->
                <div class="reset-links">
                    <p class="mb-2">Remember your password?</p>
                    <a href="login.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sign-in-alt mr-1"></i>Back to Login
                    </a>
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
    
    <!-- Custom Reset Password Script -->
    <script>
        $(document).ready(function() {
            <?php if (!$token_valid): ?>
                // If token is invalid, show error and disable form
                $('#resetForm').find('input, button').prop('disabled', true);
            <?php endif; ?>
            
            // Password toggle functionality
            $('#togglePassword').click(function(e) {
                e.preventDefault();
                const passwordInput = $('#new_password');
                const icon = $(this).find('i');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                icon.toggleClass('fa-eye fa-eye-slash');
            });
            
            $('#toggleConfirmPassword').click(function(e) {
                e.preventDefault();
                const confirmInput = $('#confirm_password');
                const icon = $(this).find('i');
                const type = confirmInput.attr('type') === 'password' ? 'text' : 'password';
                confirmInput.attr('type', type);
                icon.toggleClass('fa-eye fa-eye-slash');
            });
            
            // Password strength checker
            $('#new_password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#password-strength-bar');
                const reqLength = $('#req-length');
                const submitBtn = $('#submitBtn');
                
                // Reset strength bar
                strengthBar.removeClass('strength-weak strength-fair strength-good strength-strong');
                
                // Check length requirement
                if (password.length >= 6) {
                    reqLength.removeClass('invalid').addClass('valid');
                } else {
                    reqLength.removeClass('valid').addClass('invalid');
                }
                
                // Calculate strength
                let strength = 0;
                
                // Length check
                if (password.length >= 6) strength += 25;
                if (password.length >= 8) strength += 10;
                
                // Complexity checks
                if (/[A-Z]/.test(password)) strength += 20;
                if (/[a-z]/.test(password)) strength += 15;
                if (/[0-9]/.test(password)) strength += 20;
                if (/[^A-Za-z0-9]/.test(password)) strength += 10;
                
                // Cap at 100
                strength = Math.min(strength, 100);
                
                // Apply strength class and width
                strengthBar.css('width', strength + '%');
                
                if (strength >= 75) {
                    strengthBar.addClass('strength-strong');
                } else if (strength >= 50) {
                    strengthBar.addClass('strength-good');
                } else if (strength >= 25) {
                    strengthBar.addClass('strength-fair');
                } else if (password.length > 0) {
                    strengthBar.addClass('strength-weak');
                }
                
                // Enable/disable submit button based on password requirements
                checkPasswordRequirements();
            });
            
            // Password match checker
            function checkPasswordMatch() {
                const password = $('#new_password').val();
                const confirm = $('#confirm_password').val();
                const reqMatch = $('#req-match');
                
                if (password && confirm) {
                    if (password === confirm) {
                        reqMatch.removeClass('invalid').addClass('valid');
                    } else {
                        reqMatch.removeClass('valid').addClass('invalid');
                    }
                } else {
                    reqMatch.removeClass('valid').addClass('invalid');
                }
                
                checkPasswordRequirements();
            }
            
            $('#new_password, #confirm_password').on('input', checkPasswordMatch);
            
            // Check all password requirements
            function checkPasswordRequirements() {
                const password = $('#new_password').val();
                const confirm = $('#confirm_password').val();
                const submitBtn = $('#submitBtn');
                
                // Check all requirements
                const lengthValid = password.length >= 6;
                const matchValid = password && confirm && password === confirm;
                
                // Enable/disable submit button
                if (lengthValid && matchValid) {
                    submitBtn.prop('disabled', false);
                } else {
                    submitBtn.prop('disabled', true);
                }
            }
            
            // Custom form submission handler
            $('#resetForm').on('submit', function(e) {
                var form = $(this);
                var submitBtn = form.find('[type="submit"]');
                var originalText = submitBtn.html();
                
                // Check password requirements
                if ($('#new_password').val().length < 6) {
                    showAlert('Password must be at least 6 characters', 'danger');
                    e.preventDefault();
                    return false;
                }
                
                if ($('#new_password').val() !== $('#confirm_password').val()) {
                    showAlert('Passwords do not match', 'danger');
                    e.preventDefault();
                    return false;
                }
                
                // Add loading state
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Resetting...');
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
                        }, 2000);
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
            
            // Initialize password validation on page load
            $('#new_password').trigger('input');
            checkPasswordMatch();
        });
    </script>
</body>
</html>