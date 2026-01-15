<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';

// Check if coming from registration
if (!isset($_SESSION['temp_email'])) {
    header("Location: register.php");
    exit();
}

$error = '';
$success = '';
$email = $_SESSION['temp_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // PRESERVED BUSINESS LOGIC - DO NOT MODIFY
    $otp_entered = clean($_POST['otp']);
    
    // Get user with OTP
    $stmt = $pdo->prepare("
        SELECT user_id, otp_code, otp_expires_at 
        FROM users 
        WHERE email = ? AND is_email_verified = 0
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "User not found or already verified";
    } elseif (strtotime($user['otp_expires_at']) < time()) {
        $error = "OTP has expired. Please request a new one.";
    } elseif ($user['otp_code'] != $otp_entered) {
        $error = "Invalid OTP code. Please check and try again.";
    } else {
        // OTP is correct - verify user
        $pdo->beginTransaction();
        
        try {
            // Update user verification status
            $stmt = $pdo->prepare("
                UPDATE users 
                SET is_email_verified = 1, 
                    otp_code = NULL, 
                    otp_expires_at = NULL 
                WHERE user_id = ?
            ");
            $stmt->execute([$user['user_id']]);
            
            // Create empty user profile
            $stmt = $pdo->prepare("
                INSERT INTO user_profiles (user_id, full_name, verification_status)
                VALUES (?, '', 'unverified')
            ");
            $stmt->execute([$user['user_id']]);
            
            // Log the action
            logAction($user['user_id'], 'email_verified', 'users', $user['user_id']);
            
            $pdo->commit();
            
            // Send welcome email
            sendWelcomeEmail($email, 'User');
            
            // Set session and redirect
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';
            
            // Clear temporary session data
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_otp']);
            
            // Check for AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                
                // AJAX response
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Email verified successfully! Welcome to Aura Luxe Resort.',
                    'redirect' => '../user/dashboard.php?verified=1'
                ]);
                exit();
            } else {
                // Traditional redirect for non-JS users
                header("Location: ../user/dashboard.php?verified=1");
                exit();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Verification failed. Please try again.";
            error_log("Verification error: " . $e->getMessage());
        }
    }
    
    // AJAX response for errors
    if ($error && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error,
            'errors' => ['otp' => $error]
        ]);
        exit();
    }
}

