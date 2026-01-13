<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

$report_type = $_GET['type'] ?? 'daily';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$cottage_id = $_GET['cottage_id'] ?? 'all';

// Get report data
require_once 'reports.php';

switch ($report_type) {
    case 'daily':
        $data = getDailyReport($date_from, $date_to, $cottage_id);
        $title = 'Daily Reservation Report';
        break;
    case 'weekly':
        $data = getWeeklyReport($date_from, $date_to, $cottage_id);
        $title = 'Weekly Reservation Report';
        break;
    case 'monthly':
        $data = getMonthlyReport($date_from, $date_to, $cottage_id);
        $title = 'Monthly Reservation Report';
        break;
    case 'cottage':
        $data = getCottagePerformanceReport($date_from, $date_to);
        $title = 'Cottage Performance Report';
        break;
    case 'revenue':
        $data = getRevenueReport($date_from, $date_to, $cottage_id);
        $title = 'Revenue Analysis Report';
        break;
    default:
        $data = [];
        $title = 'Report';
}

// Get summary
$summary = getSummaryStats($date_from, $date_to, $cottage_id);

// Get resort info (you can customize this)
$resort_name = "Resort Reservation System";
$generated_date = date('F j, Y g:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Print</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 0.5in;
            }
            
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                color: #000;
                background: white;
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            background: white;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .header .subtitle {
            margin: 10px 0;
            font-size: 16px;
            color: #666;
        }
        
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .meta-item {
            flex: 1;
        }
        
        .meta-item strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .summary-item {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            display: block;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .report-table th {
            background: #343a40;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #454d55;
        }
        
        .report-table td {
            padding: 10px 12px;
            border: 1px solid #dee2e6;
        }
        
        .report-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        
        .print-actions {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-print {
            background: #28a745;
        }
        
        .btn-print:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <!-- Print Actions -->
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> Print Report
        </button>
        <a href="reports.php" class="btn">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
        <a href="export-report.php?type=<?= $report_type ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&cottage_id=<?= $cottage_id ?>" 
           class="btn">
            <i class="fas fa-download"></i> Export as CSV
        </a>
    </div>
    
    <!-- Report Header -->
    <div class="header">
        <h1><?= $resort_name ?></h1>
        <div class="subtitle"><?= $title ?></div>
        <div>Period: <?= formatDate($date_from) ?> to <?= formatDate($date_to) ?></div>
        <div>Generated: <?= $generated_date ?></div>
    </div>
    
    <!-- Summary Stats -->
    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-value"><?= number_format($summary['total_reservations'] ?? 0) ?></span>
            <span class="summary-label">Total Reservations</span>
        </div>
        <div class="summary-item">
            <span class="summary-value"><?= number_format($summary['approved_reservations'] ?? 0) ?></span>
            <span class="summary-label">Approved</span>
        </div>
        <div class="summary-item">
            <span class="summary-value">₱<?= number_format($summary['total_revenue'] ?? 0, 2) ?></span>
            <span class="summary-label">Total Revenue</span>
        </div>
        <div class="summary-item">
            <span class="summary-value"><?= number_format($summary['unique_customers'] ?? 0) ?></span>
            <span class="summary-label">Unique Customers</span>
        </div>
    </div>
    
    <!-- Detailed Report Table -->
    <table class="report-table">
        <thead>
            <tr>
                <?php if ($report_type === 'daily'): ?>
                <th>Date</th>
                <th>Total</th>
                <th>Approved</th>
                <th>Pending</th>
                <th>Rejected</th>
                <th>Revenue</th>
                <th>Customers</th>
                
                <?php elseif ($report_type === 'weekly'): ?>
                <th>Week</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Total</th>
                <th>Approved</th>
                <th>Revenue</th>
                <th>Avg/Booking</th>
                
                <?php elseif ($report_type === 'monthly'): ?>
                <th>Month</th>
                <th>Total</th>
                <th>Approved</th>
                <th>Revenue</th>
                <th>Customers</th>
                <th>Avg Nights</th>
                
                <?php elseif ($report_type === 'cottage'): ?>
                <th>Cottage</th>
                <th>Capacity</th>
                <th>Price/Night</th>
                <th>Bookings</th>
                <th>Approved</th>
                <th>Revenue</th>
                <th>Avg/Booking</th>
                <th>Market Share</th>
                
                <?php elseif ($report_type === 'revenue'): ?>
                <th>Date</th>
                <th>Daily Revenue</th>
                <th>Cumulative</th>
                <th>Bookings</th>
                <th>Avg Value</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
            <tr>
                <td colspan="10" style="text-align: center; padding: 20px;">
                    No data available for the selected period
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <?php if ($report_type === 'daily'): ?>
                    <td><?= formatDate($row['report_date']) ?></td>
                    <td><?= $row['total_reservations'] ?></td>
                    <td><?= $row['approved_reservations'] ?></td>
                    <td><?= $row['pending_reservations'] ?? 0 ?></td>
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
    
    <!-- Additional Notes -->
    <div class="page-break"></div>
    <div style="margin-top: 30px;">
        <h3>Report Notes & Analysis</h3>
        <div style="border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; margin-top: 15px;">
            <p><strong>Key Findings:</strong></p>
            <ul>
                <li>Total reservations during period: <?= number_format($summary['total_reservations'] ?? 0) ?></li>
                <li>Approval rate: <?= number_format(($summary['approved_reservations'] ?? 0) / max(1, $summary['total_reservations'] ?? 1) * 100, 1) ?>%</li>
                <li>Average revenue per booking: ₱<?= number_format($summary['avg_revenue_per_booking'] ?? 0, 2) ?></li>
                <li>Average nights per booking: <?= number_format($summary['avg_nights_per_booking'] ?? 0, 1) ?></li>
                <li>Total nights booked: <?= number_format($summary['total_nights_booked'] ?? 0) ?></li>
            </ul>
            
            <p><strong>Recommendations:</strong></p>
            <ol>
                <li>Consider promotional offers during low-occupancy periods</li>
                <li>Focus marketing on top-performing cottages</li>
                <li>Review and optimize pricing strategy based on demand patterns</li>
                <li>Implement customer loyalty programs to increase repeat bookings</li>
            </ol>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p><?= $resort_name ?> - Confidential Report</p>
        <p>Page 1 of 1 | Generated on <?= $generated_date ?></p>
        <p>This report is for internal use only.</p>
    </div>
    
    <script>
    // Auto-print option
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === 'true') {
        window.print();
    }
    
    // Add print styles
    const style = document.createElement('style');
    style.textContent = `
        @media print {
            body { font-size: 10pt; }
            .report-table { font-size: 9pt; }
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>