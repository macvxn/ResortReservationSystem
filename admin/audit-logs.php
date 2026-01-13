<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

// Get filter parameters
$action_type = $_GET['action_type'] ?? 'all';
$user_id = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$table_affected = $_GET['table_affected'] ?? 'all';

// Build query
$sql = "
    SELECT 
        al.*,
        u.email as user_email,
        u.role as user_role,
        up.full_name as user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    LEFT JOIN user_profiles up ON al.user_id = up.user_id
    WHERE 1=1
";

$params = [];

// Action type filter
if ($action_type !== 'all') {
    $sql .= " AND al.action_type = ?";
    $params[] = $action_type;
}

// User filter
if (!empty($user_id) && is_numeric($user_id)) {
    $sql .= " AND al.user_id = ?";
    $params[] = $user_id;
}

// Date range filter
if (!empty($date_from)) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

// Search filter
if (!empty($search)) {
    $sql .= " AND (
        al.action_type LIKE ? OR 
        al.table_affected LIKE ? OR 
        al.ip_address LIKE ? OR
        u.email LIKE ? OR
        up.full_name LIKE ?
    )";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Table filter
if ($table_affected !== 'all') {
    $sql .= " AND al.table_affected = ?";
    $params[] = $table_affected;
}

$sql .= " ORDER BY al.created_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique action types for filter
$action_types_stmt = $pdo->query("SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type");
$action_types = $action_types_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get unique tables for filter
$tables_stmt = $pdo->query("SELECT DISTINCT table_affected FROM audit_logs WHERE table_affected IS NOT NULL ORDER BY table_affected");
$tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get recent users for filter
$users_stmt = $pdo->prepare("
    SELECT DISTINCT u.user_id, u.email, up.full_name 
    FROM audit_logs al
    JOIN users u ON al.user_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE al.user_id IS NOT NULL
    ORDER BY al.created_at DESC
    LIMIT 20
");
$users_stmt->execute();
$recent_users = $users_stmt->fetchAll();

// Get stats
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_logs,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT action_type) as unique_actions,
        COUNT(DISTINCT ip_address) as unique_ips,
        MIN(created_at) as first_log,
        MAX(created_at) as last_log
    FROM audit_logs
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stats_stmt->execute([$date_from, $date_to]);
$stats = $stats_stmt->fetch();

