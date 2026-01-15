<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

// Check if user is verified
$user_profile = getUserProfile($_SESSION['user_id']);
if ($user_profile['verification_status'] !== 'verified') {
    $_SESSION['error'] = "You need to be verified to make reservations. Please complete ID verification first.";
    header("Location: cottages.php");
    exit();
}

// Get cottage ID
$cottage_id = $_GET['cottage_id'] ?? 0;
if (!$cottage_id) {
    header("Location: cottages.php");
    exit();
}

// Get cottage details
$stmt = $pdo->prepare("SELECT * FROM cottages WHERE cottage_id = ? AND is_active = TRUE");
$stmt->execute([$cottage_id]);
$cottage = $stmt->fetch();

if (!$cottage) {
    header("Location: cottages.php?error=cottage_not_found");
    exit();
}

// Handle classic POST submission (fallback for non-JS users)
$classic_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $guests = $_POST['guests'] ?? 1;
    
    // Validate dates
    if (empty($check_in) || empty($check_out)) {
        $classic_errors[] = "Please select both check-in and check-out dates.";
    }
    
    if ($check_in >= $check_out) {
        $classic_errors[] = "Check-out date must be after check-in date.";
    }
    
    // Check if dates are in the past
    $today = date('Y-m-d');
    if ($check_in < $today) {
        $classic_errors[] = "Check-in date cannot be in the past.";
    }
    
    // Validate guest count
    $guests = intval($guests);
    if ($guests < 1) {
        $classic_errors[] = "Number of guests must be at least 1.";
    }
    
    if ($guests > $cottage['capacity']) {
        $classic_errors[] = "This cottage can only accommodate up to {$cottage['capacity']} guests.";
    }
    
    // Check availability
    if (empty($classic_errors) && !isDateAvailable($cottage_id, $check_in, $check_out)) {
        $classic_errors[] = "The selected dates are not available. Please choose different dates.";
    }
    
    // Calculate total
    if (empty($classic_errors)) {
        $total_nights = calculateNights($check_in, $check_out);
        $total_price = $total_nights * $cottage['price_per_night'];
        
        // Create reservation
        try {
            $pdo->beginTransaction();
            
            // Insert reservation
            $stmt = $pdo->prepare("
                INSERT INTO reservations 
                (user_id, cottage_id, check_in_date, check_out_date, total_nights, total_price, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_admin_review')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $cottage_id,
                $check_in,
                $check_out,
                $total_nights,
                $total_price
            ]);
            
            $reservation_id = $pdo->lastInsertId();
            
            // Log the action
            logAction($_SESSION['user_id'], 'CREATE_RESERVATION', 'reservations', $reservation_id);
            
            $pdo->commit();
            
            // Redirect to payment page
            header("Location: upload-payment.php?reservation_id=" . $reservation_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $classic_errors[] = "Failed to create reservation. Please try again.";
        }
    }
}

