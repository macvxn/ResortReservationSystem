<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

// Handle delete action
if (isset($_GET['delete'])) {
    $cottage_id = intval($_GET['delete']);
    
    try {
        // Soft delete - set is_active to FALSE
        $stmt = $pdo->prepare("UPDATE cottages SET is_active = FALSE WHERE cottage_id = ?");
        $stmt->execute([$cottage_id]);
        
        // Log action
        logAction($_SESSION['user_id'], 'DELETE_COTTAGE', 'cottages', $cottage_id);
        
        $_SESSION['success'] = "Cottage disabled successfully.";
        header("Location: cottages.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete cottage.";
    }
}

// Handle toggle active status
if (isset($_GET['toggle'])) {
    $cottage_id = intval($_GET['toggle']);
    
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM cottages WHERE cottage_id = ?");
        $stmt->execute([$cottage_id]);
        $cottage = $stmt->fetch();
        
        if ($cottage) {
            $new_status = $cottage['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE cottages SET is_active = ? WHERE cottage_id = ?");
            $stmt->execute([$new_status, $cottage_id]);
            
            $action = $new_status ? 'ENABLE_COTTAGE' : 'DISABLE_COTTAGE';
            logAction($_SESSION['user_id'], $action, 'cottages', $cottage_id);
            
            $_SESSION['success'] = "Cottage status updated.";
            header("Location: cottages.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update cottage status.";
    }
}

// Get all cottages
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(ci.image_id) as image_count,
           COUNT(r.reservation_id) as reservation_count
    FROM cottages c
    LEFT JOIN cottage_images ci ON c.cottage_id = ci.cottage_id
    LEFT JOIN reservations r ON c.cottage_id = r.cottage_id
    GROUP BY c.cottage_id
    ORDER BY c.is_active DESC, c.cottage_name ASC
");
$stmt->execute();
$cottages = $stmt->fetchAll();

// Get stats
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive
    FROM cottages
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cottage Management - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <h2><i class="fas fa-home"></i> Cottage Management</h2>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <a href="cottages.php">
                    <h3><?= $stats['total'] ?? 0 ?></h3>
                    <p>Total Cottages</p>
                </a>
            </div>
            <div class="stat-card active">
                <a href="cottages.php?filter=active">
                    <h3 style="color: #28a745;"><?= $stats['active'] ?? 0 ?></h3>
                    <p>Active Cottages</p>
                </a>
            </div>
            <div class="stat-card">
                <a href="cottages.php?filter=inactive">
                    <h3 style="color: #dc3545;"><?= $stats['inactive'] ?? 0 ?></h3>
                    <p>Inactive Cottages</p>
                </a>
            </div>
            <div class="stat-card">
                <a href="add-cottage.php">
                    <h3 style="color: #007bff;"><i class="fas fa-plus"></i></h3>
                    <p>Add New Cottage</p>
                </a>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-bar">
            <a href="add-cottage.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Cottage
            </a>
            <a href="bulk-upload.php" class="btn btn-secondary">
                <i class="fas fa-upload"></i> Bulk Upload
            </a>
            <a href="export-cottages.php" class="btn btn-info">
                <i class="fas fa-download"></i> Export List
            </a>
        </div>
        
        <!-- Cottages Table -->
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Cottage</th>
                        <th>Details</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Stats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cottages)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No cottages found. <a href="add-cottage.php">Add your first cottage</a></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($cottages as $cottage): ?>
                        <tr>
                            <td>
                                <div class="cottage-info">
                                    <div class="cottage-name">
                                        <strong><?= htmlspecialchars($cottage['cottage_name']) ?></strong>
                                        <?php if (!$cottage['is_active']): ?>
                                        <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="cottage-desc">
                                        <?= htmlspecialchars(substr($cottage['description'] ?? 'No description', 0, 100)) ?>...
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="cottage-details">
                                    <div><i class="fas fa-users"></i> Capacity: <?= $cottage['capacity'] ?> guests</div>
                                    <div><i class="fas fa-calendar"></i> Created: <?= formatDate($cottage['created_at']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="cottage-price">
                                    <strong>₱<?= number_format($cottage['price_per_night'], 2) ?></strong>
                                    <small>per night</small>
                                </div>
                            </td>
                            <td>
                                <?php if ($cottage['is_active']): ?>
                                <span class="status-badge status-active">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                                <?php else: ?>
                                <span class="status-badge status-inactive">
                                    <i class="fas fa-times-circle"></i> Inactive
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="cottage-stats">
                                    <div>
                                        <i class="fas fa-image"></i>
                                        <?= $cottage['image_count'] ?> images
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-check"></i>
                                        <?= $cottage['reservation_count'] ?> bookings
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit-cottage.php?id=<?= $cottage['cottage_id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <a href="cottage-images.php?id=<?= $cottage['cottage_id'] ?>" 
                                       class="btn btn-sm btn-secondary" 
                                       title="Manage Images">
                                        <i class="fas fa-images"></i>
                                    </a>
                                    
                                    <a href="?toggle=<?= $cottage['cottage_id'] ?>" 
                                       class="btn btn-sm <?= $cottage['is_active'] ? 'btn-warning' : 'btn-success' ?>" 
                                       title="<?= $cottage['is_active'] ? 'Disable' : 'Enable' ?>"
                                       onclick="return confirm('<?= $cottage['is_active'] ? 'Disable this cottage?' : 'Enable this cottage?' ?>')">
                                        <i class="fas <?= $cottage['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </a>
                                    
                                    <a href="?delete=<?= $cottage['cottage_id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure? This will disable the cottage but keep existing reservations.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination (if needed) -->
        <div class="pagination">
            <p>Showing <?= count($cottages) ?> of <?= $stats['total'] ?? 0 ?> cottages</p>
        </div>
    </div>
    
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.active {
            border: 2px solid #007bff;
        }
        
        .stat-card a {
            text-decoration: none;
            color: inherit;
        }
        
        .stat-card h3 {
            font-size: 2.5em;
            margin: 0;
        }
        
        .stat-card p {
            margin: 10px 0 0 0;
            color: #6c757d;
        }
        
        .action-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .admin-table th {
            background: #343a40;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .admin-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .admin-table tr:hover {
            background: #f8f9fa;
        }
        
        .cottage-info {
            max-width: 250px;
        }
        
        .cottage-name {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .cottage-desc {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .cottage-details {
            font-size: 14px;
            color: #6c757d;
        }
        
        .cottage-details div {
            margin-bottom: 5px;
        }
        
        .cottage-details i {
            width: 20px;
            color: #007bff;
        }
        
        .cottage-price {
            text-align: center;
        }
        
        .cottage-price strong {
            display: block;
            font-size: 1.2em;
            color: #28a745;
        }
        
        .cottage-price small {
            color: #6c757d;
            font-size: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .cottage-stats {
            font-size: 14px;
        }
        
        .cottage-stats div {
            margin-bottom: 5px;
        }
        
        .cottage-stats i {
            width: 20px;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .text-center {
            text-align: center;
        }
        
        .pagination {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .admin-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>