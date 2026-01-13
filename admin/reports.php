<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

// Get date range parameters
$report_type = $_GET['type'] ?? 'daily';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$cottage_id = $_GET['cottage_id'] ?? 'all';

// Validate dates
if (empty($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
if (empty($date_to)) $date_to = date('Y-m-d');

// Get report data based on type
switch ($report_type) {
    case 'daily':
        $report_data = getDailyReport($date_from, $date_to, $cottage_id);
        $chart_data = formatDailyChartData($report_data);
        break;
    case 'weekly':
        $report_data = getWeeklyReport($date_from, $date_to, $cottage_id);
        $chart_data = formatWeeklyChartData($report_data);
        break;
    case 'monthly':
        $report_data = getMonthlyReport($date_from, $date_to, $cottage_id);
        $chart_data = formatMonthlyChartData($report_data);
        break;
    case 'cottage':
        $report_data = getCottagePerformanceReport($date_from, $date_to);
        $chart_data = formatCottageChartData($report_data);
        break;
    case 'revenue':
        $report_data = getRevenueReport($date_from, $date_to, $cottage_id);
        $chart_data = formatRevenueChartData($report_data);
        break;
    default:
        $report_type = 'daily';
        $report_data = getDailyReport($date_from, $date_to, $cottage_id);
        $chart_data = formatDailyChartData($report_data);
}

// Get cottage list for filter
$cottages = $pdo->query("SELECT cottage_id, cottage_name FROM cottages WHERE is_active = TRUE ORDER BY cottage_name")->fetchAll();

// Get summary stats
$summary = getSummaryStats($date_from, $date_to, $cottage_id);

// Report functions
function getDailyReport($date_from, $date_to, $cottage_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            DATE(r.created_at) as report_date,
            COUNT(r.reservation_id) as total_reservations,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_reservations,
            SUM(CASE WHEN r.status = 'pending_admin_review' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(r.total_price) as total_revenue,
            COUNT(DISTINCT r.user_id) as unique_customers
        FROM reservations r
        WHERE DATE(r.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$date_from, $date_to];
    
    if ($cottage_id !== 'all') {
        $sql .= " AND r.cottage_id = ?";
        $params[] = $cottage_id;
    }
    
    $sql .= " GROUP BY DATE(r.created_at) ORDER BY report_date";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getWeeklyReport($date_from, $date_to, $cottage_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            YEARWEEK(r.created_at) as week_number,
            MIN(DATE(r.created_at)) as week_start,
            MAX(DATE(r.created_at)) as week_end,
            COUNT(r.reservation_id) as total_reservations,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(r.total_price) as total_revenue,
            AVG(r.total_price) as avg_revenue_per_reservation
        FROM reservations r
        WHERE DATE(r.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$date_from, $date_to];
    
    if ($cottage_id !== 'all') {
        $sql .= " AND r.cottage_id = ?";
        $params[] = $cottage_id;
    }
    
    $sql .= " GROUP BY YEARWEEK(r.created_at) ORDER BY week_start";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getMonthlyReport($date_from, $date_to, $cottage_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            DATE_FORMAT(r.created_at, '%Y-%m') as month,
            COUNT(r.reservation_id) as total_reservations,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(r.total_price) as total_revenue,
            COUNT(DISTINCT r.user_id) as unique_customers,
            AVG(r.total_nights) as avg_nights_per_reservation
        FROM reservations r
        WHERE DATE(r.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$date_from, $date_to];
    
    if ($cottage_id !== 'all') {
        $sql .= " AND r.cottage_id = ?";
        $params[] = $cottage_id;
    }
    
    $sql .= " GROUP BY DATE_FORMAT(r.created_at, '%Y-%m') ORDER BY month";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCottagePerformanceReport($date_from, $date_to) {
    global $pdo;
    
    $sql = "
        SELECT 
            c.cottage_id,
            c.cottage_name,
            c.capacity,
            c.price_per_night,
            COUNT(r.reservation_id) as total_reservations,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(r.total_price) as total_revenue,
            SUM(r.total_nights) as total_nights_booked,
            AVG(r.total_price) as avg_revenue_per_booking,
            (COUNT(r.reservation_id) * 100.0 / (SELECT COUNT(*) FROM reservations WHERE DATE(created_at) BETWEEN ? AND ?)) as market_share
        FROM cottages c
        LEFT JOIN reservations r ON c.cottage_id = r.cottage_id AND DATE(r.created_at) BETWEEN ? AND ?
        WHERE c.is_active = TRUE
        GROUP BY c.cottage_id, c.cottage_name, c.capacity, c.price_per_night
        ORDER BY total_revenue DESC
    ";
    
    $params = [$date_from, $date_to, $date_from, $date_to];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getRevenueReport($date_from, $date_to, $cottage_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            DATE(r.created_at) as report_date,
            SUM(r.total_price) as daily_revenue,
            SUM(SUM(r.total_price)) OVER (ORDER BY DATE(r.created_at)) as cumulative_revenue,
            COUNT(r.reservation_id) as daily_bookings,
            AVG(r.total_price) as avg_booking_value
        FROM reservations r
        WHERE r.status = 'approved'
        AND DATE(r.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$date_from, $date_to];
    
    if ($cottage_id !== 'all') {
        $sql .= " AND r.cottage_id = ?";
        $params[] = $cottage_id;
    }
    
    $sql .= " GROUP BY DATE(r.created_at) ORDER BY report_date";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getSummaryStats($date_from, $date_to, $cottage_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            COUNT(r.reservation_id) as total_reservations,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_reservations,
            SUM(CASE WHEN r.status = 'pending_admin_review' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(r.total_price) as total_revenue,
            AVG(r.total_price) as avg_revenue_per_booking,
            COUNT(DISTINCT r.user_id) as unique_customers,
            SUM(r.total_nights) as total_nights_booked,
            AVG(r.total_nights) as avg_nights_per_booking,
            MAX(r.total_price) as highest_booking_value,
            MIN(r.total_price) as lowest_booking_value
        FROM reservations r
        WHERE DATE(r.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$date_from, $date_to];
    
    if ($cottage_id !== 'all') {
        $sql .= " AND r.cottage_id = ?";
        $params[] = $cottage_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

// Chart data formatting functions
function formatDailyChartData($data) {
    $labels = [];
    $reservations = [];
    $revenue = [];
    
    foreach ($data as $row) {
        $labels[] = date('M j', strtotime($row['report_date']));
        $reservations[] = $row['total_reservations'];
        $revenue[] = $row['total_revenue'];
    }
    
    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Reservations',
                'data' => $reservations,
                'borderColor' => '#007bff',
                'backgroundColor' => 'rgba(0, 123, 255, 0.1)'
            ],
            [
                'label' => 'Revenue (₱)',
                'data' => $revenue,
                'borderColor' => '#28a745',
                'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                'yAxisID' => 'y1'
            ]
        ]
    ];
}

function formatCottageChartData($data) {
    $labels = [];
    $revenue = [];
    $reservations = [];
    
    foreach ($data as $row) {
        $labels[] = substr($row['cottage_name'], 0, 15) . '...';
        $revenue[] = $row['total_revenue'] ?? 0;
        $reservations[] = $row['total_reservations'] ?? 0;
    }
    
    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Revenue (₱)',
                'data' => $revenue,
                'backgroundColor' => '#007bff'
            ],
            [
                'label' => 'Reservations',
                'data' => $reservations,
                'backgroundColor' => '#28a745'
            ]
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
        
        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #007bff;">
                    <i class="fas fa-calendar-check fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($summary['total_reservations'] ?? 0) ?></h3>
                    <p>Total Reservations</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($summary['approved_reservations'] ?? 0) ?></h3>
                    <p>Approved Reservations</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3>₱<?= number_format($summary['total_revenue'] ?? 0, 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #17a2b8;">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($summary['unique_customers'] ?? 0) ?></h3>
                    <p>Unique Customers</p>
                </div>
            </div>
        </div>
        
        <!-- Report Filters -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Report Type:</label>
                        <select name="type" class="form-control" onchange="this.form.submit()">
                            <option value="daily" <?= $report_type === 'daily' ? 'selected' : '' ?>>Daily Report</option>
                            <option value="weekly" <?= $report_type === 'weekly' ? 'selected' : '' ?>>Weekly Report</option>
                            <option value="monthly" <?= $report_type === 'monthly' ? 'selected' : '' ?>>Monthly Report</option>
                            <option value="cottage" <?= $report_type === 'cottage' ? 'selected' : '' ?>>Cottage Performance</option>
                            <option value="revenue" <?= $report_type === 'revenue' ? 'selected' : '' ?>>Revenue Analysis</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cottage:</label>
                        <select name="cottage_id" class="form-control">
                            <option value="all" <?= $cottage_id === 'all' ? 'selected' : '' ?>>All Cottages</option>
                            <?php foreach ($cottages as $cottage): ?>
                            <option value="<?= $cottage['cottage_id'] ?>" <?= $cottage_id == $cottage['cottage_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cottage['cottage_name']) ?>
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Generate Report
                        </button>
                        <button type="button" onclick="printReport()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="export-report.php?type=<?= $report_type ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&cottage_id=<?= $cottage_id ?>" 
                           class="btn btn-success">
                            <i class="fas fa-download"></i> Export
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-container">
                <canvas id="reportChart"></canvas>
            </div>
        </div>
        
        <!-- Detailed Report Table -->
        <div class="report-table-section">
            <h3>
                <i class="fas fa-table"></i>
                <?= ucfirst($report_type) ?> Report Details
                <small><?= formatDate($date_from) ?> to <?= formatDate($date_to) ?></small>
            </h3>
            
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <?php if ($report_type === 'daily'): ?>
                            <th>Date</th>
                            <th>Total Reservations</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Rejected</th>
                            <th>Revenue</th>
                            <th>Unique Customers</th>
                            <?php elseif ($report_type === 'weekly'): ?>
                            <th>Week</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Total Reservations</th>
                            <th>Approved</th>
                            <th>Revenue</th>
                            <th>Avg per Booking</th>
                            <?php elseif ($report_type === 'monthly'): ?>
                            <th>Month</th>
                            <th>Total Reservations</th>
                            <th>Approved</th>
                            <th>Revenue</th>
                            <th>Unique Customers</th>
                            <th>Avg Nights</th>
                            <?php elseif ($report_type === 'cottage'): ?>
                            <th>Cottage</th>
                            <th>Capacity</th>
                            <th>Price/Night</th>
                            <th>Total Bookings</th>
                            <th>Approved</th>
                            <th>Revenue</th>
                            <th>Avg per Booking</th>
                            <th>Market Share</th>
                            <?php elseif ($report_type === 'revenue'): ?>
                            <th>Date</th>
                            <th>Daily Revenue</th>
                            <th>Cumulative Revenue</th>
                            <th>Daily Bookings</th>
                            <th>Avg Booking Value</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No data available for the selected period</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php if ($report_type === 'daily'): ?>
                                <td><?= formatDate($row['report_date']) ?></td>
                                <td><?= $row['total_reservations'] ?></td>
                                <td><?= $row['approved_reservations'] ?></td>
                                <td><?= $row['pending_reservations'] ?></td>
                                <td><?= $row['rejected_reservations'] ?></td>
                                <td>₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                                <td><?= $row['unique_customers'] ?></td>
                                
                                <?php elseif ($report_type === 'weekly'): ?>
                                <td>Week <?= $row['week_number'] % 100 ?></td>
                                <td><?= formatDate($row['week_start']) ?></td>
                                <td><?= formatDate($row['week_end']) ?></td>
                                <td><?= $row['total_reservations'] ?></td>
                                <td><?= $row['approved_reservations'] ?></td>
                                <td>₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                                <td>₱<?= number_format($row['avg_revenue_per_reservation'] ?? 0, 2) ?></td>
                                
                                <?php elseif ($report_type === 'monthly'): ?>
                                <td><?= date('F Y', strtotime($row['month'] . '-01')) ?></td>
                                <td><?= $row['total_reservations'] ?></td>
                                <td><?= $row['approved_reservations'] ?></td>
                                <td>₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                                <td><?= $row['unique_customers'] ?></td>
                                <td><?= number_format($row['avg_nights_per_reservation'] ?? 0, 1) ?></td>
                                
                                <?php elseif ($report_type === 'cottage'): ?>
                                <td><?= htmlspecialchars($row['cottage_name']) ?></td>
                                <td><?= $row['capacity'] ?></td>
                                <td>₱<?= number_format($row['price_per_night'], 2) ?></td>
                                <td><?= $row['total_reservations'] ?? 0 ?></td>
                                <td><?= $row['approved_reservations'] ?? 0 ?></td>
                                <td>₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                                <td>₱<?= number_format($row['avg_revenue_per_booking'] ?? 0, 2) ?></td>
                                <td><?= number_format($row['market_share'] ?? 0, 1) ?>%</td>
                                
                                <?php elseif ($report_type === 'revenue'): ?>
                                <td><?= formatDate($row['report_date']) ?></td>
                                <td>₱<?= number_format($row['daily_revenue'] ?? 0, 2) ?></td>
                                <td>₱<?= number_format($row['cumulative_revenue'] ?? 0, 2) ?></td>
                                <td><?= $row['daily_bookings'] ?? 0 ?></td>
                                <td>₱<?= number_format($row['avg_booking_value'] ?? 0, 2) ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Insights Section -->
        <div class="insights-section">
            <h3><i class="fas fa-lightbulb"></i> Key Insights</h3>
            <div class="insights-grid">
                <div class="insight-card">
                    <div class="insight-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="insight-content">
                        <h4>Top Performing Cottage</h4>
                        <p>
                            <?php
                            $top_cottage = getTopPerformingCottage($date_from, $date_to);
                            echo $top_cottage ? htmlspecialchars($top_cottage['cottage_name']) : 'No data';
                            ?>
                        </p>
                        <small>Highest revenue generator</small>
                    </div>
                </div>
                
                <div class="insight-card">
                    <div class="insight-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="insight-content">
                        <h4>Conversion Rate</h4>
                        <p>
                            <?php
                            $total = $summary['total_reservations'] ?? 1;
                            $approved = $summary['approved_reservations'] ?? 0;
                            echo number_format(($approved / $total) * 100, 1);
                            ?>%
                        </p>
                        <small>Approved vs Total Reservations</small>
                    </div>
                </div>
                
                <div class="insight-card">
                    <div class="insight-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="insight-content">
                        <h4>Peak Booking Day</h4>
                        <p>
                            <?php
                            $peak_day = getPeakBookingDay($date_from, $date_to);
                            echo $peak_day ? date('l', strtotime($peak_day['peak_day'])) : 'No data';
                            ?>
                        </p>
                        <small>Most popular day for bookings</small>
                    </div>
                </div>
                
                <div class="insight-card">
                    <div class="insight-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="insight-content">
                        <h4>Customer Retention</h4>
                        <p>
                            <?php
                            $retention = calculateCustomerRetention($date_from, $date_to);
                            echo number_format($retention, 1);
                            ?>%
                        </p>
                        <small>Repeat customer rate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Initialize Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('reportChart').getContext('2d');
        const chartData = <?= json_encode($chart_data) ?>;
        
        const reportChart = new Chart(ctx, {
            type: '<?= $report_type === "cottage" ? "bar" : "line" ?>',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Reservations'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (₱)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    });
    
    // Print report function
    function printReport() {
        window.open('print-report.php?type=<?= $report_type ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&cottage_id=<?= $cottage_id ?>', '_blank');
    }
    
    // Auto-refresh every 5 minutes for real-time updates
    setTimeout(function() {
        window.location.reload();
    }, 300000); // 5 minutes
    </script>
    
    <style>
        .admin-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Stats Grid */
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            flex-shrink: 0;
        }
        
        .stat-content h3 {
            margin: 0;
            font-size: 1.8em;
            color: #333;
        }
        
        .stat-content p {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Filter Section */
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
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
        
        /* Charts Section */
        .charts-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-container {
            height: 400px;
            position: relative;
        }
        
        /* Report Table */
        .report-table-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .report-table-section h3 {
            color: #495057;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .report-table-section h3 small {
            font-size: 14px;
            color: #6c757d;
            font-weight: normal;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .admin-table th {
            background: #343a40;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .admin-table tr:hover {
            background: #f8f9fa;
        }
        
        .admin-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Insights Section */
        .insights-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .insights-section h3 {
            color: #495057;
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .insight-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
        }
        
        .insight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .insight-icon {
            width: 50px;
            height: 50px;
            background: #e7f3ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .insight-icon i {
            font-size: 24px;
            color: #007bff;
        }
        
        .insight-content h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 16px;
        }
        
        .insight-content p {
            margin: 0;
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
        
        .insight-content small {
            color: #6c757d;
            font-size: 12px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-form .form-row {
                flex-direction: column;
            }
            
            .filter-form .form-group {
                width: 100%;
            }
            
            .insights-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</body>
</html>

<?php
// Additional helper functions
function getTopPerformingCottage($date_from, $date_to) {
    global $pdo;
    
    $sql = "
        SELECT c.cottage_name, SUM(r.total_price) as total_revenue
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.cottage_id
        WHERE r.status = 'approved'
        AND DATE(r.created_at) BETWEEN ? AND ?
        GROUP BY c.cottage_id
        ORDER BY total_revenue DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_from, $date_to]);
    return $stmt->fetch();
}

function getPeakBookingDay($date_from, $date_to) {
    global $pdo;
    
    $sql = "
        SELECT 
            DAYNAME(created_at) as peak_day,
            COUNT(*) as booking_count
        FROM reservations
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DAYNAME(created_at)
        ORDER BY booking_count DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_from, $date_to]);
    return $stmt->fetch();
}

function calculateCustomerRetention($date_from, $date_to) {
    global $pdo;
    
    // Get total unique customers
    $sql1 = "
        SELECT COUNT(DISTINCT user_id) as total_customers
        FROM reservations
        WHERE DATE(created_at) BETWEEN ? AND ?
    ";
    $stmt = $pdo->prepare($sql1);
    $stmt->execute([$date_from, $date_to]);
    $total = $stmt->fetch()['total_customers'];
    
    // Get repeat customers (more than 1 booking)
    $sql2 = "
        SELECT COUNT(*) as repeat_customers
        FROM (
            SELECT user_id, COUNT(*) as booking_count
            FROM reservations
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY user_id
            HAVING booking_count > 1
        ) as repeats
    ";
    $stmt = $pdo->prepare($sql2);
    $stmt->execute([$date_from, $date_to]);
    $repeats = $stmt->fetch()['repeat_customers'];
    
    if ($total > 0) {
        return ($repeats / $total) * 100;
    }
    
    return 0;
}
?>