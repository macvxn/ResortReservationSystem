<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Valid email required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match";
    } elseif (emailExists($email)) {
        $error = "Email already registered";
    } else {
        // Generate OTP
        $otp = generateOTP();
        $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, role, otp_code, otp_expires_at)
            VALUES (?, ?, 'user', ?, ?)
        ");
        
        if ($stmt->execute([$email, $password_hash, $otp, $otp_expires])) {
            
            // Send OTP email
            $emailResult = sendOTPEmail($email, $email, $otp);
            
            if ($emailResult['success']) {
                $_SESSION['temp_email'] = $email;
                // Remove this in production - only for testing
                // $_SESSION['temp_otp'] = $otp;
                
                header("Location: verify-otp.php?sent=1");
                exit();
            } else {
                $error = "Account created but failed to send email: " . $emailResult['message'];
            }
        } else {
            $error = "Registration failed";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Resort Reservation</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2 class="text-center">Create Account</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required autofocus placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label>Password (min. 6 characters)</label>
                <input type="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <p class="text-center mt-20">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </div>
</body>
</html>