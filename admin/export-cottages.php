<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Get all cottages with details
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        COUNT(DISTINCT ci.image_id) as image_count,
        COUNT(DISTINCT r.reservation_id) as total_reservations,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
        SUM(CASE WHEN r.status = 'pending_admin_review' THEN 1 ELSE 0 END) as pending_reservations
    FROM cottages c
    LEFT JOIN cottage_images ci ON c.cottage_id = ci.cottage_id
    LEFT JOIN reservations r ON c.cottage_id = r.cottage_id
    GROUP BY c.cottage_id
    ORDER BY c.cottage_name ASC
");
$stmt->execute();
$cottages = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="cottages_export_' . date('Y-m-d_His') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add header row
fputcsv($output, [
    'Cottage ID',
    'Cottage Name',
    'Description',
    'Capacity',
    'Price per Night',
    'Status',
    'Total Images',
    'Total Reservations',
    'Approved Reservations',
    'Pending Reservations',
    'Created Date',
    'Last Updated'
]);

// Add data rows
foreach ($cottages as $cottage) {
    fputcsv($output, [
        $cottage['cottage_id'],
        $cottage['cottage_name'],
        $cottage['description'],
        $cottage['capacity'],
        number_format($cottage['price_per_night'], 2),
        $cottage['is_active'] ? 'Active' : 'Inactive',
        $cottage['image_count'],
        $cottage['total_reservations'] ?? 0,
        $cottage['approved_reservations'] ?? 0,
        $cottage['pending_reservations'] ?? 0,
        date('Y-m-d H:i:s', strtotime($cottage['created_at'])),
        date('Y-m-d H:i:s', strtotime($cottage['updated_at']))
    ]);
}

// Log action
logAction($_SESSION['user_id'], 'cottage_list_exported', 'cottages', null);

fclose($output);
exit();
?>