<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Log the logout action
if (isset($_SESSION['user_id'])) {
    logAction($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
}

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login with message
header("Location: login.php?logged_out=1");
exit();
?>