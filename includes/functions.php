<?php
require_once __DIR__ . '/../config/database.php';

// Check if email exists
function emailExists($email) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// Create audit log
function logAction($user_id, $action_type, $table_affected = null, $record_id = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action_type, table_affected, record_id, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->execute([$user_id, $action_type, $table_affected, $record_id, $ip]);
}

// Get user profile
function getUserProfile($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT up.*, u.email, u.role 
        FROM user_profiles up
        JOIN users u ON up.user_id = u.user_id
        WHERE up.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Get verification status badge
function getVerificationBadge($status) {
    $badges = [
        'unverified' => '<span style="background: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px; font-size: 12px;">⚠️ Unverified</span>',
        'pending_verification' => '<span style="background: #17a2b8; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px;">🕐 Pending Review</span>',
        'verified' => '<span style="background: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px;">✓ Verified</span>',
        'rejected' => '<span style="background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px;">✗ Rejected</span>'
    ];
    
    return $badges[$status] ?? $status;
}

// Get reservation status badge
function getReservationBadge($status) {
    $badges = [
        'pending_admin_review' => '<span style="background: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px; font-size: 12px;">⏳ Pending</span>',
        'approved' => '<span style="background: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px;">✓ Approved</span>',
        'rejected' => '<span style="background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px;">✗ Rejected</span>'
    ];
    
    return $badges[$status] ?? $status;
}

// Format date for display
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Format datetime for display
function formatDateTime($datetime) {
    return date('F j, Y g:i A', strtotime($datetime));
}

// Upload file helper
function uploadFile($file, $folder) {
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, PDF'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large (max 5MB)'];
    }
    
    $new_name = uniqid() . '_' . time() . '.' . $ext;
    $upload_path = UPLOAD_PATH . $folder . '/' . $new_name;
    
    // Create directory if it doesn't exist
    if (!is_dir(UPLOAD_PATH . $folder)) {
        mkdir(UPLOAD_PATH . $folder, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_name];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

// Check date availability
function isDateAvailable($cottage_id, $check_in, $check_out, $exclude_reservation_id = null) {
    global $pdo;
    
    $sql = "
        SELECT COUNT(*) as conflicts
        FROM reservations
        WHERE cottage_id = ?
        AND status = 'approved'
        AND (
            (check_in_date <= ? AND check_out_date > ?)
            OR
            (check_in_date < ? AND check_out_date >= ?)
            OR
            (check_in_date >= ? AND check_out_date <= ?)
        )
    ";
    
    $params = [$cottage_id, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out];
    
    if ($exclude_reservation_id) {
        $sql .= " AND reservation_id != ?";
        $params[] = $exclude_reservation_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['conflicts'] == 0;
}

// Calculate nights between dates
function calculateNights($check_in, $check_out) {
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $interval = $date1->diff($date2);
    return $interval->days;
}

// Get pending verifications count
function getPendingVerificationsCount() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM user_profiles
        WHERE verification_status = 'pending_verification'
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['count'];
}

// Get all pending verifications
function getPendingVerifications() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT up.*, u.email, u.created_at as registration_date
        FROM user_profiles up
        JOIN users u ON up.user_id = u.user_id
        WHERE up.verification_status = 'pending_verification'
        ORDER BY up.updated_at ASC
    ");
    $stmt->execute();
    
    return $stmt->fetchAll();
}
?>