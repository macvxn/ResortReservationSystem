<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/ocr.php';
require_once '../includes/email.php';

requireLogin();

$profile = getUserProfile($_SESSION['user_id']);
$error = '';
$success = '';

// Check if profile is filled
if (empty($profile['full_name']) || empty($profile['id_number'])) {
    header("Location: profile.php");
    exit();
}

// Set AdminLTE page variables
$page_title = 'Upload Government ID - Aura Luxe Resort';

// Classic POST handling (fallback when JS is disabled)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    if (!isset($_FILES['id_image']) || $_FILES['id_image']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please select an ID image to upload";
    } else {
        $file = $_FILES['id_image'];
        
        // Upload file
        $upload_result = uploadFile($file, 'ids');
        
        if ($upload_result['success']) {
            $filename = $upload_result['filename'];
            
            // Update profile with ID image
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET id_image_path = ?, 
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            
            if ($stmt->execute([$filename, $_SESSION['user_id']])) {
                logAction($_SESSION['user_id'], 'id_uploaded', 'user_profiles', $profile['profile_id']);
                
                // Process OCR (with auto-approval)
                try {
                    $ocr_result = processIDWithOCR($filename, $profile['profile_id']);
                    
                    if ($ocr_result['success']) {
                        
                        // Check if auto-approved
                        if ($ocr_result['auto_approved']) {
                            // Send verification success email
                            $email_result = sendWelcomeEmail(
                                $_SESSION['email'], 
                                $profile['full_name']
                            );
                            
                            header("Location: dashboard.php?auto_verified=1&confidence=" . $ocr_result['confidence']);
                            exit();
                        } else {
                            // Manual review required
                            header("Location: dashboard.php?manual_review=1&confidence=" . $ocr_result['confidence']);
                            exit();
                        }
                    } else {
                        // OCR failed - still goes to manual review
                        error_log("OCR processing failed: " . $ocr_result['message']);
                        header("Location: dashboard.php?manual_review=1");
                        exit();
                    }
                } catch (Exception $e) {
                    error_log("OCR exception: " . $e->getMessage());
                    header("Location: dashboard.php?manual_review=1");
                    exit();
                }
            } else {
                $error = "Failed to save ID information";
            }
        } else {
            $error = $upload_result['message'];
        }
    }
}
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
        
        /* Card styling */
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
        
        .card-body {
            background-color: var(--background-cream);
        }
        
        /* Upload area styling */
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
        
        .current-id-status {
            background: rgba(64, 224, 208, 0.1);
            border: 1px solid var(--primary-turquoise);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
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
        
        /* Verification badge */
        .verification-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .badge-verified {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }
        
        .badge-pending {
            background-color: rgba(255, 211, 0, 0.2);
            color: #b08d00;
            border: 1px solid var(--accent-yellow);
        }
        
        .badge-unverified {
            background-color: rgba(252, 108, 133, 0.2);
            color: var(--accent-watermelon);
            border: 1px solid var(--accent-watermelon);
        }
        
        .badge-rejected {
            background-color: rgba(220, 53, 69, 0.2);
            color: var(--accent-watermelon);
            border: 1px solid var(--accent-watermelon);
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
        
        .alert-info {
            background-color: rgba(0, 191, 255, 0.1);
            border-left-color: var(--secondary-aqua);
        }
        
        .alert-warning {
            background-color: rgba(255, 211, 0, 0.1);
            border-left-color: var(--accent-yellow);
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
        
        .btn-success {
            background: linear-gradient(135deg, var(--accent-coral), #ff9a80);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #ff6b3d, var(--accent-coral));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 127, 80, 0.4);
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
        
        /* Requirements list */
        .requirements-list {
            background: rgba(64, 224, 208, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .requirements-list h5 {
            color: var(--primary-turquoise);
            margin-bottom: 15px;
        }
        
        /* File info */
        .file-info {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        /* Loader */
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
                                <i class="fas fa-id-card mr-2"></i>Upload Government ID
                            </h1>
                            <p class="mb-0 mt-2" style="opacity: 0.9;">
                                <a href="dashboard.php" class="back-link" style="color: white; text-decoration: underline;">
                                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
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
                    <?php if (isset($_GET['profile_updated'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-check"></i> Profile Updated!</h5>
                            Profile updated! Now upload your government ID.
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- AJAX Messages Container -->
                <div id="ajax-messages" style="display: none;"></div>

                <!-- Current ID Status -->
                <?php if (!empty($profile['id_image_path'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-history mr-2"></i>Current Status</h3>
                                </div>
                                <div class="card-body">
                                    <div class="current-id-status">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="mb-3">
                                                    <strong>Verification Status:</strong>
                                                    <?php 
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch($profile['verification_status']) {
                                                        case 'verified':
                                                            $status_class = 'badge-verified';
                                                            $status_text = '✅ Verified';
                                                            break;
                                                        case 'pending_verification':
                                                            $status_class = 'badge-pending';
                                                            $status_text = '🕐 Pending Verification';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'badge-rejected';
                                                            $status_text = '✗ Rejected';
                                                            break;
                                                        default:
                                                            $status_class = 'badge-unverified';
                                                            $status_text = '⚠️ Not Verified';
                                                    }
                                                    ?>
                                                    <span class="verification-badge <?php echo $status_class; ?> ml-2">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </h5>
                                                
                                                <?php if ($profile['verification_status'] == 'rejected' && !empty($profile['admin_remarks'])): ?>
                                                    <div class="alert alert-warning">
                                                        <h6><i class="fas fa-exclamation-triangle mr-1"></i> Admin Remarks:</h6>
                                                        <?php echo nl2br(htmlspecialchars($profile['admin_remarks'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($profile['verification_status'] == 'pending_verification'): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-clock mr-2"></i>
                                                        Your ID is under review. Please wait for admin verification.
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($profile['verification_status'] == 'verified'): ?>
                                                    <div class="alert alert-success">
                                                        <i class="fas fa-check-circle mr-2"></i>
                                                        Your ID has been verified! You can now make reservations.
                                                    </div>
                                                    <a href="cottages.php" class="btn btn-success">
                                                        <i class="fas fa-home mr-1"></i>Browse Cottages
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <?php if (strpos($profile['id_image_path'], '.pdf') !== false): ?>
                                                    <div class="preview-pdf">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </div>
                                                    <p>PDF Document</p>
                                                <?php else: ?>
                                                    <img 
                                                        src="../uploads/ids/<?php echo htmlspecialchars($profile['id_image_path']); ?>" 
                                                        alt="Current ID"
                                                        class="preview-image"
                                                        style="max-height: 200px;"
                                                    >
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Upload Form (only if not verified/pending) -->
                <?php if ($profile['verification_status'] != 'verified' && $profile['verification_status'] != 'pending_verification'): ?>
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Requirements Card -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Requirements</h3>
                                </div>
                                <div class="card-body">
                                    <div class="requirements-list">
                                        <h5><i class="fas fa-camera mr-2"></i>Before You Upload:</h5>
                                        <ul>
                                            <li>Make sure your ID is <strong>clear and readable</strong></li>
                                            <li>Your <strong>full name</strong> must be visible</li>
                                            <li>Your <strong>ID number</strong> must be visible</li>
                                            <li>All corners of the ID should be in frame</li>
                                            <li>Avoid glare or shadows</li>
                                        </ul>
                                        
                                        <h5 class="mt-4"><i class="fas fa-id-badge mr-2"></i>Accepted IDs:</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul>
                                                    <li>Driver's License</li>
                                                    <li>Passport</li>
                                                    <li>National ID (PhilSys)</li>
                                                    <li>Postal ID</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul>
                                                    <li>Voter's ID</li>
                                                    <li>UMID</li>
                                                    <li>SSS ID</li>
                                                    <li>PRC ID</li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <h5 class="mt-4"><i class="fas fa-file-alt mr-2"></i>File Requirements:</h5>
                                        <ul>
                                            <li><strong>Format:</strong> JPG, PNG, or PDF</li>
                                            <li><strong>Maximum size:</strong> 5MB</li>
                                            <li><strong>Minimum resolution:</strong> 800x600px recommended</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Upload Card -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-upload mr-2"></i>Upload ID</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" id="uploadForm" novalidate>
                                        <!-- Upload Area -->
                                        <div class="upload-area" id="uploadArea">
                                            <div class="upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <p class="mb-2"><strong>Click to select ID image</strong></p>
                                            <p class="text-muted mb-0">or drag and drop here</p>
                                            <p class="text-muted" style="font-size: 12px;">JPG, PNG, or PDF (max 5MB)</p>
                                        </div>

                                        <input 
                                            type="file" 
                                            id="fileInput"
                                            name="id_image" 
                                            accept="image/jpeg,image/jpg,image/png,application/pdf"
                                            required
                                            style="display: none;"
                                        >

                                        <!-- Preview Container -->
                                        <div id="previewContainer" class="preview-container" style="display: none;">
                                            <h5>Preview:</h5>
                                            <div id="previewContent" class="text-center"></div>
                                            <div id="fileInfo" class="file-info"></div>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div id="progressContainer" class="progress-container">
                                            <div class="progress">
                                                <div class="progress-bar" id="progressBar"></div>
                                            </div>
                                            <div class="progress-text" id="progressText">Uploading...</div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="form-group mt-3">
                                            <button type="submit" class="btn btn-primary btn-block" id="submitBtn" style="display: none;">
                                                <span class="btn-text">
                                                    <i class="fas fa-upload mr-1"></i>Upload ID for Verification
                                                </span>
                                                <span class="submit-loader">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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

<!-- Upload ID AJAX Script -->
<script>
$(document).ready(function() {
    const form = $('#uploadForm');
    const fileInput = $('#fileInput');
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
        submitBtn.show();
        
        // Clear previous preview
        previewContent.empty();
        fileInfo.empty();
        
        // File info
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.html(`
            <div class="d-flex justify-content-between">
                <div>
                    <strong>${file.name}</strong><br>
                    <small class="text-muted">${(file.type.split('/')[1] || 'PDF').toUpperCase()} • ${fileSize} MB</small>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="clearFile()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);
        
        // Image preview
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContent.html(`
                    <img src="${e.target.result}" class="preview-image" alt="Preview">
                `);
            };
            reader.readAsDataURL(file);
        } else {
            // PDF preview
            previewContent.html(`
                <div class="preview-pdf">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <p>PDF Document</p>
            `);
        }
    }
    
    // Clear file selection
    window.clearFile = function() {
        fileInput.val('');
        previewContainer.hide();
        submitBtn.hide();
        progressContainer.hide();
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
    
    // Handle form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        if (!fileInput[0].files.length) {
            showMessage('error', 'No File Selected', 'Please select an ID image to upload.');
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
                    showMessage('success', 'Success!', response.message);
                    
                    // Redirect if needed
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 2000);
                    } else {
                        // Refresh page after 3 seconds to show updated status
                        setTimeout(function() {
                            window.location.reload();
                        }, 3000);
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
                            if (field === 'id_image') {
                                showMessage('error', 'Upload Error', response.errors[field]);
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
        xhr.open('POST', 'ajax_upload_id.php', true);
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