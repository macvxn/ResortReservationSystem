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

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "auth/login.php");
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

// Check if Tesseract OCR is available
function isTesseractAvailable() {
    exec('tesseract --version 2>&1', $output, $return_code);
    return $return_code === 0;
}
?>