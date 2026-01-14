<?php
session_start();

// Base configuration
define('BASE_URL', 'http://127.0.0.1:8000/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'phpmailer028@gmail.com');
define('SMTP_PASSWORD', 'eornkfwqgplacryl');
define('SMTP_FROM_EMAIL', 'phpmailer028@gmail.com');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is blocked
function isUserBlocked() {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    require_once __DIR__ . '/database.php';
    
    $stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user && $user['status'] === 'blocked';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
    
    // Check if user is blocked (except admins)
    if (!isAdmin() && isUserBlocked()) {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "auth/login.php?blocked=1");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "user/dashboard.php");
        exit();
    }
}

// Sanitize input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate random OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}
?>