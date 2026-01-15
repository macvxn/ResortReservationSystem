<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

// Get user ID
$user_id = $_SESSION['user_id'];

// Get reservation ID
$reservation_id = $_GET['reservation_id'] ?? 0;
if (!$reservation_id) {
    header("Location: my-reservations.php");
    exit();
}

// Check if reservation belongs to user and get details
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.cottage_name 
        FROM reservations r
        JOIN cottages c ON r.cottage_id = c.cottage_id
        WHERE r.reservation_id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        header("Location: my-reservations.php?error=not_found");
        exit();
    }
    
    // Check if already has payment
    $stmt = $pdo->prepare("SELECT * FROM payment_proofs WHERE reservation_id = ?");
    $stmt->execute([$reservation_id]);
    $existing_payment = $stmt->fetch();
    
    if ($existing_payment) {
        header("Location: my-reservations.php?error=already_uploaded");
        exit();
    }
    
} catch (Exception $e) {
    header("Location: my-reservations.php?error=system");
    exit();
}

// Handle classic POST submission (fallback for non-JS users)
$classic_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $reference_number = $_POST['reference_number'] ?? '';
    
    // Basic validation
    if (empty($reference_number)) {
        $classic_errors[] = "Reference number is required.";
    }
    
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        $classic_errors[] = "Please upload payment proof.";
    } else {
        $file = $_FILES['payment_proof'];
    }
    
    // If no errors, process
    if (empty($classic_errors)) {
        // Upload file
        $upload_result = uploadFile($file, 'payments');
        
        if ($upload_result['success']) {
            try {
                // Save to database
                $stmt = $pdo->prepare("
                    INSERT INTO payment_proofs 
                    (reservation_id, receipt_image_path, reference_number)
                    VALUES (?, ?, ?)
                ");
                
                $stmt->execute([
                    $reservation_id,
                    $upload_result['filename'],
                    $reference_number
                ]);
                
                // Log action
                logAction($user_id, 'UPLOAD_PAYMENT', 'payment_proofs', $pdo->lastInsertId());
                
                // Redirect to success
                header("Location: my-reservations.php?success=payment_uploaded");
                exit();
                
            } catch (Exception $e) {
                $classic_errors[] = "Failed to save payment. Please try again.";
            }
        } else {
            $classic_errors[] = $upload_result['message'];
        }
    }
}

