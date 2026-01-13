<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/ocr.php';

requireLogin();
requireAdmin();

$success = '';
$error = '';

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profile_id = (int)$_POST['profile_id'];
    $action = $_POST['action'];
    $remarks = clean($_POST['remarks'] ?? '');
    
    if ($action == 'approve') {
        $stmt = $pdo->prepare("
            UPDATE user_profiles 
            SET verification_status = 'verified',
                verified_by = ?,
                verified_at = NOW(),
                admin_remarks = NULL
            WHERE profile_id = ?
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $profile_id])) {
            logAction($_SESSION['user_id'], 'user_verified', 'user_profiles', $profile_id);
            $success = "User verified successfully!";
        }
        
    } elseif ($action == 'reject') {
        if (empty($remarks)) {
            $error = "Please provide a reason for rejection";
        } else {
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET verification_status = 'rejected',
                    verified_by = ?,
                    verified_at = NOW(),
                    admin_remarks = ?
                WHERE profile_id = ?
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $remarks, $profile_id])) {
                logAction($_SESSION['user_id'], 'user_rejected', 'user_profiles', $profile_id);
                $success = "User verification rejected";
            }
        }
    }
}

// Get pending verifications
$pending = getPendingVerifications();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Users - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            color: white;
        }
        .admin-nav h2 {
            color: white;
            margin: 0 0 10px 0;
        }
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
        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        .verification-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .user-info {
            display: grid;
            gap: 10px;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .id-preview {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 15px 0;
            cursor: pointer;
        }
        .confidence-box {
            background: #f8f9fa;
            border-left: 4px solid;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .confidence-high {
            border-color: #28a745;
            background: #d4edda;
        }
        .confidence-medium {
            border-color: #ffc107;
            background: #fff3cd;
        }
        .confidence-low {
            border-color: #dc3545;
            background: #f8d7da;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
        }
        .modal img {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            margin-top: 5%;
        }
        .close-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-nav">
            <h2>👨‍💼 Admin Panel</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="verify-users.php">Verify Users</a>
                <a href="reservations.php">Reservations</a>
                <a href="cottages.php">Manage Cottages</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
<h3>🔍 User Verification Management</h3>

        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="number"><?php echo $stats['total_verified'] ?? 0; ?></div>
                <div class="label">Total Verified</div>
            </div>
            <div class="stat-item">
                <div class="number" style="color: #28a745;">
                    <?php echo $stats['auto_approved'] ?? 0; ?>
                </div>
                <div class="label">Auto-Approved (AI)</div>
            </div>
            <div class="stat-item">
                <div class="number" style="color: #17a2b8;">
                    <?php echo $stats['manual_approved'] ?? 0; ?>
                </div>
                <div class="label">Manual Approved</div>
            </div>
            <div class="stat-item">
                <div class="number" style="color: #ffc107;">
                    <?php echo count($pending); ?>
                </div>
                <div class="label">Pending Review</div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>ℹ️ Note:</strong> Users with AI confidence score ≥50% are automatically verified. 
            Only low-confidence submissions appear here for manual review.
        </div>

        <?php if (empty($pending)): ?>
            <div class="card">
                <p style="text-align: center; color: #666;">
                    ✓ No pending manual verifications
                </p>
                <p style="text-align: center; color: #999; font-size: 14px;">
                    High-confidence IDs are being auto-approved by the AI system.
                </p>
            </div>
        <?php else: ?>
            <!-- Rest of verification cards remain the same -->
            <?php foreach ($pending as $user): ?>
                <?php 
                $ocr_log = getOCRLog($user['profile_id']); 
                $confidence_info = $ocr_log ? getConfidenceLevel($ocr_log['confidence_score']) : null;
                ?>
                
                <!-- Existing verification card code here -->
                
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

        <h3>🔍 Pending User Verifications</h3>
        <p style="color: #666;">Review and approve user ID submissions</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
            <div class="card">
                <p style="text-align: center; color: #666;">
                    ✓ No pending verifications at the moment
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($pending as $user): ?>
                <?php 
                $ocr_log = getOCRLog($user['profile_id']); 
                $confidence_info = $ocr_log ? getConfidenceLevel($ocr_log['confidence_score']) : null;
                ?>
                
                <div class="verification-card">
                    <h3>📋 Verification Request</h3>
                    
                    <div class="user-info">
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Full Name:</span>
                            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span><?php echo htmlspecialchars($user['phone_number'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Registered:</span>
                            <span><?php echo formatDateTime($user['registration_date']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">ID Uploaded:</span>
                            <span><?php echo formatDateTime($user['updated_at']); ?></span>
                        </div>
                    </div>

                    <!-- OCR Confidence Score -->
                    <?php if ($ocr_log): ?>
                        <div class="confidence-box confidence-<?php echo strtolower($confidence_info['level']); ?>">
                            <strong>🤖 AI Confidence Score</strong>
                            <div style="margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 24px; font-weight: bold;">
                                        <?php echo $confidence_info['icon']; ?> 
                                        <?php echo number_format($ocr_log['confidence_score'], 1); ?>%
                                    </span>
                                    <span style="font-weight: bold; color: <?php echo $confidence_info['color']; ?>">
                                        <?php echo $confidence_info['level']; ?> Confidence
                                    </span>
                                </div>
                                <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                                    AI has analyzed the ID and compared it with user input. 
                                    <?php if ($ocr_log['confidence_score'] >= 80): ?>
                                        The information appears to match well.
                                    <?php elseif ($ocr_log['confidence_score'] >= 50): ?>
                                        Some information matches, but manual review recommended.
                                    <?php else: ?>
                                        Low match detected. Careful verification required.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            ℹ️ OCR processing not available for this submission
                        </div>
                    <?php endif; ?>

                    <!-- ID Image Preview -->
                    <h4>Uploaded Government ID:</h4>
                    <img 
                        src="../uploads/ids/<?php echo htmlspecialchars($user['id_image_path']); ?>" 
                        alt="Government ID"
                        class="id-preview"
                        onclick="openModal(this.src)"
                    >
                    <p style="font-size: 12px; color: #666; text-align: center;">
                        Click image to enlarge
                    </p>

                    <!-- Verification Form -->
                    <form method="POST" id="verifyForm<?php echo $user['profile_id']; ?>">
                        <input type="hidden" name="profile_id" value="<?php echo $user['profile_id']; ?>">
                        
                        <div class="form-group">
                            <label>Admin Remarks (required for rejection):</label>
                            <textarea 
                                name="remarks" 
                                rows="3"
                                placeholder="Provide reason if rejecting (e.g., 'ID image is blurry', 'Name does not match', etc.)"
                            ></textarea>
                        </div>

                        <div class="action-buttons">
                            <button 
                                type="button" 
                                class="btn btn-success"
                                onclick="submitVerification(<?php echo $user['profile_id']; ?>, 'approve')"
                            >
                                ✓ Approve
                            </button>
                            <button 
                                type="button" 
                                class="btn btn-danger"
                                onclick="submitVerification(<?php echo $user['profile_id']; ?>, 'reject')"
                            >
                                ✗ Reject
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close-modal">&times;</span>
        <img id="modalImage">
    </div>

    <script>
        function submitVerification(profileId, action) {
            const form = document.getElementById('verifyForm' + profileId);
            const remarks = form.querySelector('textarea[name="remarks"]').value.trim();
            
            if (action === 'reject' && remarks === '') {
                alert('Please provide a reason for rejection');
                return;
            }
            
            if (action === 'approve') {
                if (!confirm('Approve this user verification?')) {
                    return;
                }
            } else {
                if (!confirm('Reject this user verification?')) {
                    return;
                }
            }
            
            // Add action to form
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            form.submit();
        }

        function openModal(src) {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalImage').src = src;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
    </script>
</body>
</html>