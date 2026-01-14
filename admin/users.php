<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)$_POST['user_id'];
    
    // Prevent admin from blocking/deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot perform this action on your own account";
    } else {
        
        if ($action == 'block') {
            $reason = clean($_POST['block_reason'] ?? '');
            
            if (empty($reason)) {
                $error = "Please provide a reason for blocking this user";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET status = 'blocked',
                        blocked_by = ?,
                        blocked_at = NOW(),
                        block_reason = ?
                    WHERE user_id = ? AND role = 'user'
                ");
                
                if ($stmt->execute([$_SESSION['user_id'], $reason, $user_id])) {
                    logAction($_SESSION['user_id'], 'user_blocked', 'users', $user_id);
                    $success = "User has been blocked successfully";
                } else {
                    $error = "Failed to block user";
                }
            }
            
        } elseif ($action == 'unblock') {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status = 'active',
                    blocked_by = NULL,
                    blocked_at = NULL,
                    block_reason = NULL
                WHERE user_id = ? AND role = 'user'
            ");
            
            if ($stmt->execute([$user_id])) {
                logAction($_SESSION['user_id'], 'user_unblocked', 'users', $user_id);
                $success = "User has been unblocked successfully";
            } else {
                $error = "Failed to unblock user";
            }
            
        } elseif ($action == 'delete') {
            $confirm = $_POST['confirm_delete'] ?? '';
            
            if ($confirm !== 'DELETE') {
                $error = "Please type 'DELETE' to confirm deletion";
            } else {
                // Soft delete - we'll keep the data but mark as deleted
                $stmt = $pdo->prepare("
                    DELETE FROM users 
                    WHERE user_id = ? AND role = 'user'
                ");
                
                if ($stmt->execute([$user_id])) {
                    logAction($_SESSION['user_id'], 'user_deleted', 'users', $user_id);
                    $success = "User account has been deleted permanently";
                } else {
                    $error = "Failed to delete user";
                }
            }
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$query = "
    SELECT u.*, up.full_name, up.phone_number, up.verification_status,
           (SELECT COUNT(*) FROM reservations WHERE user_id = u.user_id) as total_reservations
    FROM users u
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.role = 'user'
";

if ($filter == 'active') {
    $query .= " AND u.status = 'active'";
} elseif ($filter == 'blocked') {
    $query .= " AND u.status = 'blocked'";
} elseif ($filter == 'verified') {
    $query .= " AND up.verification_status = 'verified'";
} elseif ($filter == 'unverified') {
    $query .= " AND up.verification_status IN ('unverified', 'pending_verification')";
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users
    FROM users
    WHERE role = 'user'
");
$stmt->execute();
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            color: white;
        }
        .admin-nav h2 { color: white; margin: 0 0 10px 0; }
        .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            font-size: 14px;
        }
        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #eee;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 10px 20px;
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }
        .filter-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .user-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
        .user-card.blocked {
            border-color: #dc3545;
            background: #fff5f5;
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .user-info {
            display: grid;
            gap: 8px;
            margin: 10px 0;
            font-size: 14px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 8px 15px;
            font-size: 14px;
            width: auto;
            display: inline-block;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }
        .close-modal:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-nav">
            <h2>👥 User Management</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="verify-users.php">Verify Users</a>
                <a href="users.php" class="active">Manage Users</a>
                <a href="reservations.php">Reservations</a>
                <a href="cottages.php">Cottages</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;">
                    <?php echo $stats['active_users']; ?>
                </div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;">
                    <?php echo $stats['blocked_users']; ?>
                </div>
                <div class="stat-label">Blocked Users</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                All Users
            </a>
            <a href="?filter=active" class="filter-tab <?php echo $filter == 'active' ? 'active' : ''; ?>">
                Active
            </a>
            <a href="?filter=blocked" class="filter-tab <?php echo $filter == 'blocked' ? 'active' : ''; ?>">
                Blocked
            </a>
            <a href="?filter=verified" class="filter-tab <?php echo $filter == 'verified' ? 'active' : ''; ?>">
                Verified
            </a>
            <a href="?filter=unverified" class="filter-tab <?php echo $filter == 'unverified' ? 'active' : ''; ?>">
                Unverified
            </a>
        </div>

        <!-- User List -->
        <h3>User List (<?php echo count($users); ?>)</h3>

        <?php if (empty($users)): ?>
            <div class="card">
                <p style="text-align: center; color: #666;">No users found</p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card <?php echo $user['status'] == 'blocked' ? 'blocked' : ''; ?>">
                    <div class="user-header">
                        <div>
                            <strong><?php echo htmlspecialchars($user['full_name'] ?: 'No name set'); ?></strong>
                            <br>
                            <small style="color: #666;"><?php echo htmlspecialchars($user['email']); ?></small>
                        </div>
                        <span class="status-badge status-<?php echo $user['status']; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>

                    <div class="user-info">
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span><?php echo htmlspecialchars($user['phone_number'] ?: 'Not set'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Verification:</span>
                            <span><?php echo getVerificationBadge($user['verification_status'] ?? 'unverified'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Reservations:</span>
                            <span><?php echo $user['total_reservations']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Registered:</span>
                            <span><?php echo formatDateTime($user['created_at']); ?></span>
                        </div>
                        
                        <?php if ($user['status'] == 'blocked'): ?>
                            <div class="info-row">
                                <span class="info-label">Blocked:</span>
                                <span><?php echo formatDateTime($user['blocked_at']); ?></span>
                            </div>
                            <?php if ($user['block_reason']): ?>
                                <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 5px;">
                                    <strong>Block Reason:</strong><br>
                                    <?php echo htmlspecialchars($user['block_reason']); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="action-buttons">
                        <?php if ($user['status'] == 'active'): ?>
                            <button 
                                class="btn btn-danger btn-small"
                                onclick="openBlockModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')"
                            >
                                🚫 Block User
                            </button>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="action" value="unblock">
                                <button 
                                    type="submit" 
                                    class="btn btn-success btn-small"
                                    onclick="return confirm('Unblock this user?')"
                                >
                                    ✓ Unblock User
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <button 
                            class="btn btn-secondary btn-small"
                            onclick="openDeleteModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')"
                        >
                            🗑️ Delete Account
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Block User Modal -->
    <div id="blockModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('blockModal')">&times;</span>
            <h3>Block User</h3>
            <p>Block user: <strong id="blockUserEmail"></strong></p>
            
            <form method="POST" id="blockForm">
                <input type="hidden" name="user_id" id="blockUserId">
                <input type="hidden" name="action" value="block">
                
                <div class="form-group">
                    <label>Reason for blocking <span style="color: red;">*</span></label>
                    <textarea 
                        name="block_reason" 
                        rows="4" 
                        required
                        placeholder="e.g., Violating terms of service, Fraudulent activity, etc."
                    ></textarea>
                </div>
                
                <button type="submit" class="btn btn-danger">Block User</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('blockModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteModal')">&times;</span>
            <h3>⚠️ Delete User Account</h3>
            <p>Delete user: <strong id="deleteUserEmail"></strong></p>
            
            <div class="alert alert-danger">
                <strong>Warning:</strong> This action is permanent and cannot be undone!
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>User profile will be deleted</li>
                    <li>User cannot login anymore</li>
                    <li>Reservations will remain for records</li>
                </ul>
            </div>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="user_id" id="deleteUserId">
                <input type="hidden" name="action" value="delete">
                
                <div class="form-group">
                    <label>Type <strong>DELETE</strong> to confirm</label>
                    <input 
                        type="text" 
                        name="confirm_delete" 
                        placeholder="Type DELETE"
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-danger">Delete Permanently</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openBlockModal(userId, email) {
            document.getElementById('blockUserId').value = userId;
            document.getElementById('blockUserEmail').textContent = email;
            document.getElementById('blockModal').style.display = 'block';
        }

        function openDeleteModal(userId, email) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserEmail').textContent = email;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>