// Get blocked dates for calendar (approved reservations)
$stmt = $pdo->prepare("
    SELECT check_in_date, check_out_date 
    FROM reservations 
    WHERE cottage_id = ? 
    AND status = 'approved'
    AND check_out_date >= CURDATE()
");
$stmt->execute([$cottage_id]);
$blocked_periods = $stmt->fetchAll();

// Convert to JavaScript array format
$js_blocked_dates = [];
foreach ($blocked_periods as $period) {
    $js_blocked_dates[] = [
        'from' => $period['check_in_date'],
        'to' => $period['check_out_date']
    ];
}
$js_blocked_dates = json_encode($js_blocked_dates);

// Get all unavailable dates (for calendar display)
$unavailable_dates = [];
foreach ($blocked_periods as $period) {
    $start = new DateTime($period['check_in_date']);
    $end = new DateTime($period['check_out_date']);
    
    // Add all dates in the blocked period (excluding check-out date)
    while ($start < $end) {
        $unavailable_dates[] = $start->format('Y-m-d');
        $start->modify('+1 day');
    }
}
$js_unavailable_dates = json_encode(array_unique($unavailable_dates));

// Set AdminLTE page variables
$page_title = "Reserve {$cottage['cottage_name']} - Aura Luxe Resort";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.min.css">
    
    <!-- Custom Resort Colors for AdminLTE -->
    <style>
        :root {
            --primary-turquoise: #40E0D0;
            --secondary-aqua: #00FFFF;
            --background-cream: #FFF5E1;
            --accent-coral: #FF7F50;
            --accent-yellow: #FFD300;
            --accent-watermelon: #FC6C85;
        }
        
        /* Override AdminLTE primary color */
        .bg-primary, .btn-primary {
            background-color: var(--primary-turquoise) !important;
            border-color: var(--primary-turquoise) !important;
        }
        
        .text-primary {
            color: var(--primary-turquoise) !important;
        }
        
        .bg-secondary {
            background-color: var(--secondary-aqua) !important;
        }
        
        .bg-warning {
            background-color: var(--accent-yellow) !important;
        }
        
        .bg-danger {
            background-color: var(--accent-watermelon) !important;
        }
        
        /* Card styling with resort theme */
        .card {
            border: 1px solid rgba(64, 224, 208, 0.2);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            border-radius: 10px 10px 0 0 !important;
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            background-color: var(--background-cream);
        }
        
        /* Button customization */
        .btn {
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.4);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary-turquoise);
            border: 2px solid var(--primary-turquoise);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary-turquoise);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--accent-coral), #ff9a80);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #ff6b3d, var(--accent-coral));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 127, 80, 0.4);
        }
        
        /* Step indicator */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            right: -40%;
            height: 3px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step.active:not(:last-child)::after {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
        }
        
        .step-number {
            display: inline-block;
            width: 32px;
            height: 32px;
            line-height: 32px;
            background: #e0e0e0;
            color: #666;
            border-radius: 50%;
            font-weight: bold;
            margin-bottom: 5px;
            position: relative;
            z-index: 2;
        }
        
        .step.active .step-number {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            box-shadow: 0 3px 10px rgba(64, 224, 208, 0.3);
        }
        
        .step-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        
        .step.active .step-label {
            color: var(--primary-turquoise);
        }
        
        /* Date selection header */
        .date-selection-header {
            background: white;
            border: 2px solid var(--primary-turquoise);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .date-inputs {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .date-input-group {
            text-align: center;
        }
        
        .date-input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .date-display {
            padding: 12px 20px;
            background: var(--background-cream);
            border: 2px solid var(--primary-turquoise);
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-turquoise);
            min-width: 150px;
        }
        
        .date-display.empty {
            color: #999;
            border-color: #ddd;
        }
        
        /* 3-Month Calendar */
        .calendar-container {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .calendar-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-months {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        @media (min-width: 1200px) {
            .calendar-months {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        .calendar-month {
            min-width: 300px;
        }
        
        .month-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-turquoise);
        }
        
        .month-header h4 {
            color: var(--primary-turquoise);
            font-weight: 700;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 600;
            color: #666;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        
        .calendar-day {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            position: relative;
        }
        
        .calendar-day:hover:not(.disabled):not(.blocked) {
            background: rgba(64, 224, 208, 0.1);
            transform: scale(1.05);
        }
        
        .calendar-day.today {
            background: var(--accent-yellow);
            color: white;
            font-weight: bold;
        }
        
        .calendar-day.selected {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            font-weight: bold;
            box-shadow: 0 3px 8px rgba(64, 224, 208, 0.4);
        }
        
        .calendar-day.check-in {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            font-weight: bold;
            border-radius: 8px 0 0 8px;
        }
        
        .calendar-day.check-out {
            background: linear-gradient(135deg, var(--secondary-aqua), var(--primary-turquoise));
            color: white;
            font-weight: bold;
            border-radius: 0 8px 8px 0;
        }
        
        .calendar-day.in-range {
            background: linear-gradient(135deg, rgba(64, 224, 208, 0.3), rgba(0, 255, 255, 0.3));
        }
        
        .calendar-day.blocked {
            background: rgba(252, 108, 133, 0.2);
            color: #721c24;
            cursor: not-allowed;
            opacity: 0.6;
            position: relative;
        }
        
        .calendar-day.blocked::after {
            content: '✗';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 10px;
            color: var(--accent-watermelon);
        }
        
        .calendar-day.disabled {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
        }
        
        .calendar-day.empty {
            visibility: hidden;
        }
        
        .day-number {
            z-index: 2;
        }
        
        /* Guest selector */
        .guest-selector {
            display: flex;
            align-items: center;
            max-width: 200px;
            margin: 20px 0;
        }
        
        .guest-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-turquoise);
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .guest-btn:hover {
            background: var(--secondary-aqua);
            transform: scale(1.1);
        }
        
        .guest-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .guest-selector input {
            width: 60px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            border: none;
            background: transparent;
        }
        
        .guest-label {
            margin-left: 10px;
            font-weight: 600;
            color: #333;
        }
        
        /* Cottage summary */
        .summary-card {
            background: white;
            border: 2px solid rgba(64, 224, 208, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .cottage-image-small img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .detail-row i {
            color: var(--primary-turquoise);
            width: 24px;
            margin-right: 10px;
        }
        
        .detail-row span {
            flex: 1;
            color: #666;
        }
        
        .detail-row strong {
            color: #333;
        }
        
        /* Price calculator */
        .price-calculator {
            background: white;
            border: 2px solid var(--primary-turquoise);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e0e0e0;
        }
        
        .breakdown-row.total {
            border-bottom: none;
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-turquoise);
        }
        
        /* Info box */
        .info-box {
            background: rgba(64, 224, 208, 0.1);
            border-left: 4px solid var(--primary-turquoise);
            border-radius: 8px;
            padding: 20px;
        }
        
        .info-box ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .info-box li {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
        }
        
        .info-box li i {
            color: var(--primary-turquoise);
            margin-right: 10px;
            margin-top: 2px;
        }
        
        /* Alert customization */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 8px;
        }
        
        .alert-danger {
            background-color: rgba(252, 108, 133, 0.1);
            border-left-color: var(--accent-watermelon);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left-color: #28a745;
        }
        
        /* Legend */
        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding: 15px;
            background: rgba(64, 224, 208, 0.05);
            border-radius: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .legend-available {
            background: white;
            border: 1px solid #ddd;
        }
        
        .legend-selected {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
        }
        
        .legend-blocked {
            background: rgba(252, 108, 133, 0.2);
        }
        
        .legend-today {
            background: var(--accent-yellow);
        }
        
        /* Progress bar */
        .progress-container {
            display: none;
            margin: 20px 0;
        }
        
        .progress {
            height: 20px;
            border-radius: 10px;
            background-color: #f5f5f5;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        
        /* Form actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        /* Note styling */
        .note {
            background: rgba(255, 211, 0, 0.1);
            border-left: 3px solid var(--accent-yellow);
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            margin-top: 15px;
        }
        
        .note i {
            color: var(--accent-yellow);
            margin-right: 5px;
        }
        
        /* Instructions */
        .instructions {
            background: rgba(64, 224, 208, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-turquoise);
        }
        
        /* AJAX loader */
        .submit-loader {
            display: none;
            margin-left: 10px;
        }
        
        .ajax-loading .submit-loader {
            display: inline-block;
        }
        
        .ajax-loading .btn-text {
            opacity: 0.7;
        }
        
        /* Calendar navigation buttons */
        .calendar-nav-btn {
            background: var(--primary-turquoise);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .calendar-nav-btn:hover {
            background: var(--secondary-aqua);
            transform: scale(1.1);
        }
        
        .calendar-nav-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12">
                        <div class="welcome-header" style="background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua)); 
                              color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <h1 class="m-0">
                                <i class="fas fa-calendar-check mr-2"></i>Make Reservation
                            </h1>
                            <p class="mb-0 mt-2" style="opacity: 0.9;">
                                <a href="cottage-details.php?id=<?= $cottage_id ?>" class="back-link" style="color: white; text-decoration: underline;">
                                    <i class="fas fa-arrow-left mr-1"></i>Back to Cottage Details
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Classic POST Messages (fallback for non-JS) -->
                <div id="classic-messages">
                    <?php if (!empty($classic_errors)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Please fix the following:</h5>
                        <ul>
                            <?php foreach ($classic_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- AJAX Messages Container -->
                <div id="ajax-messages" style="display: none;"></div>

                <div class="row">
                    <div class="col-lg-12">
                        <!-- Step Indicator -->
                        <div class="card">
                            <div class="card-body">
                                <div class="step-indicator">
                                    <div class="step active">
                                        <span class="step-number">1</span>
                                        <span class="step-label">Select Dates</span>
                                    </div>
                                    <div class="step">
                                        <span class="step-number">2</span>
                                        <span class="step-label">Upload Payment</span>
                                    </div>
                                    <div class="step">
                                        <span class="step-number">3</span>
                                        <span class="step-label">Confirmation</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column: Cottage Summary -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-home mr-2"></i> Cottage Summary</h3>
                            </div>
                            <div class="card-body">
                                <div class="summary-card">
                                    <?php
                                    // Get primary image
                                    $stmt = $pdo->prepare("
                                        SELECT image_path 
                                        FROM cottage_images 
                                        WHERE cottage_id = ? AND is_primary = TRUE 
                                        LIMIT 1
                                    ");
                                    $stmt->execute([$cottage_id]);
                                    $image = $stmt->fetch();
                                    ?>
                                    <div class="cottage-image-small">
                                        <img src="../uploads/cottages/<?= htmlspecialchars($image ? $image['image_path'] : 'default_cottage.jpg') ?>" 
                                             alt="<?= htmlspecialchars($cottage['cottage_name']) ?>">
                                    </div>
                                    
                                    <h4 class="text-primary mb-3"><?= htmlspecialchars($cottage['cottage_name']) ?></h4>
                                    
                                    <div class="detail-row">
                                        <i class="fas fa-users"></i>
                                        <span>Capacity:</span>
                                        <strong><?= $cottage['capacity'] ?> guests</strong>
                                    </div>
                                    
                                    <div class="detail-row">
                                        <i class="fas fa-tag"></i>
                                        <span>Price per night:</span>
                                        <strong class="text-primary">₱<?= number_format($cottage['price_per_night'], 2) ?></strong>
                                    </div>
                                    
                                    <div class="detail-row">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Min. stay:</span>
                                        <strong>1 night</strong>
                                    </div>
                                </div>

                                <!-- Selected Dates Display -->
                                <div class="date-selection-header">
                                    <h5 class="text-primary mb-3"><i class="fas fa-calendar-day mr-2"></i> Selected Dates</h5>
                                    
                                    <div class="date-inputs">
                                        <div class="date-input-group">
                                            <label for="check_in_display">Check-in</label>
                                            <div id="check_in_display" class="date-display empty">Not selected</div>
                                            <input type="hidden" id="check_in" name="check_in" value="">
                                        </div>
                                        
                                        <div class="date-input-group">
                                            <label for="check_out_display">Check-out</label>
                                            <div id="check_out_display" class="date-display empty">Not selected</div>
                                            <input type="hidden" id="check_out" name="check_out" value="">
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearDates()">
                                            <i class="fas fa-times mr-1"></i> Clear Dates
                                        </button>
                                    </div>
                                </div>

                                <!-- Price Calculator -->
                                <div class="price-calculator" id="priceCalculator" style="display: none;">
                                    <h5 class="text-primary mb-3"><i class="fas fa-calculator mr-2"></i> Price Breakdown</h5>
                                    
                                    <div class="breakdown-row">
                                        <span>Cottage rate:</span>
                                        <span id="ratePerNight">₱0.00</span>
                                    </div>
                                    
                                    <div class="breakdown-row">
                                        <span>Number of nights:</span>
                                        <span id="nightsCount">0</span>
                                    </div>
                                    
                                    <div class="breakdown-row total">
                                        <span><strong>Total amount:</strong></span>
                                        <span id="totalAmount">₱0.00</span>
                                    </div>
                                    
                                    <div class="note">
                                        <i class="fas fa-info-circle"></i>
                                        Payment proof must be uploaded within 24 hours of reservation.
                                    </div>
                                </div>

                                <!-- Guest Selection -->
                                <div class="mt-4">
                                    <h5 class="text-primary"><i class="fas fa-users mr-2"></i> Number of Guests</h5>
                                    
                                    <div class="guest-selector">
                                        <button type="button" class="guest-btn" onclick="adjustGuests(-1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        
                                        <input type="number" 
                                               id="guests" 
                                               name="guests" 
                                               value="1" 
                                               min="1" 
                                               max="<?= $cottage['capacity'] ?>"
                                               readonly>
                                        
                                        <span class="guest-label">Guest(s)</span>
                                        
                                        <button type="button" class="guest-btn" onclick="adjustGuests(1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="note">
                                        <i class="fas fa-info-circle"></i>
                                        Maximum capacity: <?= $cottage['capacity'] ?> guests
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Calendar & Form -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i> Select Your Dates</h3>
                            </div>
                            <div class="card-body">
                                <!-- Instructions -->
                                <div class="instructions">
                                    <h5 class="text-primary"><i class="fas fa-mouse-pointer mr-2"></i> How to select dates:</h5>
                                    <p class="mb-2">1. <strong>Click on your check-in date</strong> (dates in red are already booked)</p>
                                    <p class="mb-2">2. <strong>Click on your check-out date</strong> (must be after check-in)</p>
                                    <p class="mb-0">3. <strong>Adjust number of guests</strong> using the buttons on the left</p>
                                </div>
                                
                                <form method="POST" id="reservationForm" novalidate>
                                    <input type="hidden" name="cottage_id" value="<?= $cottage_id ?>">
                                    
                                    <!-- Calendar Navigation -->
                                    <div class="calendar-navigation">
                                        <button type="button" class="calendar-nav-btn" onclick="changeCalendarMonths(-3)" id="prevMonthsBtn">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        
                                        <h4 id="calendarRange" class="text-primary"></h4>
                                        
                                        <button type="button" class="calendar-nav-btn" onclick="changeCalendarMonths(3)" id="nextMonthsBtn">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- 3-Month Calendar Container -->
                                    <div class="calendar-container">
                                        <div id="threeMonthCalendar" class="calendar-months">
                                            <!-- Calendar will be generated by JavaScript -->
                                        </div>
                                        
                                        <!-- Legend -->
                                        <div class="calendar-legend">
                                            <div class="legend-item">
                                                <div class="legend-color legend-available"></div>
                                                <span>Available</span>
                                            </div>
                                            <div class="legend-item">
                                                <div class="legend-color legend-selected"></div>
                                                <span>Selected</span>
                                            </div>
                                            <div class="legend-item">
                                                <div class="legend-color legend-today"></div>
                                                <span>Today</span>
                                            </div>
                                            <div class="legend-item">
                                                <div class="legend-color legend-blocked"></div>
                                                <span>Booked</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Important Notes -->
                                    <div class="form-group mt-4">
                                        <h5><i class="fas fa-exclamation-circle mr-2" style="color: var(--accent-yellow);"></i> Important Information</h5>
                                        
                                        <div class="info-box">
                                            <ul>
                                                <li>
                                                    <i class="fas fa-clock"></i>
                                                    <strong>Check-in time:</strong> 2:00 PM
                                                </li>
                                                <li>
                                                    <i class="fas fa-clock"></i>
                                                    <strong>Check-out time:</strong> 12:00 PM
                                                </li>
                                                <li>
                                                    <i class="fas fa-calendar-times"></i>
                                                    <strong>Cancellation:</strong> No cancellations after submission
                                                </li>
                                                <li>
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <strong>Payment:</strong> Upload payment proof after reservation
                                                </li>
                                                <li>
                                                    <i class="fas fa-user-shield"></i>
                                                    <strong>Confirmation:</strong> Subject to admin approval
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="progress-container" id="progressContainer">
                                        <div class="progress">
                                            <div class="progress-bar" id="progressBar"></div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <span id="progressText" style="color: var(--primary-turquoise); font-weight: 600;"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                            <span class="btn-text">
                                                <i class="fas fa-check-circle mr-1"></i> Confirm Reservation
                                            </span>
                                            <span class="submit-loader">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                        
                                        <a href="cottage-details.php?id=<?= $cottage_id ?>" class="btn btn-secondary">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            Aura Luxe Resort
        </div>
        <strong>Copyright &copy; <?php echo date('Y'); ?> Aura Luxe Resort.</strong> All rights reserved.
    </footer>
</div>

<!-- AdminLTE Scripts -->
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>
<script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../adminlte/dist/js/adminlte.min.js"></script>

<script>
// Data from PHP
const unavailableDates = <?= $js_unavailable_dates ?>;
const cottagePrice = <?= $cottage['price_per_night'] ?>;
const cottageCapacity = <?= $cottage['capacity'] ?>;
let selectedCheckIn = null;
let selectedCheckOut = null;
let isSelectingCheckIn = true;
let currentStartMonth = 0; // 0 = current month, 1 = next month from current, etc.

$(document).ready(function() {
    // Hide classic messages initially
    $('#classic-messages').hide();
    
    // Initialize calendar
    generateThreeMonthCalendar();
    
    // Initialize guest selector
    updateGuestSelector();
    
    // Update navigation buttons
    updateNavigationButtons();
});

/**
 * Generate 3-month calendar starting from currentStartMonth offset
 */
function generateThreeMonthCalendar() {
    const today = new Date();
    const calendarContainer = $('#threeMonthCalendar');
    const calendarRange = $('#calendarRange');
    
    // Calculate start date
    const startDate = new Date(today.getFullYear(), today.getMonth() + currentStartMonth, 1);
    
    // Update calendar range display
    const monthNames = ["January", "February", "March", "April", "May", "June",
                       "July", "August", "September", "October", "November", "December"];
    
    const endDate = new Date(today.getFullYear(), today.getMonth() + currentStartMonth + 2, 1);
    calendarRange.text(`${monthNames[startDate.getMonth()]} ${startDate.getFullYear()} - ${monthNames[endDate.getMonth()]} ${endDate.getFullYear()}`);
    
    // Generate HTML for 3 months
    let calendarHTML = '';
    
    for (let monthOffset = 0; monthOffset < 4; monthOffset++) {
        const monthDate = new Date(today.getFullYear(), today.getMonth() + currentStartMonth + monthOffset, 1);
        calendarHTML += generateMonthCalendar(monthDate);
    }
    
    calendarContainer.html(calendarHTML);
    
    // Update date displays
    updateDateDisplays();
    
    // Update price calculator
    updatePriceCalculator();
}

/**
 * Generate calendar for a specific month
 */
function generateMonthCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const monthNames = ["January", "February", "March", "April", "May", "June",
                       "July", "August", "September", "October", "November", "December"];
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let calendarHTML = `
        <div class="calendar-month">
            <div class="month-header">
                <h4>${monthNames[month]} ${year}</h4>
            </div>
            <div class="calendar-weekdays">
                <div>Sun</div>
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div>Sat</div>
            </div>
            <div class="calendar-days">
    `;
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDay; i++) {
        calendarHTML += '<div class="calendar-day empty"></div>';
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        const dateStr = currentDate.toISOString().split('T')[0];
        
        // Determine day state
        let className = 'calendar-day';
        let isDisabled = false;
        let isBlocked = false;
        
        // Check if date is in the past
        if (currentDate < today) {
            className += ' disabled';
            isDisabled = true;
        }
        
        // Check if date is today
        if (currentDate.getTime() === today.getTime()) {
            className += ' today';
        }
        
        // Check if date is unavailable (booked)
        if (unavailableDates.includes(dateStr)) {
            className += ' blocked';
            isBlocked = true;
        }
        
        // Check if date is selected
        if (dateStr === selectedCheckIn) {
            className += ' check-in';
        } else if (dateStr === selectedCheckOut) {
            className += ' check-out';
        } else if (selectedCheckIn && selectedCheckOut && 
                   currentDate > new Date(selectedCheckIn) && 
                   currentDate < new Date(selectedCheckOut)) {
            className += ' in-range';
        }
        
        // Add click handler if date is selectable
        if (!isDisabled && !isBlocked) {
            calendarHTML += `<div class="${className}" data-date="${dateStr}" onclick="selectDate('${dateStr}')">
                <span class="day-number">${day}</span>
            </div>`;
        } else {
            calendarHTML += `<div class="${className}" data-date="${dateStr}">
                <span class="day-number">${day}</span>
            </div>`;
        }
    }
    
    calendarHTML += '</div></div>';
    return calendarHTML;
}

/**
 * Select a date from the calendar
 */
function selectDate(dateStr) {
    const selectedDate = new Date(dateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Don't allow selecting past dates
    if (selectedDate < today) {
        return;
    }
    
    // If no check-in selected, or if clicking on a date before current check-in, set as check-in
    if (!selectedCheckIn || selectedDate < new Date(selectedCheckIn)) {
        selectedCheckIn = dateStr;
        selectedCheckOut = null;
        isSelectingCheckIn = false;
    } 
    // If check-in is selected and we're selecting check-out
    else if (selectedCheckIn && !selectedCheckOut) {
        // Check-out must be after check-in
        if (selectedDate <= new Date(selectedCheckIn)) {
            showMessage('error', 'Check-out date must be after check-in date.');
            return;
        }
        
        // Check if any dates in the range are unavailable
        if (!isDateRangeAvailable(selectedCheckIn, dateStr)) {
            showMessage('error', 'Some dates in your selection are already booked. Please choose different dates.');
            return;
        }
        
        selectedCheckOut = dateStr;
        isSelectingCheckIn = true;
    }
    // If both dates are selected and user clicks on a new date
    else if (selectedCheckIn && selectedCheckOut) {
        selectedCheckIn = dateStr;
        selectedCheckOut = null;
        isSelectingCheckIn = false;
    }
    
    // Regenerate calendar to update highlighting
    generateThreeMonthCalendar();
    
    // Update date displays and price calculator
    updateDateDisplays();
    updatePriceCalculator();
}

/**
 * Check if a date range is available
 */
function isDateRangeAvailable(startDateStr, endDateStr) {
    const startDate = new Date(startDateStr);
    const endDate = new Date(endDateStr);
    const current = new Date(startDate);
    
    while (current < endDate) {
        const dateStr = current.toISOString().split('T')[0];
        if (unavailableDates.includes(dateStr)) {
            return false;
        }
        current.setDate(current.getDate() + 1);
    }
    
    return true;
}

/**
 * Update date displays
 */
function updateDateDisplays() {
    const checkInDisplay = $('#check_in_display');
    const checkOutDisplay = $('#check_out_display');
    const checkInInput = $('#check_in');
    const checkOutInput = $('#check_out');
    
    if (selectedCheckIn) {
        const checkInDate = new Date(selectedCheckIn);
        checkInDisplay.text(checkInDate.toLocaleDateString('en-US', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        }));
        checkInDisplay.removeClass('empty');
        checkInInput.val(selectedCheckIn);
    } else {
        checkInDisplay.text('Not selected');
        checkInDisplay.addClass('empty');
        checkInInput.val('');
    }
    
    if (selectedCheckOut) {
        const checkOutDate = new Date(selectedCheckOut);
        checkOutDisplay.text(checkOutDate.toLocaleDateString('en-US', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        }));
        checkOutDisplay.removeClass('empty');
        checkOutInput.val(selectedCheckOut);
    } else {
        checkOutDisplay.text('Not selected');
        checkOutDisplay.addClass('empty');
        checkOutInput.val('');
    }
}

/**
 * Clear selected dates
 */
function clearDates() {
    selectedCheckIn = null;
    selectedCheckOut = null;
    isSelectingCheckIn = true;
    generateThreeMonthCalendar();
    updateDateDisplays();
    updatePriceCalculator();
}

/**
 * Change calendar months (navigation)
 */
function changeCalendarMonths(direction) {
    currentStartMonth += direction;
    generateThreeMonthCalendar();
    updateNavigationButtons();
}

/**
 * Update navigation buttons state
 */
function updateNavigationButtons() {
    const prevBtn = $('#prevMonthsBtn');
    const nextBtn = $('#nextMonthsBtn');
    
    // Disable previous button if we're at the beginning
    prevBtn.prop('disabled', currentStartMonth <= 0);
    
    // We can always go forward (no limit for future months)
    nextBtn.prop('disabled', false);
}

/**
 * Update price calculator
 */
function updatePriceCalculator() {
    const calculator = $('#priceCalculator');
    
    if (selectedCheckIn && selectedCheckOut) {
        // Calculate nights
        const checkInDate = new Date(selectedCheckIn);
        const checkOutDate = new Date(selectedCheckOut);
        const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
        
        if (nights <= 0) {
            calculator.hide();
            return;
        }
        
        // Calculate total
        const total = nights * cottagePrice;
        
        // Update display
        $('#ratePerNight').text('₱' + cottagePrice.toFixed(2));
        $('#nightsCount').text(nights + ' night' + (nights !== 1 ? 's' : ''));
        $('#totalAmount').text('₱' + total.toFixed(2));
        
        // Show calculator
        calculator.show();
    } else {
        calculator.hide();
    }
}

/**
 * Guest management functions
 */
function adjustGuests(change) {
    const guestInput = $('#guests');
    let current = parseInt(guestInput.val()) + change;
    
    // Validate bounds
    if (current < 1) current = 1;
    if (current > cottageCapacity) current = cottageCapacity;
    
    guestInput.val(current);
    updateGuestSelector();
}

function updateGuestSelector() {
    const guestInput = $('#guests');
    const minusBtn = $('.guest-btn').first();
    const plusBtn = $('.guest-btn').last();
    
    // Disable minus button at minimum
    minusBtn.prop('disabled', (parseInt(guestInput.val()) <= 1));
    
    // Disable plus button at maximum
    plusBtn.prop('disabled', (parseInt(guestInput.val()) >= cottageCapacity));
}

/**
 * AJAX Form Submission
 */
$('#reservationForm').on('submit', function(e) {
    e.preventDefault();
    
    // Basic validation
    if (!selectedCheckIn || !selectedCheckOut) {
        showMessage('error', 'Please select both check-in and check-out dates.');
        return false;
    }
    
    if (selectedCheckIn >= selectedCheckOut) {
        showMessage('error', 'Check-out date must be after check-in date.');
        return false;
    }
    
    const guests = $('#guests').val();
    if (guests < 1 || guests > cottageCapacity) {
        showMessage('error', 'Please select a valid number of guests.');
        return false;
    }
    
    // Check if dates are available
    if (!isDateRangeAvailable(selectedCheckIn, selectedCheckOut)) {
        showMessage('error', 'Some dates in your selection are already booked. Please choose different dates.');
        return false;
    }
    
    // Confirm reservation
    if (!confirm('Are you sure you want to proceed with this reservation? This action cannot be undone.')) {
        return false;
    }
    
    // Disable form and show progress
    const submitBtn = $('#submitBtn');
    const progressContainer = $('#progressContainer');
    const progressBar = $('#progressBar');
    const progressText = $('#progressText');
    
    submitBtn.addClass('ajax-loading').prop('disabled', true);
    progressContainer.show();
    progressBar.css('width', '0%');
    progressText.text('Processing reservation...');
    
    // Prepare form data
    const formData = new FormData(this);
    formData.append('check_in', selectedCheckIn);
    formData.append('check_out', selectedCheckOut);
    formData.append('guests', guests);
    
    // Send AJAX request
    $.ajax({
        url: 'ajax_reserve.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            
            // Upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 50;
                    progressBar.css('width', percentComplete + '%');
                    progressText.text(`Processing: ${Math.round(percentComplete * 2)}%`);
                }
            });
            
            return xhr;
        },
        success: function(response) {
            if (response.success) {
                progressBar.css('width', '100%');
                progressBar.css('background', 'linear-gradient(135deg, #28a745, #20c997)');
                progressText.text('Reservation successful! Redirecting...');
                
                showMessage('success', response.message);
                
                // Redirect after 2 seconds
                setTimeout(function() {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                }, 2000);
            } else {
                // Enable form
                submitBtn.removeClass('ajax-loading').prop('disabled', false);
                progressContainer.hide();
                
                // Show errors
                if (response.message) {
                    showMessage('error', response.message);
                }
                
                if (response.errors) {
                    for (const field in response.errors) {
                        showMessage('error', response.errors[field]);
                    }
                }
            }
        },
        error: function(xhr, status, error) {
            submitBtn.removeClass('ajax-loading').prop('disabled', false);
            progressContainer.hide();
            showMessage('error', 'Network error. Please check your connection and try again.');
            console.error('AJAX Error:', error);
        }
    });
});

/**
 * Show message in AJAX container
 */
function showMessage(type, message) {
    const ajaxMessages = $('#ajax-messages');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check' : 'fa-ban';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas ${icon}"></i> ${type === 'success' ? 'Success!' : 'Error!'}</h5>
            ${message}
        </div>
    `;
    
    ajaxMessages.html(alertHtml).show();
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(function() {
            ajaxMessages.find('.alert').fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
}

// Auto-hide classic messages after 5 seconds
setTimeout(function() {
    $('#classic-messages').find('.alert').fadeTo(500, 0).slideUp(500, function() {
        $(this).remove();
    });
}, 5000);
</script>
</body>
</html>