// Resend OTP functionality
if (isset($_GET['resend'])) {
    $new_otp = generateOTP();
    $new_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET otp_code = ?, otp_expires_at = ? 
        WHERE email = ?
    ");
    
    if ($stmt->execute([$new_otp, $new_expires, $email])) {
        // Send new OTP email
        $emailResult = sendOTPEmail($email, $email, $new_otp);
        
        if ($emailResult['success']) {
            $success = "New OTP sent to your email!";
        } else {
            $error = "Failed to send OTP: " . $emailResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Aura Luxe Resort</title>
    
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
        
        .verify-page {
            background: 
                linear-gradient(135deg, rgba(64, 224, 208, 0.85) 0%, rgba(0, 191, 255, 0.85) 100%),
                url('https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
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
        
        .verify-box {
            width: 100%;
            max-width: 450px;
        }
        
        .verify-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .verify-logo a {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .verify-logo a i {
            color: var(--accent-yellow);
        }
        
        .verify-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .verify-card-header {
            background: var(--primary-turquoise);
            color: white;
            text-align: center;
            padding: 25px 20px;
            border-bottom: none;
        }
        
        .verify-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .verify-card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .verify-card-body {
            padding: 30px;
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
        
        /* OTP Input Styling */
        .otp-container {
            margin: 30px 0;
        }
        
        .otp-input-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .otp-digit {
            width: 50px;
            height: 60px;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .otp-digit:focus {
            border-color: var(--primary-turquoise);
            box-shadow: 0 0 0 0.2rem rgba(64, 224, 208, 0.25);
            background: white;
            outline: none;
        }
        
        .otp-digit.filled {
            border-color: var(--primary-turquoise);
            background: white;
            color: var(--primary-turquoise);
        }
        
        .otp-digit.error {
            border-color: var(--accent-coral);
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .countdown-container {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .countdown {
            font-size: 1.1rem;
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .countdown.expired {
            color: var(--accent-coral);
        }
        
        .countdown-icon {
            color: var(--primary-turquoise);
            margin-right: 8px;
        }
        
        .btn-verify-custom {
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
        
        .btn-verify-custom:hover {
            background: var(--primary-turquoise-dark);
            border-color: var(--primary-turquoise-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .btn-resend-custom {
            background: var(--accent-coral);
            border-color: var(--accent-coral);
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-resend-custom:hover {
            background: var(--accent-coral-dark);
            border-color: var(--accent-coral-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 111, 97, 0.4);
        }
        
        .btn-resend-custom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .verify-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .verify-links a {
            color: var(--primary-turquoise);
            text-decoration: none;
            font-weight: 500;
        }
        
        .verify-links a:hover {
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
        
        /* Instructions */
        .instructions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .instructions ul {
            margin: 10px 0 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 5px;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .otp-digit {
                width: 40px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .verify-box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body class="hold-transition verify-page">
    <div class="verify-box">
        <!-- Verify Logo -->
        <div class="verify-logo">
            <a href="../index.php">
                <i class="fas fa-umbrella-beach"></i>
                <span>Aura Luxe Resort</span>
            </a>
        </div>
        
        <!-- Verify Card -->
        <div class="card verify-card">
            <div class="card-header verify-card-header">
                <h1><i class="fas fa-envelope mr-2"></i>Email Verification</h1>
                <p>Enter the 6-digit code sent to your email</p>
            </div>
            
            <div class="card-body verify-card-body">
                <!-- Success Messages -->
                <?php if (isset($_GET['sent'])): ?>
                    <div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle mr-2"></i>
                        OTP sent successfully! Please check your email inbox.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Error Messages -->
                <?php if ($error && !isset($_POST['ajax'])): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Email Display -->
                <div class="email-display">
                    <p><i class="fas fa-envelope mr-2"></i>Verification code sent to:</p>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>
                
                <!-- OTP Form -->
                <form method="POST" class="ajax-form" id="otpForm">
                    <!-- OTP Input -->
                    <div class="otp-container">
                        <div class="otp-input-group">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <input 
                                    type="text" 
                                    name="otp_<?php echo $i; ?>"
                                    id="otp_<?php echo $i; ?>"
                                    class="otp-digit"
                                    maxlength="1"
                                    data-index="<?php echo $i; ?>"
                                    autocomplete="off"
                                >
                            <?php endfor; ?>
                            <input type="hidden" name="otp" id="otpHidden">
                        </div>
                        
                        <!-- Countdown Timer -->
                        <div class="countdown-container">
                            <div class="countdown" id="countdown">
                                <i class="fas fa-clock countdown-icon"></i>
                                <span id="timer">15:00</span>
                            </div>
                        </div>
                        
                        <!-- Verify Button -->
                        <button type="submit" class="btn btn-verify-custom" id="verifyBtn">
                            <i class="fas fa-check-circle mr-2"></i>Verify Email
                        </button>
                    </div>
                </form>
                
                <!-- Resend OTP -->
                <div class="text-center mt-3">
                    <p class="text-muted mb-2">Didn't receive the code?</p>
                    <a href="?resend=1" class="btn btn-resend-custom" id="resendBtn">
                        <i class="fas fa-redo mr-2"></i>Resend OTP
                    </a>
                    <p class="text-muted mt-2 small" id="resendTimer"></p>
                </div>
                
                <!-- Fallback for non-JS users -->
                <noscript>
                    <div class="alert alert-info alert-custom mt-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        JavaScript is disabled. Please enter the 6-digit code manually.
                    </div>
                </noscript>
                
                <!-- Instructions -->
                <div class="instructions">
                    <p class="mb-1"><strong>Instructions:</strong></p>
                    <ul>
                        <li>Check your email inbox (and spam folder)</li>
                        <li>Enter the 6-digit code received</li>
                        <li>Code expires in 15 minutes</li>
                        <li>You can request a new code after 60 seconds</li>
                    </ul>
                </div>
                
                <!-- Navigation Links -->
                <div class="verify-links">
                    <p class="mb-0">
                        <a href="register.php">
                            <i class="fas fa-arrow-left mr-1"></i>Use different email
                        </a>
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
    
    <!-- Custom OTP Verification Script -->
    <script>
        $(document).ready(function() {
            // OTP input handling
            const otpInputs = $('.otp-digit');
            const hiddenOtpInput = $('#otpHidden');
            let currentOtp = ['', '', '', '', '', ''];
            
            // Function to update hidden input
            function updateHiddenOtp() {
                const otpValue = currentOtp.join('');
                hiddenOtpInput.val(otpValue);
                
                // Auto-fill all inputs for non-JS fallback
                otpInputs.each(function(index) {
                    $(this).val(currentOtp[index]);
                });
            }
            
            // Handle OTP input
            otpInputs.on('input', function() {
                const index = parseInt($(this).data('index')) - 1;
                const value = $(this).val();
                
                // Only allow digits
                if (!/^\d*$/.test(value)) {
                    $(this).val('');
                    currentOtp[index] = '';
                    updateHiddenOtp();
                    return;
                }
                
                // Limit to one digit
                if (value.length > 1) {
                    $(this).val(value.charAt(0));
                }
                
                currentOtp[index] = $(this).val();
                updateHiddenOtp();
                
                // Move to next input
                if (value !== '' && index < 5) {
                    otpInputs.eq(index + 1).focus();
                }
                
                // Remove error state
                $(this).removeClass('error');
                
                // Add filled class
                if (value !== '') {
                    $(this).addClass('filled');
                } else {
                    $(this).removeClass('filled');
                }
            });
            
            // Handle backspace
            otpInputs.on('keydown', function(e) {
                const index = parseInt($(this).data('index')) - 1;
                
                if (e.key === 'Backspace') {
                    if (currentOtp[index] === '' && index > 0) {
                        // Move to previous input
                        otpInputs.eq(index - 1).focus();
                    }
                    currentOtp[index] = '';
                    $(this).val('');
                    updateHiddenOtp();
                    $(this).removeClass('filled');
                    
                    e.preventDefault();
                }
                
                // Allow arrow keys navigation
                if (e.key === 'ArrowLeft' && index > 0) {
                    otpInputs.eq(index - 1).focus();
                    e.preventDefault();
                }
                
                if (e.key === 'ArrowRight' && index < 5) {
                    otpInputs.eq(index + 1).focus();
                    e.preventDefault();
                }
            });
            
            // Handle paste
            otpInputs.eq(0).on('paste', function(e) {
                e.preventDefault();
                const pastedData = e.originalEvent.clipboardData.getData('text');
                const digits = pastedData.replace(/[^\d]/g, '').split('').slice(0, 6);
                
                digits.forEach((digit, index) => {
                    if (index < 6) {
                        otpInputs.eq(index).val(digit);
                        currentOtp[index] = digit;
                        otpInputs.eq(index).addClass('filled');
                    }
                });
                
                updateHiddenOtp();
                
                // Focus on last filled input or next available
                const lastFilledIndex = digits.length - 1;
                if (lastFilledIndex < 5) {
                    otpInputs.eq(lastFilledIndex + 1).focus();
                } else {
                    otpInputs.eq(5).focus();
                }
            });
            
            // Countdown timer (15 minutes)
            let timeLeft = 900; // 15 minutes in seconds
            const timerEl = $('#timer');
            const countdownEl = $('#countdown');
            const resendBtn = $('#resendBtn');
            const resendTimerEl = $('#resendTimer');
            let canResend = false;
            let resendCooldown = 60; // 60 seconds cooldown
            
            function updateTimer() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerEl.text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
                
                if (timeLeft <= 0) {
                    countdownEl.addClass('expired');
                    timerEl.html('<span style="color: var(--accent-coral);">Code expired</span>');
                    otpInputs.prop('disabled', true).addClass('error');
                    $('#verifyBtn').prop('disabled', true);
                } else {
                    timeLeft--;
                    setTimeout(updateTimer, 1000);
                }
            }
            
            function updateResendTimer() {
                if (resendCooldown > 0) {
                    resendTimerEl.text(`Resend available in ${resendCooldown} seconds`);
                    resendCooldown--;
                    setTimeout(updateResendTimer, 1000);
                } else {
                    canResend = true;
                    resendTimerEl.text('');
                    resendBtn.prop('disabled', false);
                }
            }
            
            // Disable resend button initially
            resendBtn.prop('disabled', true);
            updateResendTimer();
            
            // Update resend button state
            resendBtn.on('click', function(e) {
                if (!canResend) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Start timers
            updateTimer();
            
            // Custom form submission
            $('#otpForm').on('submit', function(e) {
                const form = $(this);
                const submitBtn = form.find('[type="submit"]');
                const originalText = submitBtn.html();
                
                // Validate OTP
                const otpValue = hiddenOtpInput.val();
                if (otpValue.length !== 6) {
                    showAlert('Please enter the complete 6-digit code', 'danger');
                    otpInputs.addClass('error');
                    e.preventDefault();
                    return false;
                }
                
                // Check if expired
                if (timeLeft <= 0) {
                    showAlert('OTP has expired. Please request a new one.', 'danger');
                    e.preventDefault();
                    return false;
                }
                
                // Add loading state
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
                submitBtn.addClass('btn-loading');
                
                // Clear previous errors
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();
                otpInputs.removeClass('error');
                
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
                        
                        // Add error styling to OTP inputs
                        otpInputs.addClass('error');
                        
                        // Clear OTP on error
                        otpInputs.val('').removeClass('filled');
                        currentOtp = ['', '', '', '', '', ''];
                        updateHiddenOtp();
                        otpInputs.eq(0).focus();
                        
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
            
            // Focus first OTP input on load
            setTimeout(() => {
                otpInputs.eq(0).focus();
            }, 100);
        });
    </script>
</body>
</html>