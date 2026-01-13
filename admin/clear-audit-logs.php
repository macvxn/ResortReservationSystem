<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

// Confirm this is a POST request for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['confirm'])) {
    header("Location: audit-logs.php");
    exit();
}

$days_to_keep = 90; // Keep logs for 90 days
$cutoff_date = date('Y-m-d', strtotime("-$days_to_keep days"));

try {
    $pdo->beginTransaction();
    
    // Count logs to be deleted
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) < ?");
    $count_stmt->execute([$cutoff_date]);
    $count = $count_stmt->fetch()['count'];
    
    if ($count === 0) {
        $_SESSION['info'] = "No old logs to delete.";
        header("Location: audit-logs.php");
        exit();
    }
    
    // Delete old logs
    $delete_stmt = $pdo->prepare("DELETE FROM audit_logs WHERE DATE(created_at) < ?");
    $delete_stmt->execute([$cutoff_date]);
    $deleted = $delete_stmt->rowCount();
    
    // Also clean up old failed login attempts
    $failed_stmt = $pdo->prepare("DELETE FROM login_attempts WHERE DATE(attempted_at) < ?");
    $failed_stmt->execute([$cutoff_date]);
    $failed_deleted = $failed_stmt->rowCount();
    
    // Log the cleanup action
    logAction($_SESSION['user_id'], 'CLEANUP_AUDIT_LOGS', 'audit_logs', null, json_encode([
        'audit_logs_deleted' => $deleted,
        'failed_logins_deleted' => $failed_deleted,
        'cutoff_date' => $cutoff_date
    ]));
    
    $pdo->commit();
    
    $_SESSION['success'] = "Cleaned up $deleted audit logs and $failed_deleted failed login attempts older than $cutoff_date.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to clean up logs: " . $e->getMessage();
}

header("Location: audit-logs.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Audit Logs - Confirmation</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container" style="max-width: 600px;">
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <i class="fas fa-trash-alt fa-4x"></i>
            </div>
            
            <h2>Cleanup Audit Logs</h2>
            
            <div class="confirmation-details">
                <p>This action will permanently delete audit logs and failed login attempts older than <strong>90 days</strong>.</p>
                
                <?php
                // Get counts
                $cutoff_date = date('Y-m-d', strtotime("-90 days"));
                $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) < ?");
                $count_stmt->execute([$cutoff_date]);
                $audit_count = $count_stmt->fetch()['count'];
                
                $failed_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE DATE(attempted_at) < ?");
                $failed_stmt->execute([$cutoff_date]);
                $failed_count = $failed_stmt->fetch()['count'];
                ?>
                
                <div class="count-summary">
                    <div class="count-item">
                        <span class="count-label">Audit Logs to Delete:</span>
                        <span class="count-value"><?= number_format($audit_count) ?></span>
                    </div>
                    <div class="count-item">
                        <span class="count-label">Failed Logins to Delete:</span>
                        <span class="count-value"><?= number_format($failed_count) ?></span>
                    </div>
                    <div class="count-item total">
                        <span class="count-label">Total Records to Delete:</span>
                        <span class="count-value"><?= number_format($audit_count + $failed_count) ?></span>
                    </div>
                </div>
                
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone. Deleted records will be permanently removed from the database.
                </div>
            </div>
            
            <div class="confirmation-actions">
                <form method="POST">
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i> Confirm Cleanup
                    </button>
                    <a href="audit-logs.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .confirmation-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .confirmation-icon {
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .confirmation-details {
            margin: 30px 0;
            text-align: left;
        }
        
        .count-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .count-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .count-item:last-child {
            border-bottom: none;
        }
        
        .count-item.total {
            font-weight: bold;
            color: #dc3545;
            font-size: 1.1em;
        }
        
        .count-label {
            color: #495057;
        }
        
        .count-value {
            font-weight: bold;
            color: #333;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .warning-box i {
            margin-right: 10px;
        }
        
        .confirmation-actions {
            margin-top: 30px;
        }
        
        .btn-lg {
            padding: 15px 30px;
            font-size: 16px;
        }
    </style>
</body>
</html>