// Get top actions
$top_actions_stmt = $pdo->prepare("
    SELECT 
        action_type,
        COUNT(*) as count
    FROM audit_logs
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY action_type
    ORDER BY count DESC
    LIMIT 10
");
$top_actions_stmt->execute([$date_from, $date_to]);
$top_actions = $top_actions_stmt->fetchAll();

// Get failed login attempts
$failed_logins_stmt = $pdo->prepare("
    SELECT 
        DATE(attempted_at) as attempt_date,
        COUNT(*) as attempt_count
    FROM login_attempts
    WHERE is_successful = FALSE
    AND DATE(attempted_at) BETWEEN ? AND ?
    GROUP BY DATE(attempted_at)
    ORDER BY attempt_date DESC
");
$failed_logins_stmt->execute([$date_from, $date_to]);
$failed_logins = $failed_logins_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs & Security - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <h2><i class="fas fa-shield-alt"></i> Audit Logs & System Security</h2>
        
        <!-- Security Stats -->
        <div class="stats-grid">
            <div class="stat-card security">
                <div class="stat-icon">
                    <i class="fas fa-history fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_logs'] ?? 0) ?></h3>
                    <p>Total Audit Logs</p>
                </div>
            </div>
            
            <div class="stat-card security">
                <div class="stat-icon">
                    <i class="fas fa-user-secret fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['unique_users'] ?? 0) ?></h3>
                    <p>Users Tracked</p>
                </div>
            </div>
            
            <div class="stat-card security">
                <div class="stat-icon">
                    <i class="fas fa-network-wired fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['unique_ips'] ?? 0) ?></h3>
                    <p>Unique IP Addresses</p>
                </div>
            </div>
            
            <div class="stat-card security">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
                <div class="stat-content">
                    <?php
                    $failed_total = array_sum(array_column($failed_logins, 'attempt_count'));
                    ?>
                    <h3 style="color: #dc3545;"><?= number_format($failed_total) ?></h3>
                    <p>Failed Login Attempts</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Action Type:</label>
                        <select name="action_type" class="form-control">
                            <option value="all" <?= $action_type === 'all' ? 'selected' : '' ?>>All Actions</option>
                            <?php foreach ($action_types as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $action_type === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>User:</label>
                        <select name="user_id" class="form-control">
                            <option value="">All Users</option>
                            <?php foreach ($recent_users as $user): ?>
                            <option value="<?= $user['user_id'] ?>" <?= $user_id == $user['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['full_name'] ?: $user['email']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Table:</label>
                        <select name="table_affected" class="form-control">
                            <option value="all" <?= $table_affected === 'all' ? 'selected' : '' ?>>All Tables</option>
                            <?php foreach ($tables as $table): ?>
                            <option value="<?= htmlspecialchars($table) ?>" <?= $table_affected === $table ? 'selected' : '' ?>>
                                <?= htmlspecialchars($table) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>From Date:</label>
                        <input type="date" 
                               name="date_from" 
                               value="<?= htmlspecialchars($date_from) ?>"
                               class="form-control"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>To Date:</label>
                        <input type="date" 
                               name="date_to" 
                               value="<?= htmlspecialchars($date_to) ?>"
                               class="form-control"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Search:</label>
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="Search logs..."
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter Logs
                        </button>
                        <a href="audit-logs.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <button type="button" onclick="exportLogs()" class="btn btn-success">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button type="button" onclick="clearOldLogs()" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Cleanup
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-row">
                <div class="chart-container">
                    <canvas id="activityChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="failedLoginsChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Actions -->
        <div class="top-actions-section">
            <h3><i class="fas fa-chart-bar"></i> Top 10 Actions</h3>
            <div class="top-actions-grid">
                <?php foreach ($top_actions as $index => $action): ?>
                <div class="action-item">
                    <div class="action-rank">#<?= $index + 1 ?></div>
                    <div class="action-info">
                        <div class="action-name"><?= htmlspecialchars($action['action_type']) ?></div>
                        <div class="action-count"><?= number_format($action['count']) ?> logs</div>
                    </div>
                    <div class="action-bar">
                        <div class="action-bar-fill" style="width: <?= min(100, ($action['count'] / max(1, $top_actions[0]['count'])) * 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Audit Logs Table -->
        <div class="table-section">
            <h3>
                <i class="fas fa-list-alt"></i>
                Audit Logs (<?= count($logs) ?> records)
                <small>Showing most recent 500 logs</small>
            </h3>
            
            <div class="table-container">
                <table class="admin-table audit-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No audit logs found for the selected filters</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div class="timestamp">
                                        <?= formatDateTime($log['created_at']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                    <div class="user-info">
                                        <div class="user-name"><?= htmlspecialchars($log['user_name'] ?: $log['user_email']) ?></div>
                                        <div class="user-role"><?= htmlspecialchars($log['user_role']) ?></div>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="action-badge action-<?= strtolower(str_replace('_', '-', $log['action_type'])) ?>">
                                        <?= htmlspecialchars($log['action_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $log['table_affected'] ? htmlspecialchars($log['table_affected']) : '<span class="text-muted">N/A</span>' ?>
                                </td>
                                <td>
                                    <?= $log['record_id'] ? '#' . $log['record_id'] : '<span class="text-muted">N/A</span>' ?>
                                </td>
                                <td>
                                    <div class="ip-address">
                                        <i class="fas fa-globe"></i>
                                        <?= htmlspecialchars($log['ip_address']) ?>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-info"
                                            onclick="showLogDetails(<?= htmlspecialchars(json_encode($log)) ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Security Monitoring -->
        <div class="security-section">
            <h3><i class="fas fa-desktop"></i> System Security Monitoring</h3>
            
            <div class="security-grid">
                <!-- Failed Login Attempts -->
                <div class="security-card">
                    <div class="security-card-header">
                        <h4><i class="fas fa-user-lock"></i> Failed Login Attempts</h4>
                        <span class="badge badge-danger"><?= count($failed_logins) ?> days</span>
                    </div>
                    <div class="security-card-body">
                        <?php if (empty($failed_logins)): ?>
                        <p class="text-success">No failed login attempts detected.</p>
                        <?php else: ?>
                        <table class="security-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Attempts</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($failed_logins as $attempt): ?>
                                <tr>
                                    <td><?= formatDate($attempt['attempt_date']) ?></td>
                                    <td><?= $attempt['attempt_count'] ?></td>
                                    <td>
                                        <?php if ($attempt['attempt_count'] > 10): ?>
                                        <span class="badge badge-danger">Critical</span>
                                        <?php elseif ($attempt['attempt_count'] > 5): ?>
                                        <span class="badge badge-warning">Warning</span>
                                        <?php else: ?>
                                        <span class="badge badge-info">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- System Health -->
                <div class="security-card">
                    <div class="security-card-header">
                        <h4><i class="fas fa-heartbeat"></i> System Health</h4>
                        <span class="badge badge-success">Online</span>
                    </div>
                    <div class="security-card-body">
                        <?php
                        // Check system health
                        $health_checks = [
                            'Database Connection' => checkDatabaseHealth(),
                            'Upload Directory' => checkUploadDirectory(),
                            'Session Status' => checkSessionHealth(),
                            'PHP Version' => checkPHPVersion(),
                            'Security Headers' => checkSecurityHeaders()
                        ];
                        ?>
                        <table class="health-table">
                            <?php foreach ($health_checks as $check => $status): ?>
                            <tr>
                                <td><?= $check ?></td>
                                <td>
                                    <?php if ($status['status'] === 'healthy'): ?>
                                    <span class="health-indicator healthy">
                                        <i class="fas fa-check-circle"></i> Healthy
                                    </span>
                                    <?php elseif ($status['status'] === 'warning'): ?>
                                    <span class="health-indicator warning">
                                        <i class="fas fa-exclamation-triangle"></i> Warning
                                    </span>
                                    <?php else: ?>
                                    <span class="health-indicator critical">
                                        <i class="fas fa-times-circle"></i> Critical
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= $status['message'] ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
                
                <!-- Backup Status -->
                <div class="security-card">
                    <div class="security-card-header">
                        <h4><i class="fas fa-database"></i> Backup Status</h4>
                        <span class="badge badge-info">Auto</span>
                    </div>
                    <div class="security-card-body">
                        <?php
                        $backup_info = getBackupInfo();
                        ?>
                        <div class="backup-info">
                            <div class="backup-item">
                                <span class="backup-label">Last Backup:</span>
                                <span class="backup-value">
                                    <?= $backup_info['last_backup'] ? formatDateTime($backup_info['last_backup']) : 'Never' ?>
                                </span>
                            </div>
                            <div class="backup-item">
                                <span class="backup-label">Backup Size:</span>
                                <span class="backup-value"><?= $backup_info['size'] ?></span>
                            </div>
                            <div class="backup-item">
                                <span class="backup-label">Backup Count:</span>
                                <span class="backup-value"><?= $backup_info['count'] ?> files</span>
                            </div>
                        </div>
                        
                        <div class="backup-actions">
                            <button type="button" onclick="createBackup()" class="btn btn-sm btn-primary">
                                <i class="fas fa-save"></i> Create Backup
                            </button>
                            <button type="button" onclick="restoreBackup()" class="btn btn-sm btn-warning">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <a href="backup-management.php" class="btn btn-sm btn-info">
                                <i class="fas fa-cog"></i> Manage
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Log Details Modal -->
    <div id="logDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Log Details</h3>
                <button type="button" class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
    
    <script>
    // Initialize Charts
    document.addEventListener('DOMContentLoaded', function() {
        // Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($failed_logins, 'attempt_date')) ?>,
                datasets: [{
                    label: 'Failed Login Attempts',
                    data: <?= json_encode(array_column($failed_logins, 'attempt_count')) ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Attempts'
                        }
                    }
                }
            }
        });
        
        // Top Actions Chart
        const actionsCtx = document.getElementById('failedLoginsChart').getContext('2d');
        const actionsChart = new Chart(actionsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($top_actions, 'action_type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($top_actions, 'count')) ?>,
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545',
                        '#6c757d', '#17a2b8', '#6610f2', '#e83e8c',
                        '#fd7e14', '#20c997'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    });
    
    // Show log details
    function showLogDetails(log) {
        const content = document.getElementById('logDetailsContent');
        content.innerHTML = `
            <div class="log-details">
                <div class="detail-row">
                    <strong>Log ID:</strong> ${log.log_id}
                </div>
                <div class="detail-row">
                    <strong>Timestamp:</strong> ${new Date(log.created_at).toLocaleString()}
                </div>
                <div class="detail-row">
                    <strong>User:</strong> ${log.user_name || log.user_email || 'System'}
                </div>
                <div class="detail-row">
                    <strong>Action Type:</strong> <span class="badge">${log.action_type}</span>
                </div>
                <div class="detail-row">
                    <strong>Table Affected:</strong> ${log.table_affected || 'N/A'}
                </div>
                <div class="detail-row">
                    <strong>Record ID:</strong> ${log.record_id || 'N/A'}
                </div>
                <div class="detail-row">
                    <strong>IP Address:</strong> ${log.ip_address}
                </div>
                <div class="detail-row">
                    <strong>User Agent:</strong> <small>${navigator.userAgent}</small>
                </div>
                <div class="detail-row">
                    <strong>Old Values:</strong>
                    <pre class="json-view">${log.old_value ? JSON.stringify(JSON.parse(log.old_value), null, 2) : 'N/A'}</pre>
                </div>
                <div class="detail-row">
                    <strong>New Values:</strong>
                    <pre class="json-view">${log.new_value ? JSON.stringify(JSON.parse(log.new_value), null, 2) : 'N/A'}</pre>
                </div>
            </div>
        `;
        
        document.getElementById('logDetailsModal').style.display = 'block';
    }
    
    // Close modal
    function closeModal() {
        document.getElementById('logDetailsModal').style.display = 'none';
    }
    
    // Export logs
    function exportLogs() {
        const params = new URLSearchParams(window.location.search);
        window.open('export-audit-logs.php?' + params.toString(), '_blank');
    }
    
    // Clear old logs
    function clearOldLogs() {
        if (confirm('Delete audit logs older than 90 days? This action cannot be undone.')) {
            window.location.href = 'clear-audit-logs.php';
        }
    }
    
    // Create backup
    function createBackup() {
        if (confirm('Create a new database backup? This may take a few moments.')) {
            window.location.href = 'create-backup.php';
        }
    }
    
    // Restore backup
    function restoreBackup() {
        alert('Backup restore functionality would be implemented here.');
        // window.location.href = 'restore-backup.php';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('logDetailsModal');
        if (event.target === modal) {
            closeModal();
        }
    };
    </script>
    
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Security Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card.security {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #007bff;
        }
        
        .stat-card.security .stat-icon {
            color: #007bff;
        }
        
        /* Charts Section */
        .charts-section {
            margin-bottom: 30px;
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 300px;
        }
        
        /* Top Actions */
        .top-actions-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .top-actions-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .action-rank {
            width: 30px;
            height: 30px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .action-info {
            flex: 1;
        }
        
        .action-name {
            font-weight: bold;
            color: #333;
        }
        
        .action-count {
            font-size: 12px;
            color: #6c757d;
        }
        
        .action-bar {
            width: 200px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .action-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            border-radius: 4px;
        }
        
        /* Audit Table */
        .table-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .audit-table th {
            background: #343a40;
            color: white;
        }
        
        .timestamp {
            font-family: monospace;
            font-size: 12px;
        }
        
        .user-info {
            font-size: 14px;
        }
        
        .user-name {
            font-weight: bold;
            color: #333;
        }
        
        .user-role {
            font-size: 11px;
            color: #6c757d;
        }
        
        .action-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .action-login { background: #d4edda; color: #155724; }
        .action-create { background: #cce5ff; color: #004085; }
        .action-update { background: #fff3cd; color: #856404; }
        .action-delete { background: #f8d7da; color: #721c24; }
        .action-approve { background: #d1ecf1; color: #0c5460; }
        .action-reject { background: #f8d7da; color: #721c24; }
        
        .ip-address {
            font-family: monospace;
            font-size: 12px;
            color: #6c757d;
        }
        
        .ip-address i {
            margin-right: 5px;
        }
        
        /* Security Section */
        .security-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .security-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .security-card-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .security-card-header h4 {
            margin: 0;
            color: #495057;
        }
        
        .security-card-body {
            padding: 15px;
        }
        
        .security-table, .health-table {
            width: 100%;
            font-size: 13px;
        }
        
        .security-table th, .health-table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .health-indicator {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .healthy { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .critical { background: #f8d7da; color: #721c24; }
        
        .backup-info {
            margin-bottom: 15px;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .backup-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .backup-label {
            font-weight: bold;
            color: #495057;
        }
        
        .backup-value {
            color: #6c757d;
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            width: 80%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 20px;
            background: #343a40;
            color: white;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            text-align: right;
        }
        
        .log-details {
            font-size: 14px;
        }
        
        .detail-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .detail-row strong {
            display: block;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .json-view {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            .security-grid {
                grid-template-columns: 1fr;
            }
            
            .action-bar {
                width: 100px;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</body>
</html>

<?php
// Health check functions
function checkDatabaseHealth() {
    global $pdo;
    try {
        $pdo->query("SELECT 1");
        return ['status' => 'healthy', 'message' => 'Connected successfully'];
    } catch (Exception $e) {
        return ['status' => 'critical', 'message' => 'Connection failed: ' . $e->getMessage()];
    }
}

function checkUploadDirectory() {
    $upload_path = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../uploads/';
    
    if (!is_dir($upload_path)) {
        return ['status' => 'critical', 'message' => 'Upload directory does not exist'];
    }
    
    if (!is_writable($upload_path)) {
        return ['status' => 'warning', 'message' => 'Upload directory not writable'];
    }
    
    // Check subdirectories
    $subdirs = ['ids', 'cottages', 'payments'];
    foreach ($subdirs as $subdir) {
        $path = $upload_path . $subdir;
        if (!is_dir($path) || !is_writable($path)) {
            return ['status' => 'warning', 'message' => "Subdirectory '$subdir' issues"];
        }
    }
    
    return ['status' => 'healthy', 'message' => 'All directories OK'];
}

function checkSessionHealth() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return ['status' => 'healthy', 'message' => 'Sessions active'];
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        return ['status' => 'warning', 'message' => 'Sessions not started'];
    }
    
    return ['status' => 'critical', 'message' => 'Session disabled'];
}

function checkPHPVersion() {
    $current = phpversion();
    $required = '7.4';
    
    if (version_compare($current, $required, '>=')) {
        return ['status' => 'healthy', 'message' => "PHP $current"];
    }
    
    return ['status' => 'warning', 'message' => "PHP $current - Upgrade to $required+"];
}

function checkSecurityHeaders() {
    $headers = headers_list();
    $security_headers = [
        'X-Frame-Options',
        'X-Content-Type-Options',
        'X-XSS-Protection',
        'Strict-Transport-Security'
    ];
    
    $missing = [];
    foreach ($security_headers as $header) {
        $found = false;
        foreach ($headers as $h) {
            if (stripos($h, $header) === 0) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missing[] = $header;
        }
    }
    
    if (empty($missing)) {
        return ['status' => 'healthy', 'message' => 'All security headers present'];
    }
    
    return ['status' => 'warning', 'message' => 'Missing: ' . implode(', ', $missing)];
}

function getBackupInfo() {
    $backup_dir = __DIR__ . '/../backups/';
    $info = [
        'last_backup' => null,
        'size' => '0 KB',
        'count' => 0
    ];
    
    if (!is_dir($backup_dir)) {
        return $info;
    }
    
    $files = glob($backup_dir . '*.sql');
    $info['count'] = count($files);
    
    if ($info['count'] > 0) {
        // Get most recent backup
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $last_file = $files[0];
        $info['last_backup'] = date('Y-m-d H:i:s', filemtime($last_file));
        
        // Calculate total size
        $total_size = 0;
        foreach ($files as $file) {
            $total_size += filesize($file);
        }
        
        if ($total_size < 1024) {
            $info['size'] = $total_size . ' B';
        } elseif ($total_size < 1048576) {
            $info['size'] = round($total_size / 1024, 2) . ' KB';
        } else {
            $info['size'] = round($total_size / 1048576, 2) . ' MB';
        }
    }
    
    return $info;
}
?>