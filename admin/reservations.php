<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$sql = "
    SELECT r.*, 
           c.cottage_name,
           c.price_per_night,
           u.email,
           up.full_name,
           pp.receipt_image_path,
           pp.reference_number,
           pp.uploaded_at as payment_date,
           u2.email as reviewed_by_email
    FROM reservations r
    JOIN cottages c ON r.cottage_id = c.cottage_id
    JOIN users u ON r.user_id = u.user_id
    JOIN user_profiles up ON u.user_id = up.user_id
    LEFT JOIN payment_proofs pp ON r.reservation_id = pp.reservation_id
    LEFT JOIN users u2 ON r.reviewed_by = u2.user_id
    WHERE 1=1
";

$params = [];

// Status filter
if ($status !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $status;
}

// Search filter
if (!empty($search)) {
    $sql .= " AND (u.email LIKE ? OR up.full_name LIKE ? OR c.cottage_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Date range filter
if (!empty($date_from)) {
    $sql .= " AND r.check_in_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND r.check_out_date <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Counts for stats
$counts_stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN status = 'pending_admin_review' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        COUNT(*) as total
    FROM reservations
");
$counts_stmt->execute();
$counts = $counts_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <h2><i class="fas fa-calendar-check"></i> Reservation Management</h2>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card <?= $status === 'all' ? 'active' : '' ?>">
                <a href="?status=all">
                    <h3><?= $counts['total'] ?? 0 ?></h3>
                    <p>Total Reservations</p>
                </a>
            </div>
            <div class="stat-card <?= $status === 'pending_admin_review' ? 'active' : '' ?>">
                <a href="?status=pending_admin_review">
                    <h3 style="color: #ffc107;"><?= $counts['pending'] ?? 0 ?></h3>
                    <p>Pending Review</p>
                </a>
            </div>
            <div class="stat-card <?= $status === 'approved' ? 'active' : '' ?>">
                <a href="?status=approved">
                    <h3 style="color: #28a745;"><?= $counts['approved'] ?? 0 ?></h3>
                    <p>Approved</p>
                </a>
            </div>
            <div class="stat-card <?= $status === 'rejected' ? 'active' : '' ?>">
                <a href="?status=rejected">
                    <h3 style="color: #dc3545;"><?= $counts['rejected'] ?? 0 ?></h3>
                    <p>Rejected</p>
                </a>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" class="form-control">
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending_admin_review" <?= $status === 'pending_admin_review' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Search:</label>
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search by name, email, cottage..."
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>From:</label>
                        <input type="date" 
                               name="date_from" 
                               value="<?= htmlspecialchars($date_from) ?>"
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>To:</label>
                        <input type="date" 
                               name="date_to" 
                               value="<?= htmlspecialchars($date_to) ?>"
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="reservations.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Reservations Table -->
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Guest</th>
                        <th>Cottage</th>
                        <th>Dates</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No reservations found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td>
                                <strong>#<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?></strong>
                                <small><?= formatDateTime($reservation['created_at']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($reservation['full_name']) ?></strong>
                                <small><?= htmlspecialchars($reservation['email']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($reservation['cottage_name']) ?>
                                <small><?= $reservation['total_nights'] ?> night(s)</small>
                            </td>
                            <td>
                                <?= formatDate($reservation['check_in_date']) ?>
                                <small>to <?= formatDate($reservation['check_out_date']) ?></small>
                            </td>
                            <td>
                                <strong>₱<?= number_format($reservation['total_price'], 2) ?></strong>
                            </td>
                            <td>
                                <?php if ($reservation['receipt_image_path']): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Paid
                                </span>
                                <br>
                                <small>Ref: <?= htmlspecialchars($reservation['reference_number']) ?></small>
                                <?php else: ?>
                                <span class="badge badge-warning">
                                    <i class="fas fa-clock"></i> No Payment
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= getReservationBadge($reservation['status']) ?>
                                <?php if ($reservation['status'] !== 'pending_admin_review'): ?>
                                <br>
                                <small>By: <?= $reservation['reviewed_by_email'] ?? 'Admin' ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="reservation-details.php?id=<?= $reservation['reservation_id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($reservation['status'] === 'pending_admin_review'): ?>
                                    <a href="approve-reservation.php?id=<?= $reservation['reservation_id'] ?>" 
                                       class="btn btn-sm btn-success" 
                                       title="Approve"
                                       onclick="return confirm('Approve this reservation?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    
                                    <a href="reject-reservation.php?id=<?= $reservation['reservation_id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Reject"
                                       onclick="return confirm('Reject this reservation?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Export Options -->
        <div class="export-section">
            <h4><i class="fas fa-download"></i> Export Data</h4>
            <div class="export-buttons">
                <a href="export-reservations.php?type=csv&status=<?= $status ?>" 
                   class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </a>
                <a href="export-reservations.php?type=pdf&status=<?= $status ?>" 
                   class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </a>
                <a href="print-reservations.php?status=<?= $status ?>" 
                   class="btn btn-secondary" 
                   target="_blank">
                    <i class="fas fa-print"></i> Print View
                </a>
            </div>
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
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .filter-form .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-form .form-group {
            flex: 1;
            min-width: 150px;
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
        }
        
        .admin-table tr:hover {
            background: #f8f9fa;
        }
        
        .admin-table tr:last-child td {
            border-bottom: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .export-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .export-buttons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .text-center {
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .filter-form .form-row {
                flex-direction: column;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
            
            .export-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>