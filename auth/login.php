<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Redirect if already logged in (based on role)
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/dashboard.php");
            }
            exit();
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
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Resort Reservation</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            color: white;
        }
        .login-header h2 {
            color: white;
            margin: 0;
        }
        .role-indicator {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-header">
            <h2>🏖️ Resort Reservation System</h2>
            <p style="margin: 5px 0 0 0;">Sign in to your account</p>
            <div class="role-indicator">
                For both Users & Administrators
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">
                Registration successful! Please check your email for verification.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['verified'])): ?>
            <div class="alert alert-success">
                Email verified! You can now login.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logged_out'])): ?>
            <div class="alert alert-info">
                You have been logged out successfully.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required 
                    autofocus
                    placeholder="your@email.com"
                >
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input 
                    type="password" 
                    name="password" 
                    required
                    placeholder="Enter your password"
                >
            </div>
            
            <div class="form-group">
                <label style="font-weight: normal;">
                    <input type="checkbox" name="remember"> Remember me
                </label>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="text-center mt-20">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>

        <div class="card" style="margin-top: 20px; background: #f8f9fa;">
            <p style="margin: 0; font-size: 14px; color: #666; text-align: center;">
                <strong>Note:</strong> System automatically detects user role<br>
                Regular users and administrators use the same login
            </p>
        </div>
    </div>
</body>
</html>