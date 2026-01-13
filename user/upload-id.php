<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/ocr.php';
require_once '../includes/email.php'; // Add this

requireLogin();

$profile = getUserProfile($_SESSION['user_id']);
$error = '';
$success = '';

// Check if profile is filled
if (empty($profile['full_name']) || empty($profile['id_number'])) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    <title>Upload ID - Resort Reservation</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9ff;
            margin: 20px 0;
            cursor: pointer;
        }
        .upload-area:hover {
            background: #f0f2ff;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            margin: 20px 0;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        .current-id {
            margin: 20px 0;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📸 Upload Government ID</h2>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>

        <?php if (isset($_GET['profile_updated'])): ?>
            <div class="alert alert-success">
                Profile updated! Now upload your government ID.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Current ID Status -->
        <?php if (!empty($profile['id_image_path'])): ?>
            <div class="current-id">
                <strong>Current Status:</strong> 
                <?php echo getVerificationBadge($profile['verification_status']); ?>
                
                <?php if ($profile['verification_status'] == 'rejected' && !empty($profile['admin_remarks'])): ?>
                    <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                        <strong>Admin Remarks:</strong><br>
                        <?php echo htmlspecialchars($profile['admin_remarks']); ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 15px;">
                    <img 
                        src="../uploads/ids/<?php echo htmlspecialchars($profile['id_image_path']); ?>" 
                        alt="Current ID"
                        class="preview-image"
                    >
                </div>
                
                <?php if ($profile['verification_status'] == 'pending_verification'): ?>
                    <p style="color: #666; margin-top: 10px;">
                        Your ID is under review. Please wait for admin verification.
                    </p>
                <?php endif; ?>
                
                <?php if ($profile['verification_status'] == 'verified'): ?>
                    <p style="color: #28a745; margin-top: 10px;">
                        ✓ Your ID has been verified! You can now make reservations.
                    </p>
                    <a href="cottages.php" class="btn btn-success">Browse Cottages</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <?php if ($profile['verification_status'] != 'verified' && $profile['verification_status'] != 'pending_verification'): ?>
            <div class="alert alert-info">
    <strong>📋 Before You Upload:</strong>
    <ol style="margin: 10px 0; padding-left: 20px;">
        <li>Make sure your ID is <strong>clear and readable</strong></li>
        <li>Your <strong>full name</strong> must be visible</li>
        <li>Your <strong>ID number</strong> must be visible</li>
        <li>All corners of the ID should be in frame</li>
        <li>Avoid glare or shadows</li>
    </ol>
    
    <strong>✅ Accepted IDs:</strong>
    <ul style="margin: 10px 0; padding-left: 20px;">
        <li>Driver's License</li>
        <li>Passport</li>
        <li>National ID (PhilSys)</li>
        <li>Postal ID</li>
        <li>Voter's ID</li>
        <li>UMID</li>
        <li>SSS ID</li>
        <li>PRC ID</li>
    </ul>
    
    <strong>📏 File Requirements:</strong>
    <ul style="margin: 10px 0; padding-left: 20px;">
        <li>Format: JPG, PNG, or PDF</li>
        <li>Maximum size: 5MB</li>
        <li>Minimum resolution: 800x600px recommended</li>
    </ul>
</div>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" onclick="document.getElementById('fileInput').click();">
                    <p style="font-size: 48px; margin: 0;">📁</p>
                    <p style="margin: 10px 0;"><strong>Click to select ID image</strong></p>
                    <p style="color: #666; font-size: 14px;">or drag and drop here</p>
                    <p style="color: #999; font-size: 12px;">JPG, PNG, or PDF (max 5MB)</p>
                </div>

                <input 
                    type="file" 
                    id="fileInput"
                    name="id_image" 
                    accept="image/jpeg,image/png,application/pdf"
                    required
                    style="display: none;"
                    onchange="previewImage(this)"
                >

                <div id="preview" style="display: none;">
                    <h4>Preview:</h4>
                    <img id="previewImg" class="preview-image">
                    <p id="fileName" style="color: #666;"></p>
                </div>

                <button type="submit" id="submitBtn" style="display: none;">
                    Upload ID for Verification
                </button>
            </form>

            <script>
                function previewImage(input) {
                    const preview = document.getElementById('preview');
                    const previewImg = document.getElementById('previewImg');
                    const fileName = document.getElementById('fileName');
                    const submitBtn = document.getElementById('submitBtn');

                    if (input.files && input.files[0]) {
                        const file = input.files[0];
                        
                        // Show file name
                        fileName.textContent = '📄 ' + file.name;
                        
                        // Only preview images (not PDF)
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                previewImg.src = e.target.result;
                                preview.style.display = 'block';
                                submitBtn.style.display = 'block';
                            }
                            reader.readAsDataURL(file);
                        } else {
                            preview.style.display = 'block';
                            previewImg.style.display = 'none';
                            submitBtn.style.display = 'block';
                        }
                    }
                }

                // Drag and drop
                const uploadArea = document.querySelector('.upload-area');
                
                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.style.background = '#e8eaff';
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.style.background = '#f8f9ff';
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.style.background = '#f8f9ff';
                    
                    const fileInput = document.getElementById('fileInput');
                    fileInput.files = e.dataTransfer.files;
                    previewImage(fileInput);
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>