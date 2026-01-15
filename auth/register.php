<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';

// Redirect if already logged in - ADDED FOR CONSISTENCY
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
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $email_value = $email; // Store for form repopulation
    
    // Validation - PRESERVED
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Valid email required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match";
    } elseif (emailExists($email)) {
        $error = "Email already registered";
    } else {
        // Generate OTP - PRESERVED
        $otp = generateOTP();
        $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user - PRESERVED
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, role, otp_code, otp_expires_at)
            VALUES (?, ?, 'user', ?, ?)
        ");
        
        if ($stmt->execute([$email, $password_hash, $otp, $otp_expires])) {
            
            // Send OTP email - PRESERVED
            $emailResult = sendOTPEmail($email, $email, $otp);
            
            if ($emailResult['success']) {
                $_SESSION['temp_email'] = $email;
                
                // Check for AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    
                    // AJAX response
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Registration successful! Please check your email for verification code.',
                        'redirect' => 'verify-otp.php?sent=1'
                    ]);
                    exit();
                } else {
                    // Traditional redirect for non-JS users
                    header("Location: verify-otp.php?sent=1");
                    exit();
                }
            } else {
                $error = "Account created but failed to send email: " . $emailResult['message'];
            }
        } else {
            $error = "Registration failed";
        }
    }
    
    // AJAX response for errors
    if ($error && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        $errors = [];
        if ($error == "Valid email required") {
            $errors['email'] = "Valid email required";
        } elseif ($error == "Password must be at least 6 characters") {
            $errors['password'] = "Password must be at least 6 characters";
        } elseif ($error == "Passwords do not match") {
            $errors['confirm_password'] = "Passwords do not match";
        } elseif ($error == "Email already registered") {
            $errors['email'] = "Email already registered";
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
    <title>Register - Aura Luxe Resort</title>
    
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
        
        .register-page {
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
        
        .register-box {
            width: 100%;
            max-width: 480px;
        }
        
        .register-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-logo a {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .register-logo a i {
            color: var(--accent-yellow);
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .register-card-header {
            background: var(--primary-turquoise);
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-bottom: none;
        }
        
        .register-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .register-card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .register-card-body {
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
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            color: var(--text-light);
            border-left: 4px solid var(--accent-yellow);
        }
        
        .password-requirements h6 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .password-requirements li {
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
        
        .btn-register-custom {
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
        
        .btn-register-custom:hover {
            background: var(--primary-turquoise-dark);
            border-color: var(--primary-turquoise-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .btn-register-custom:active {
            transform: translateY(0);
        }
        
        .register-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .register-links a {
            color: var(--primary-turquoise);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-links a:hover {
            color: var(--primary-turquoise-dark);
            text-decoration: underline;
        }
        
        .terms-check {
            margin-top: 15px;
            margin-bottom: 20px;
        }
        
        .terms-check label {
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .terms-check a {
            color: var(--primary-turquoise);
            text-decoration: none;
        }
        
        .terms-check a:hover {
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
        
        /* Password toggle button - FIXED */
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
    </style>
</head>
<body class="hold-transition register-page">
    <div class="register-box">
        <!-- Register Logo -->
        <div class="register-logo">
            <a href="../index.php">
                <i class="fas fa-umbrella-beach"></i>
                <span>Aura Luxe Resort</span>
            </a>
        </div>
        
        <!-- Register Card -->
        <div class="card register-card">
            <div class="card-header register-card-header">
                <h1><i class="fas fa-user-plus mr-2"></i>Join Our Community</h1>
                <p>Create your free account</p>
            </div>
            
            <div class="card-body register-card-body">
                <!-- Error Messages -->
                <?php if ($error && !isset($_POST['ajax'])): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Registration Form -->
                <form method="POST" class="ajax-form" id="registerForm">
                    <!-- Email Field -->
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
                        <small class="form-text text-muted">We'll send a verification code to this email</small>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <h6><i class="fas fa-shield-alt mr-1"></i>Password Requirements</h6>
                        <ul>
                            <li id="req-length" class="invalid">At least 6 characters</li>
                            <li id="req-match" class="invalid">Passwords must match</li>
                        </ul>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" style="font-weight: 600; color: var(--text-dark);">Password</label>
                        <div class="password-wrapper">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password"
                                    class="form-control" 
                                    placeholder="Create a strong password"
                                    required
                                    minlength="6"
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
                                    placeholder="Re-enter your password"
                                    required
                                    minlength="6"
                                >
                            </div>
                            <button type="button" class="password-toggle-btn" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Terms Agreement -->
                    <div class="terms-check">
                        <div class="icheck-primary">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">
                                I agree to the 
                                <a href="#" onclick="return false;">Terms of Service</a> 
                                and 
                                <a href="#" onclick="return false;">Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-register-custom btn-block">
                            <i class="fas fa-user-plus mr-2"></i>Create Account
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
                
                <!-- Register Links -->
                <div class="register-links">
                    <p class="mb-2">Already have an account?</p>
                    <a href="login.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sign-in-alt mr-1"></i>Sign In Instead
                    </a>
                    <p class="mt-3 mb-0">
                        <a href="../index.php">
                            <i class="fas fa-home mr-1"></i>Back to Home
                        </a>
                    </p>
                </div>
                
                <!-- Registration Info -->
                <div class="alert alert-info alert-custom mt-3">
                    <i class="fas fa-info-circle mr-2"></i>
                    <small>
                        <strong>Note:</strong> You'll receive a verification code via email.<br>
                        You must verify your email before you can login.
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Required Scripts -->
    <script src="../adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../adminlte/dist/js/adminlte.min.js"></script>
    
    <!-- iCheck Plugin -->
    <script src="../adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.js"></script>
    
    <!-- AJAX Handler -->
    <script src="../assets/js/ajax-handler.js"></script>
    
    <!-- Custom Registration Script - FIXED -->
    <script>
        $(document).ready(function() {
            // Password toggle functionality - WORKING FIX
            $('#togglePassword').click(function(e) {
                e.preventDefault();
                const passwordInput = $('#password');
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
            
            // Password strength checker - WORKING FIX
            $('#password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#password-strength-bar');
                const reqLength = $('#req-length');
                
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
            });
            
            // Password match checker - WORKING FIX
            function checkPasswordMatch() {
                const password = $('#password').val();
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
            }
            
            $('#password, #confirm_password').on('input', checkPasswordMatch);
            
            // Custom form submission handler
            $('#registerForm').on('submit', function(e) {
                var form = $(this);
                var submitBtn = form.find('[type="submit"]');
                var originalText = submitBtn.html();
                
                // Check if terms are accepted
                if (!$('#terms').is(':checked')) {
                    showAlert('You must agree to the terms and conditions', 'danger');
                    e.preventDefault();
                    return false;
                }
                
                // Check password match before submission
                if ($('#password').val() !== $('#confirm_password').val()) {
                    showAlert('Passwords do not match', 'danger');
                    e.preventDefault();
                    return false;
                }
                
                // Check password length
                if ($('#password').val().length < 6) {
                    showAlert('Password must be at least 6 characters', 'danger');
                    e.preventDefault();
                    return false;
                }
                
                // Add loading state
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creating Account...');
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
                                } else {
                                    // General error
                                    form.prepend('<div class="invalid-feedback">' + data.errors[field] + '</div>');
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
            $('#password').trigger('input');
            checkPasswordMatch();
        });
    </script>
</body>
</html>