// Set AdminLTE page variables
$page_title = "Upload Payment - Aura Luxe Resort";
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
        
        .bg-success {
            background-color: #28a745 !important;
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
        
        /* Reservation summary */
        .reservation-summary {
            background: white;
            border: 2px solid rgba(64, 224, 208, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .summary-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-turquoise);
        }
        
        .reservation-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-turquoise);
            margin-bottom: 10px;
        }
        
        .summary-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            background: rgba(64, 224, 208, 0.05);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--primary-turquoise);
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .total-amount {
            background: linear-gradient(135deg, rgba(64, 224, 208, 0.1), rgba(0, 255, 255, 0.1));
            border: 2px solid var(--primary-turquoise);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 10px;
        }
        
        .total-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .total-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-turquoise);
        }
        
        /* Upload area styling */
        .upload-container {
            margin: 30px 0;
        }
        
        .upload-area {
            border: 3px dashed var(--primary-turquoise);
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            background: rgba(64, 224, 208, 0.05);
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .upload-area:hover {
            background: rgba(64, 224, 208, 0.1);
            border-color: var(--accent-coral);
        }
        
        .upload-area.dragover {
            background: rgba(64, 224, 208, 0.2);
            border-color: var(--accent-yellow);
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 60px;
            color: var(--primary-turquoise);
            margin-bottom: 15px;
        }
        
        .preview-container {
            margin: 20px 0;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            border: 2px solid #ddd;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .preview-pdf {
            font-size: 80px;
            color: var(--accent-coral);
            margin: 20px 0;
        }
        
        /* File info */
        .file-info {
            background: white;
            border: 1px solid rgba(64, 224, 208, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
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
        
        .progress-text {
            text-align: center;
            margin-top: 10px;
            font-weight: 600;
            color: var(--primary-turquoise);
        }
        
        /* Alert customization */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 8px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left-color: #28a745;
        }
        
        .alert-danger {
            background-color: rgba(252, 108, 133, 0.1);
            border-left-color: var(--accent-watermelon);
        }
        
        .alert-warning {
            background-color: rgba(255, 211, 0, 0.1);
            border-left-color: var(--accent-yellow);
        }
        
        /* Instructions */
        .instructions-box {
            background: linear-gradient(135deg, rgba(255, 127, 80, 0.1), rgba(255, 211, 0, 0.1));
            border: 1px solid rgba(255, 127, 80, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .instructions-box h5 {
            color: var(--accent-coral);
            margin-bottom: 15px;
        }
        
        .instructions-box ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .instructions-box li {
            margin-bottom: 10px;
            padding-left: 5px;
        }
        
        .instructions-box li:last-child {
            margin-bottom: 0;
        }
        
        /* Payment methods */
        .payment-methods {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        
        .payment-method {
            text-align: center;
            padding: 15px;
            background: white;
            border: 1px solid rgba(64, 224, 208, 0.3);
            border-radius: 10px;
            width: 120px;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: var(--primary-turquoise);
        }
        
        .payment-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .gcash {
            color: #00a859;
        }
        
        .maya {
            color: #6c3e93;
        }
        
        .bank {
            color: #0056b3;
        }
        
        /* Form styling */
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-turquoise);
            box-shadow: 0 0 0 3px rgba(64, 224, 208, 0.2);
            outline: none;
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
        
        /* Time limit warning */
        .time-limit {
            background: rgba(255, 211, 0, 0.1);
            border: 1px solid var(--accent-yellow);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }
        
        .countdown {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent-yellow);
            margin: 10px 0;
        }
        
        /* File size info */
        .file-size-info {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
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
                                <i class="fas fa-file-upload mr-2"></i>Upload Payment Proof
                            </h1>
                            <p class="mb-0 mt-2" style="opacity: 0.9;">
                                <a href="my-reservations.php" class="back-link" style="color: white; text-decoration: underline;">
                                    <i class="fas fa-arrow-left mr-1"></i>Back to My Reservations
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
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
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

                        <!-- Reservation Summary Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-receipt mr-2"></i> Reservation Summary</h3>
                            </div>
                            <div class="card-body">
                                <div class="reservation-summary">
                                    <div class="summary-header">
                                        <div class="reservation-number">
                                            Reservation #<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?>
                                        </div>
                                        <div class="text-muted">
                                            Please upload payment proof for this reservation
                                        </div>
                                    </div>
                                    
                                    <div class="summary-details">
                                        <div class="summary-item">
                                            <div class="summary-label">Cottage</div>
                                            <div class="summary-value"><?= htmlspecialchars($reservation['cottage_name']) ?></div>
                                        </div>
                                        
                                        <div class="summary-item">
                                            <div class="summary-label">Check-in Date</div>
                                            <div class="summary-value"><?= formatDate($reservation['check_in_date']) ?></div>
                                        </div>
                                        
                                        <div class="summary-item">
                                            <div class="summary-label">Check-out Date</div>
                                            <div class="summary-value"><?= formatDate($reservation['check_out_date']) ?></div>
                                        </div>
                                        
                                        <div class="summary-item">
                                            <div class="summary-label">Total Nights</div>
                                            <div class="summary-value"><?= $reservation['total_nights'] ?> night(s)</div>
                                        </div>
                                    </div>
                                    
                                    <div class="total-amount">
                                        <div class="total-label">Total Amount to Pay</div>
                                        <div class="total-value">₱<?= number_format($reservation['total_price'], 2) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Form Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-credit-card mr-2"></i> Payment Details</h3>
                            </div>
                            <div class="card-body">
                                <!-- Time Limit Warning -->
                                <div class="time-limit">
                                    <h5 class="text-warning">
                                        <i class="fas fa-clock mr-2"></i>24-Hour Time Limit
                                    </h5>
                                    <p>Please upload payment proof within 24 hours to avoid cancellation.</p>
                                    <div class="countdown" id="countdownTimer">
                                        <!-- Countdown will be updated by JavaScript -->
                                    </div>
                                </div>
                                
                                <form method="POST" enctype="multipart/form-data" id="paymentForm" novalidate>
                                    <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>">
                                    
                                    <!-- Payment Methods -->
                                    <div class="payment-methods">
                                        <div class="payment-method">
                                            <div class="payment-icon gcash">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div>GCash</div>
                                        </div>
                                        <div class="payment-method">
                                            <div class="payment-icon maya">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                            <div>Maya</div>
                                        </div>
                                        <div class="payment-method">
                                            <div class="payment-icon bank">
                                                <i class="fas fa-university"></i>
                                            </div>
                                            <div>Bank Transfer</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reference Number -->
                                    <div class="form-group">
                                        <label for="reference_number">
                                            <i class="fas fa-hashtag mr-2" style="color: var(--primary-turquoise);"></i>
                                            Transaction Reference Number
                                        </label>
                                        <input type="text" 
                                               id="reference_number" 
                                               name="reference_number" 
                                               class="form-control"
                                               placeholder="Enter reference number from your payment"
                                               value="<?= htmlspecialchars($_POST['reference_number'] ?? '') ?>"
                                               required>
                                        <small class="text-muted">This is the transaction ID from GCash, Maya, or your bank</small>
                                    </div>
                                    
                                    <!-- File Upload -->
                                    <div class="form-group">
                                        <label><i class="fas fa-receipt mr-2" style="color: var(--primary-turquoise);"></i> Payment Proof</label>
                                        
                                        <div class="upload-container">
                                            <div class="upload-area" id="uploadArea">
                                                <div class="upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <h5 class="mb-2">Click to select payment proof</h5>
                                                <p class="mb-0">or drag and drop here</p>
                                                <p class="text-muted" style="font-size: 14px;">JPG, PNG, or PDF (max 5MB)</p>
                                            </div>
                                            
                                            <input type="file" 
                                                   id="payment_proof" 
                                                   name="payment_proof" 
                                                   accept="image/jpeg,image/jpg,image/png,application/pdf"
                                                   required
                                                   style="display: none;">
                                        </div>
                                        
                                        <!-- Preview Container -->
                                        <div id="previewContainer" class="preview-container" style="display: none;">
                                            <h5>Preview:</h5>
                                            <div id="previewContent" class="text-center"></div>
                                            <div id="fileInfo" class="file-info"></div>
                                        </div>
                                        
                                        <div class="file-size-info">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Maximum file size: 5MB • Accepted formats: JPG, PNG, PDF
                                        </div>
                                    </div>
                                    
                                    <!-- Instructions -->
                                    <div class="instructions-box">
                                        <h5><i class="fas fa-info-circle mr-2"></i> Payment Instructions</h5>
                                        <ol>
                                            <li>Send payment to one of the supported methods above</li>
                                            <li>Take a screenshot or photo of the payment confirmation</li>
                                            <li>Upload the image or PDF file above</li>
                                            <li>Enter the transaction reference number</li>
                                            <li>Click "Submit Payment Proof" to complete</li>
                                        </ol>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div id="progressContainer" class="progress-container">
                                        <div class="progress">
                                            <div class="progress-bar" id="progressBar"></div>
                                        </div>
                                        <div class="progress-text" id="progressText">Uploading...</div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                            <span class="btn-text">
                                                <i class="fas fa-paper-plane mr-1"></i> Submit Payment Proof
                                            </span>
                                            <span class="submit-loader">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                        
                                        <a href="my-reservations.php" class="btn btn-secondary btn-lg ml-2">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Important Notes -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-exclamation-circle mr-2"></i> Important Notes</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <h5><i class="fas fa-shield-alt mr-2"></i> Security Reminder</h5>
                                    <ul class="mb-0">
                                        <li>Do not share your payment details with anyone</li>
                                        <li>Only use official resort payment channels</li>
                                        <li>Keep your transaction reference number secure</li>
                                        <li>You will receive email confirmation after submission</li>
                                    </ul>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-question-circle mr-2"></i> Need Help?</h5>
                                    <p class="mb-2">If you encounter any issues with payment:</p>
                                    <ul class="mb-0">
                                        <li>Contact resort support: <strong>support@auraluxeresort.com</strong></li>
                                        <li>Call: <strong>(02) 1234-5678</strong></li>
                                        <li>Visit the front desk for assistance</li>
                                    </ul>
                                </div>
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
$(document).ready(function() {
    const form = $('#paymentForm');
    const fileInput = $('#payment_proof');
    const uploadArea = $('#uploadArea');
    const previewContainer = $('#previewContainer');
    const previewContent = $('#previewContent');
    const fileInfo = $('#fileInfo');
    const submitBtn = $('#submitBtn');
    const progressContainer = $('#progressContainer');
    const progressBar = $('#progressBar');
    const progressText = $('#progressText');
    const ajaxMessages = $('#ajax-messages');
    const classicMessages = $('#classic-messages');
    
    // Hide classic messages initially
    classicMessages.hide();
    
    // Initialize countdown timer
    initCountdownTimer();
    
    // Click upload area to trigger file input
    uploadArea.on('click', function() {
        fileInput.click();
    });
    
    // Handle file selection
    fileInput.on('change', function() {
        if (this.files && this.files[0]) {
            previewFile(this.files[0]);
        }
    });
    
    // Drag and drop functionality
    uploadArea.on('dragover', function(e) {
        e.preventDefault();
        uploadArea.addClass('dragover');
    });
    
    uploadArea.on('dragleave', function() {
        uploadArea.removeClass('dragover');
    });
    
    uploadArea.on('drop', function(e) {
        e.preventDefault();
        uploadArea.removeClass('dragover');
        
        if (e.originalEvent.dataTransfer.files.length) {
            fileInput[0].files = e.originalEvent.dataTransfer.files;
            previewFile(e.originalEvent.dataTransfer.files[0]);
        }
    });
    
    // Preview selected file
    function previewFile(file) {
        // Validate file
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!validTypes.includes(file.type)) {
            showMessage('error', 'Invalid File Type', 'Please upload JPG, PNG, or PDF files only.');
            return;
        }
        
        if (file.size > maxSize) {
            showMessage('error', 'File Too Large', 'Maximum file size is 5MB.');
            return;
        }
        
        // Show preview
        previewContainer.show();
        
        // Clear previous preview
        previewContent.empty();
        fileInfo.empty();
        
        // File info
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.html(`
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${file.name}</strong><br>
                    <small class="text-muted">${(file.type.split('/')[1] || 'PDF').toUpperCase()} • ${fileSize} MB</small>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="clearFile()">
                    <i class="fas fa-times"></i> Remove
                </button>
            </div>
        `);
        
        // Image preview
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContent.html(`
                    <img src="${e.target.result}" class="preview-image" alt="Payment Proof Preview">
                `);
            };
            reader.readAsDataURL(file);
        } else {
            // PDF preview
            previewContent.html(`
                <div class="preview-pdf">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <p>PDF Document - ${fileSize} MB</p>
            `);
        }
    }
    
    // Clear file selection
    window.clearFile = function() {
        fileInput.val('');
        previewContainer.hide();
        previewContent.empty();
        fileInfo.empty();
    };
    
    // Show message
    function showMessage(type, title, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check' : 'fa-ban';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas ${icon}"></i> ${title}</h5>
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
    
    // Initialize countdown timer
    function initCountdownTimer() {
        // Set deadline to 24 hours from now
        const deadline = new Date();
        deadline.setHours(deadline.getHours() + 24);
        
        function updateCountdown() {
            const now = new Date();
            const diff = deadline - now;
            
            if (diff <= 0) {
                $('#countdownTimer').html('TIME EXPIRED');
                $('#submitBtn').prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                showMessage('error', 'Time Expired', '24-hour window has expired. Please contact support.');
                return;
            }
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            $('#countdownTimer').html(
                `<span class="badge bg-warning" style="font-size: 1.2em;">${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}</span>`
            );
        }
        
        // Update every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    // Handle form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const referenceNumber = $('#reference_number').val().trim();
        if (!referenceNumber) {
            showMessage('error', 'Reference Number Required', 'Please enter your transaction reference number.');
            return;
        }
        
        if (!fileInput[0].files.length) {
            showMessage('error', 'No File Selected', 'Please select a payment proof file.');
            return;
        }
        
        // Confirm submission
        if (!confirm('Are you sure you want to submit this payment proof? This action cannot be undone.')) {
            return;
        }
        
        // Disable form
        submitBtn.addClass('ajax-loading').prop('disabled', true);
        progressContainer.show();
        progressBar.css('width', '0%');
        progressText.text('Uploading...');
        
        // Create FormData
        const formData = new FormData(this);
        
        // Add AJAX identifier
        formData.append('ajax', '1');
        
        // Send AJAX request with progress tracking
        const xhr = new XMLHttpRequest();
        
        // Progress tracking
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.css('width', percentComplete + '%');
                progressText.text(`Uploading: ${Math.round(percentComplete)}%`);
            }
        });
        
        // Request completed
        xhr.addEventListener('load', function() {
            try {
                const response = JSON.parse(xhr.responseText);
                
                // Enable form
                submitBtn.removeClass('ajax-loading').prop('disabled', false);
                
                if (response.success) {
                    progressBar.css('width', '100%');
                    progressText.text('Processing complete!');
                    progressBar.css('background', 'linear-gradient(135deg, #28a745, #20c997)');
                    
                    // Show success message
                    showMessage('success', 'Payment Uploaded!', response.message);
                    
                    // Redirect if needed
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 2000);
                    }
                } else {
                    progressBar.css('background', 'linear-gradient(135deg, #dc3545, #e83e8c)');
                    progressText.text('Upload failed');
                    
                    // Show errors
                    if (response.message) {
                        showMessage('error', 'Error!', response.message);
                    }
                    
                    // Show field-specific errors
                    if (response.errors) {
                        for (const field in response.errors) {
                            if (field === 'payment_proof') {
                                showMessage('error', 'Upload Error', response.errors[field]);
                            } else if (field === 'reference_number') {
                                showMessage('error', 'Reference Number Error', response.errors[field]);
                            } else {
                                showMessage('error', 'Error', response.errors[field]);
                            }
                        }
                    }
                }
            } catch (error) {
                // JSON parse error
                showMessage('error', 'Server Error', 'Unable to process response. Please try again.');
                submitBtn.removeClass('ajax-loading').prop('disabled', false);
                progressContainer.hide();
            }
        });
        
        // Request error
        xhr.addEventListener('error', function() {
            showMessage('error', 'Network Error', 'Unable to connect to server. Please check your connection.');
            submitBtn.removeClass('ajax-loading').prop('disabled', false);
            progressContainer.hide();
        });
        
        // Send request
        xhr.open('POST', 'ajax_upload_payment.php', true);
        xhr.send(formData);
    });
    
    // Auto-hide classic messages after 5 seconds
    setTimeout(function() {
        classicMessages.find('.alert').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 5000);
});
</script>
</body>
</html>