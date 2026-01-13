<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireLogin();

// Get cottage ID
$cottage_id = $_GET['id'] ?? 0;
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

// Get cottage images
$stmt = $pdo->prepare("SELECT * FROM cottage_images WHERE cottage_id = ? ORDER BY is_primary DESC");
$stmt->execute([$cottage_id]);
$images = $stmt->fetchAll();

// Get primary image
$primary_image = '';
foreach ($images as $image) {
    if ($image['is_primary']) {
        $primary_image = $image['image_path'];
        break;
    }
}
if (empty($primary_image) && count($images) > 0) {
    $primary_image = $images[0]['image_path'];
}

// Get user verification status
$user_profile = getUserProfile($_SESSION['user_id']);
$is_verified = $user_profile['verification_status'] === 'verified';

// Check upcoming reservations for this cottage (for availability info)
$stmt = $pdo->prepare("
    SELECT check_in_date, check_out_date 
    FROM reservations 
    WHERE cottage_id = ? 
    AND status = 'approved'
    AND check_out_date >= CURDATE()
    ORDER BY check_in_date ASC
    LIMIT 5
");
$stmt->execute([$cottage_id]);
$upcoming_reservations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cottage['cottage_name']) ?> - Resort Reservation System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <div class="container">
        <!-- Back Button -->
        <a href="cottages.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Cottages
        </a>
        
        <div class="cottage-details">
            <!-- Cottage Name and Price -->
            <div class="cottage-header">
                <h2><?= htmlspecialchars($cottage['cottage_name']) ?></h2>
                <div class="price-display">
                    <span class="price">₱<?= number_format($cottage['price_per_night'], 2) ?></span>
                    <span class="price-label">per night</span>
                </div>
            </div>
            
            <!-- Image Gallery -->
            <div class="image-gallery">
                <div class="main-image">
                    <img id="mainImage" src="../uploads/cottages/<?= htmlspecialchars($primary_image) ?>" 
                         alt="<?= htmlspecialchars($cottage['cottage_name']) ?>"
                         onerror="this.src='https://via.placeholder.com/800x400?text=Cottage+Image'">
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="thumbnail-container">
                    <?php foreach ($images as $image): ?>
                    <img src="../uploads/cottages/<?= htmlspecialchars($image['image_path']) ?>" 
                         alt="Thumbnail"
                         class="thumbnail"
                         onclick="changeImage(this.src)"
                         onerror="this.src='https://via.placeholder.com/100x100?text=Image'">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Main Content Grid -->
            <div class="details-grid">
                <!-- Left Column: Details -->
                <div class="details-left">
                    <!-- Description -->
                    <div class="detail-section">
                        <h3><i class="fas fa-info-circle"></i> Description</h3>
                        <p><?= nl2br(htmlspecialchars($cottage['description'])) ?></p>
                    </div>
                    
                    <!-- Capacity & Features -->
                    <div class="detail-section">
                        <h3><i class="fas fa-home"></i> Cottage Features</h3>
                        <div class="features-grid">
                            <div class="feature">
                                <i class="fas fa-users"></i>
                                <div>
                                    <strong>Maximum Guests</strong>
                                    <p><?= $cottage['capacity'] ?> people</p>
                                </div>
                            </div>
                            <div class="feature">
                                <i class="fas fa-tag"></i>
                                <div>
                                    <strong>Price per Night</strong>
                                    <p>₱<?= number_format($cottage['price_per_night'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Reservation Box -->
                <div class="details-right">
                    <div class="reservation-box">
                        <h3><i class="fas fa-calendar-alt"></i> Book This Cottage</h3>
                        
                        <?php if ($is_verified): ?>
                            <div class="price-breakdown">
                                <div class="price-row">
                                    <span>Price per night:</span>
                                    <span>₱<?= number_format($cottage['price_per_night'], 2) ?></span>
                                </div>
                                <hr>
                                <div class="price-row total">
                                    <span><strong>Total per night:</strong></span>
                                    <span><strong>₱<?= number_format($cottage['price_per_night'], 2) ?></strong></span>
                                </div>
                            </div>
                            
                            <a href="reserve.php?cottage_id=<?= $cottage['cottage_id'] ?>" class="btn btn-primary btn-block">
                                <i class="fas fa-calendar-check"></i> Check Availability & Reserve
                            </a>
                            
                            <p class="note">
                                <i class="fas fa-info-circle"></i>
                                You'll be able to select dates and review the total cost on the next page.
                            </p>
                        <?php else: ?>
                            <div class="verification-required">
                                <i class="fas fa-lock fa-3x"></i>
                                <h4>Verification Required</h4>
                                <p>You need to be verified before you can make reservations.</p>
                                
                                <div class="verification-status">
                                    Your Status: <?= getVerificationBadge($user_profile['verification_status']) ?>
                                </div>
                                
                                <?php if ($user_profile['verification_status'] === 'unverified'): ?>
                                    <a href="upload-id.php" class="btn btn-primary btn-block">
                                        <i class="fas fa-id-card"></i> Upload ID for Verification
                                    </a>
                                <?php elseif ($user_profile['verification_status'] === 'pending_verification'): ?>
                                    <p class="note">
                                        <i class="fas fa-clock"></i>
                                        Your ID is under review. Please wait for admin approval.
                                    </p>
                                <?php elseif ($user_profile['verification_status'] === 'rejected'): ?>
                                    <a href="profile.php" class="btn btn-warning btn-block">
                                        <i class="fas fa-exclamation-triangle"></i> View Rejection Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Info -->
                    <div class="quick-info">
                        <h4><i class="fas fa-bolt"></i> Quick Info</h4>
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Instant booking for verified users</li>
                            <li><i class="fas fa-shield-alt"></i> Secure payment confirmation</li>
                            <li><i class="fas fa-headset"></i> 24/7 customer support</li>
                            <li><i class="fas fa-calendar-times"></i> Flexible date selection</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function changeImage(src) {
        document.getElementById('mainImage').src = src;
    }
    </script>
    <script src="../js/main.js"></script>
</body>
</html>