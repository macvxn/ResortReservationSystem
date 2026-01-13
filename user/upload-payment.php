<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
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

// Handle form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_number = $_POST['reference_number'] ?? '';
    
    // Basic validation
    if (empty($reference_number)) {
        $errors[] = "Reference number is required.";
    }
    
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Please upload payment proof.";
    } else {
        $file = $_FILES['payment_proof'];
    }
    
    // If no errors, process
    if (empty($errors)) {
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
                $errors[] = "Failed to save payment. Please try again.";
            }
        } else {
            $errors[] = $upload_result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment - Resort Reservation System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .reservation-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2em;
            color: #28a745;
        }
        
        .upload-area {
            border: 2px dashed #007bff;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            cursor: pointer;
            margin: 20px 0;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            background: #e9ecef;
            border-color: #0056b3;
        }
        
        .upload-area i {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .file-input {
            display: none;
        }
        
        .selected-file {
            margin-top: 15px;
            padding: 10px;
            background: #d4edda;
            border-radius: 5px;
            color: #155724;
            display: none;
        }
        
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .error-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2><i class="fas fa-file-upload"></i> Upload Payment Proof</h2>
        
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <strong><i class="fas fa-exclamation-triangle"></i> Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="payment-container">
            <!-- Reservation Summary -->
            <div class="reservation-summary">
                <h4>Reservation #<?= str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT) ?></h4>
                <div class="summary-item">
                    <span>Cottage:</span>
                    <span><?= htmlspecialchars($reservation['cottage_name']) ?></span>
                </div>
                <div class="summary-item">
                    <span>Check-in:</span>
                    <span><?= formatDate($reservation['check_in_date']) ?></span>
                </div>
                <div class="summary-item">
                    <span>Check-out:</span>
                    <span><?= formatDate($reservation['check_out_date']) ?></span>
                </div>
                <div class="summary-item">
                    <span>Total Nights:</span>
                    <span><?= $reservation['total_nights'] ?></span>
                </div>
                <div class="summary-item">
                    <span>Total Amount:</span>
                    <span>₱<?= number_format($reservation['total_price'], 2) ?></span>
                </div>
            </div>
            
            <!-- Payment Form -->
            <form method="POST" enctype="multipart/form-data" id="paymentForm">
                <!-- Reference Number -->
                <div class="form-group">
                    <label for="reference_number">
                        <i class="fas fa-hashtag"></i> Transaction Reference Number *
                    </label>
                    <input type="text" 
                           id="reference_number" 
                           name="reference_number" 
                           class="form-control"
                           placeholder="Enter reference number from GCash/Maya/Bank"
                           value="<?= htmlspecialchars($_POST['reference_number'] ?? '') ?>"
                           required>
                    <small>This is the transaction ID from your payment</small>
                </div>
                
                <!-- File Upload -->
                <div class="form-group">
                    <label><i class="fas fa-receipt"></i> Payment Proof *</label>
                    
                    <div class="upload-area" onclick="document.getElementById('payment_proof').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4>Click to upload payment proof</h4>
                        <p>Supported: JPG, PNG, PDF (max 5MB)</p>
                        <div id="selectedFileName" class="selected-file">
                            <i class="fas fa-file"></i> <span id="fileName">No file selected</span>
                        </div>
                    </div>
                    
                    <input type="file" 
                           id="payment_proof" 
                           name="payment_proof" 
                           class="file-input"
                           accept=".jpg,.jpeg,.png,.pdf"
                           required
                           onchange="showFileName(this)">
                </div>
                
                <!-- Instructions -->
                <div class="form-group">
                    <h4><i class="fas fa-info-circle"></i> Payment Instructions</h4>
                    <ol>
                        <li>Send payment via GCash to: <strong>0917 123 4567</strong></li>
                        <li>Take screenshot of payment confirmation</li>
                        <li>Upload screenshot above</li>
                        <li>Enter transaction reference number</li>
                        <li>Click submit to complete</li>
                    </ol>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; padding: 15px;">
                    <i class="fas fa-paper-plane"></i> Submit Payment Proof
                </button>
                
                <p style="text-align: center; margin-top: 15px; color: #666;">
                    <i class="fas fa-clock"></i> Please upload within 24 hours
                </p>
            </form>
        </div>
    </div>
    
    <script>
    function showFileName(input) {
        const fileNameDiv = document.getElementById('selectedFileName');
        const fileNameSpan = document.getElementById('fileName');
        
        if (input.files.length > 0) {
            fileNameSpan.textContent = input.files[0].name;
            fileNameDiv.style.display = 'block';
        } else {
            fileNameDiv.style.display = 'none';
        }
    }
    
    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const referenceNumber = document.getElementById('reference_number').value.trim();
        const fileInput = document.getElementById('payment_proof');
        
        if (referenceNumber.length < 3) {
            e.preventDefault();
            alert('Reference number must be at least 3 characters.');
            return false;
        }
        
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Please select a payment proof file.');
            return false;
        }
        
        // Simple file size check (5MB = 5 * 1024 * 1024 bytes)
        const file = fileInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('File is too large. Maximum size is 5MB.');
            return false;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            e.preventDefault();
            alert('Invalid file type. Only JPG, PNG, and PDF are allowed.');
            return false;
        }
        
        // Confirm submission
        if (!confirm('Submit payment proof? This cannot be undone.')) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>