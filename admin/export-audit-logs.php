<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

// Get filter parameters
$action_type = $_GET['action_type'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'csv';

// Build query (same as audit-logs.php)
$sql = "
    SELECT 
        al.*,
        u.email as user_email,
        u.role as user_role,
        up.full_name as user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    LEFT JOIN user_profiles up ON al.user_id = up.user_id
    WHERE DATE(al.created_at) BETWEEN ? AND ?
";

$params = [$date_from, $date_to];

if ($action_type !== 'all') {
    $sql .= " AND al.action_type = ?";
    $params[] = $action_type;
}

$sql .= " ORDER BY al.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Log export action
logAction($_SESSION['user_id'], 'EXPORT_AUDIT_LOGS', 'audit_logs', null);

// Export based on format
if ($format === 'csv') {
    exportAuditLogsCSV($logs, $date_from, $date_to);
} elseif ($format === 'json') {
    exportAuditLogsJSON($logs, $date_from, $date_to);
}

function exportAuditLogsCSV($logs, $date_from, $date_to) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . $date_from . '_to_' . $date_to . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Headers
    fputcsv($output, [
        'Log ID',
        'Timestamp',
        'User ID',
        'User Email',
        'User Name',
        'User Role',
        'Action Type',
        'Table Affected',
        'Record ID',
        'IP Address',
        'Old Values',
        'New Values',
        'Created At'
    ]);
    
    // Data rows
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['log_id'],
            $log['created_at'],
            $log['user_id'],
            $log['user_email'],
            $log['user_name'],
            $log['user_role'],
            $log['action_type'],
            $log['table_affected'],
            $log['record_id'],
            $log['ip_address'],
            $log['old_value'],
            $log['new_value'],
            $log['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}

function exportAuditLogsJSON($logs, $date_from, $date_to) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . $date_from . '_to_' . $date_to . '.json"');
    
    $export_data = [
        'metadata' => [
            'export_date' => date('Y-m-d H:i:s'),
            'date_from' => $date_from,
            'date_to' => $date_to,
            'total_records' => count($logs),
            'exported_by' => $_SESSION['email'] ?? 'Admin'
        ],
        'logs' => $logs
    ];
    
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
}
?>