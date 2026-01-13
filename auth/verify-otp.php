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
$email = $_SESSION['temp_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            
            header("Location: ../user/dashboard.php?verified=1");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Verification failed. Please try again.";
            error_log("Verification error: " . $e->getMessage());
        }
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
    <title>Verify Email - Resort Reservation</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .otp-input {
            text-align: center;
            font-size: 32px;
            letter-spacing: 10px;
            font-weight: bold;
            padding: 20px;
        }
        .email-display {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .countdown {
            text-align: center;
            color: #666;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">📧 Verify Your Email</h2>
        
        <div class="email-display">
            <p style="margin: 0; color: #666;">Verification code sent to:</p>
            <strong style="color: #333; font-size: 16px;"><?php echo htmlspecialchars($email); ?></strong>
        </div>
        
        <?php if (isset($_GET['sent'])): ?>
            <div class="alert alert-success">
                ✓ OTP sent successfully! Please check your email inbox.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="otpForm">
            <div class="form-group">
                <label>Enter 6-Digit Code</label>
                <input 
                    type="text" 
                    name="otp" 
                    id="otpInput"
                    class="otp-input"
                    maxlength="6" 
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    required 
                    autofocus
                    autocomplete="off"
                >
                <div class="countdown" id="countdown"></div>
            </div>
            
            <button type="submit">Verify Email</button>
        </form>
        
        <div class="text-center mt-20">
            <p style="color: #666;">Didn't receive the code?</p>
            <a href="?resend=1" class="btn btn-secondary" style="width: auto; display: inline-block;">
                📨 Resend OTP
            </a>
        </div>
        
        <p class="text-center mt-20">
            <a href="register.php">← Use different email</a>
        </p>
    </div>
    
    <script>
        // Auto-focus OTP input
        const otpInput = document.getElementById('otpInput');
        
        // Only allow numbers
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits entered
            if (this.value.length === 6) {
                setTimeout(() => {
                    document.getElementById('otpForm').submit();
                }, 300);
            }
        });
        
        // Countdown timer (15 minutes)
        let timeLeft = 900; // 15 minutes in seconds
        const countdownEl = document.getElementById('countdown');
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownEl.textContent = `Code expires in ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                countdownEl.innerHTML = '<span style="color: #dc3545;">⚠️ Code expired. Please request a new one.</span>';
                otpInput.disabled = true;
            } else {
                timeLeft--;
                setTimeout(updateCountdown, 1000);
            }
        }
        
        updateCountdown();
    </script>
</body>
</html>