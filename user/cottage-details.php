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

// Check upcoming reservations for this cottage
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

// Set AdminLTE page variables
$page_title = htmlspecialchars($cottage['cottage_name']) . ' - Aura Luxe Resort';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.min.css">
    
    <!-- Image Gallery CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.0/css/lightgallery-bundle.min.css">
    
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
        
        /* Back button */
        .back-button {
            background: white;
            color: var(--primary-turquoise);
            border: 2px solid var(--primary-turquoise);
            border-radius: 30px;
            padding: 10px 20px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: var(--primary-turquoise);
            color: white;
            text-decoration: none;
            transform: translateX(-5px);
        }
        
        /* Cottage header */
        .cottage-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .cottage-header h1 {
            margin: 0;
            font-weight: 700;
        }
        
        .price-display {
            text-align: right;
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 25px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .price-amount {
            font-size: 2rem;
            font-weight: 700;
            color: white;
        }
        
        .price-label {
            display: block;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Image gallery */
        .main-image-container {
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .main-image:hover {
            transform: scale(1.02);
        }
        
        .thumbnail-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            margin-bottom: 30px;
        }
        
        .thumbnail {
            width: 100px;
            height: 75px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .thumbnail:hover, .thumbnail.active {
            border-color: var(--primary-turquoise);
            transform: translateY(-2px);
        }
        
        /* Card styling */
        .card {
            border: 1px solid rgba(64, 224, 208, 0.2);
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            border-radius: 15px 15px 0 0 !important;
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
        
        /* Features grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(64, 224, 208, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(64, 224, 208, 0.15);
            border-color: var(--primary-turquoise);
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .feature-text strong {
            color: var(--primary-turquoise);
            display: block;
            margin-bottom: 5px;
        }
        
        /* Reservation box */
        .reservation-box {
            position: sticky;
            top: 20px;
        }
        
        .reservation-card {
            background: white;
            border-radius: 15px;
            border: 1px solid rgba(64, 224, 208, 0.2);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .price-breakdown {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .price-row.total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
            font-size: 1.1rem;
            color: var(--primary-turquoise);
            font-weight: 700;
        }
        
        /* Verification required */
        .verification-required {
            text-align: center;
            padding: 30px 20px;
        }
        
        .verification-required i {
            color: var(--accent-yellow);
            margin-bottom: 20px;
        }
        
        .verification-status {
            background: rgba(255, 211, 0, 0.1);
            border: 1px solid var(--accent-yellow);
            border-radius: 10px;
            padding: 10px 15px;
            margin: 20px 0;
            font-weight: 600;
        }
        
        /* Quick info */
        .quick-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .quick-info h4 {
            color: var(--primary-turquoise);
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .quick-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .quick-info li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quick-info i {
            color: var(--accent-coral);
            width: 20px;
        }
        
        /* Button customization */
        .btn {
            border-radius: 30px;
            padding: 12px 25px;
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
        
        .btn-warning {
            background: linear-gradient(135deg, var(--accent-yellow), #ffdd00);
            border: none;
            color: #333;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 211, 0, 0.4);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        /* Note styling */
        .note {
            background: rgba(64, 224, 208, 0.1);
            border-left: 4px solid var(--primary-turquoise);
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #555;
        }
        
        .note i {
            color: var(--primary-turquoise);
            margin-right: 10px;
        }
        
        /* Description styling */
        .description-content {
            line-height: 1.8;
            color: #555;
            font-size: 1.05rem;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .cottage-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .price-display {
                text-align: center;
            }
            
            .main-image {
                height: 250px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .reservation-box {
                position: static;
            }
        }
        
        /* Image modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-image {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .modal-prev {
            left: 20px;
        }
        
        .modal-next {
            right: 20px;
        }
        
        /* Image counter */
        .image-counter {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Image Modal -->
    <div class="image-modal" id="imageModal">
        <button class="modal-close" onclick="closeModal()">×</button>
        <button class="modal-nav modal-prev" onclick="prevImage()">❮</button>
        <img class="modal-image" id="modalImage" src="" alt="">
        <button class="modal-nav modal-next" onclick="nextImage()">❯</button>
        <div class="image-counter" id="imageCounter"></div>
    </div>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12">
                        <a href="cottages.php" class="back-button">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Cottages
                        </a>
                        
                        <div class="cottage-header">
                            <div>
                                <h1 class="m-0">
                                    <i class="fas fa-home mr-2"></i>
                                    <?php echo htmlspecialchars($cottage['cottage_name']); ?>
                                </h1>
                                <p class="mb-0 mt-2" style="opacity: 0.9;">
                                    Luxury cottage at Aura Luxe Resort
                                </p>
                            </div>
                            <div class="price-display">
                                <div class="price-amount">
                                    ₱<?php echo number_format($cottage['price_per_night'], 0); ?>
                                </div>
                                <span class="price-label">per night</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Image Gallery -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="main-image-container">
                            <img 
                                id="mainImage" 
                                class="main-image" 
                                src="../uploads/cottages/<?php echo htmlspecialchars($primary_image); ?>" 
                                alt="<?php echo htmlspecialchars($cottage['cottage_name']); ?>"
                                onclick="openModal(this.src, '<?php echo htmlspecialchars($cottage['cottage_name']); ?>')"
                                onerror="this.src='https://via.placeholder.com/1200x400/40E0D0/ffffff?text=Aura+Luxe+Resort'">
                        </div>
                        
                        <?php if (count($images) > 1): ?>
                            <div class="thumbnail-container">
                                <?php foreach ($images as $index => $image): ?>
                                    <img 
                                        src="../uploads/cottages/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                        alt="Thumbnail"
                                        class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                        onclick="changeImage(this.src, '<?php echo htmlspecialchars($image['image_path']); ?>', <?php echo $index; ?>)"
                                        onerror="this.src='https://via.placeholder.com/100x75/40E0D0/ffffff?text=Thumbnail'">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="row">
                    <!-- Left Column: Details -->
                    <div class="col-lg-8">
                        <!-- Description Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle mr-2"></i>Description
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="description-content">
                                    <?php echo nl2br(htmlspecialchars($cottage['description'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Features Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-home mr-2"></i>Cottage Features
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="features-grid">
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="feature-text">
                                            <strong>Maximum Guests</strong>
                                            <p><?php echo $cottage['capacity']; ?> people</p>
                                        </div>
                                    </div>
                                    
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                        <div class="feature-text">
                                            <strong>Price per Night</strong>
                                            <p>₱<?php echo number_format($cottage['price_per_night'], 2); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($cottage['bedrooms'])): ?>
                                        <div class="feature-item">
                                            <div class="feature-icon">
                                                <i class="fas fa-bed"></i>
                                            </div>
                                            <div class="feature-text">
                                                <strong>Bedrooms</strong>
                                                <p><?php echo $cottage['bedrooms']; ?> bedroom(s)</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <i class="fas fa-ruler-combined"></i>
                                        </div>
                                        <div class="feature-text">
                                            <strong>Size</strong>
                                            <p><?php echo $cottage['size_sqft'] ?? 'N/A'; ?> sq ft</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities (if any) -->
                        <?php if (!empty($cottage['amenities'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-star mr-2"></i>Amenities
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php 
                                        $amenities = explode(',', $cottage['amenities']);
                                        foreach ($amenities as $amenity): 
                                            if (trim($amenity)):
                                        ?>
                                            <div class="col-md-6 mb-2">
                                                <i class="fas fa-check-circle text-success mr-2"></i>
                                                <?php echo htmlspecialchars(trim($amenity)); ?>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column: Reservation Box -->
                    <div class="col-lg-4">
                        <div class="reservation-box">
                            <!-- Reservation Card -->
                            <div class="card reservation-card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-calendar-alt mr-2"></i>Book This Cottage
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <?php if ($is_verified): ?>
                                        <div class="price-breakdown">
                                            <div class="price-row">
                                                <span>Price per night:</span>
                                                <span>₱<?php echo number_format($cottage['price_per_night'], 2); ?></span>
                                            </div>
                                            <hr>
                                            <div class="price-row total">
                                                <span><strong>Total per night:</strong></span>
                                                <span><strong>₱<?php echo number_format($cottage['price_per_night'], 2); ?></strong></span>
                                            </div>
                                        </div>
                                        
                                        <a href="reserve.php?cottage_id=<?php echo $cottage['cottage_id']; ?>" 
                                           class="btn btn-success btn-block">
                                            <i class="fas fa-calendar-check mr-1"></i> Check Availability & Reserve
                                        </a>
                                        
                                        <div class="note">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            You'll be able to select dates and review the total cost on the next page.
                                        </div>
                                    <?php else: ?>
                                        <div class="verification-required">
                                            <i class="fas fa-lock fa-3x mb-3"></i>
                                            <h4>Verification Required</h4>
                                            <p>You need to be verified before you can make reservations.</p>
                                            
                                            <div class="verification-status">
                                                <?php 
                                                $status_text = '';
                                                switch($user_profile['verification_status']) {
                                                    case 'unverified':
                                                        $status_text = '⚠️ Not Verified';
                                                        break;
                                                    case 'pending_verification':
                                                        $status_text = '🕐 Pending Verification';
                                                        break;
                                                    case 'verified':
                                                        $status_text = '✅ Verified';
                                                        break;
                                                    case 'rejected':
                                                        $status_text = '✗ Rejected';
                                                        break;
                                                }
                                                echo htmlspecialchars($status_text);
                                                ?>
                                            </div>
                                            
                                            <?php if ($user_profile['verification_status'] === 'unverified'): ?>
                                                <a href="upload-id.php" class="btn btn-primary btn-block">
                                                    <i class="fas fa-id-card mr-1"></i> Upload ID for Verification
                                                </a>
                                            <?php elseif ($user_profile['verification_status'] === 'pending_verification'): ?>
                                                <div class="note">
                                                    <i class="fas fa-clock mr-2"></i>
                                                    Your ID is under review. Please wait for admin approval.
                                                </div>
                                            <?php elseif ($user_profile['verification_status'] === 'rejected'): ?>
                                                <a href="profile.php" class="btn btn-warning btn-block">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> View Rejection Details
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Quick Info Card -->
                            <div class="quick-info">
                                <h4><i class="fas fa-bolt mr-2"></i>Quick Info</h4>
                                <ul>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <span>Instant booking for verified users</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-shield-alt"></i>
                                        <span>Secure payment confirmation</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-headset"></i>
                                        <span>24/7 customer support</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Flexible date selection</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-wifi"></i>
                                        <span>Free high-speed WiFi</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-car"></i>
                                        <span>Free parking available</span>
                                    </li>
                                </ul>
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

<!-- Cottage Details Script -->
<script>
// Image gallery variables
let currentImageIndex = 0;
const cottageImages = [
    <?php foreach ($images as $index => $image): ?>
    {
        src: '../uploads/cottages/<?php echo htmlspecialchars($image['image_path']); ?>',
        alt: '<?php echo htmlspecialchars($cottage['cottage_name']); ?> - Image <?php echo $index + 1; ?>'
    },
    <?php endforeach; ?>
];

// Change main image when thumbnail is clicked
function changeImage(src, imagePath, index) {
    // Update main image
    document.getElementById('mainImage').src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
    
    // Update current index
    currentImageIndex = index;
}

// Open image modal
function openModal(src, alt) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const imageCounter = document.getElementById('imageCounter');
    
    modalImage.src = src;
    modalImage.alt = alt;
    imageCounter.textContent = `${currentImageIndex + 1} / ${cottageImages.length}`;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Close modal
function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Navigate to previous image in modal
function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + cottageImages.length) % cottageImages.length;
    updateModalImage();
}

// Navigate to next image in modal
function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % cottageImages.length;
    updateModalImage();
}

// Update modal image and thumbnails
function updateModalImage() {
    const modalImage = document.getElementById('modalImage');
    const imageCounter = document.getElementById('imageCounter');
    const mainImage = document.getElementById('mainImage');
    
    modalImage.src = cottageImages[currentImageIndex].src;
    modalImage.alt = cottageImages[currentImageIndex].alt;
    imageCounter.textContent = `${currentImageIndex + 1} / ${cottageImages.length}`;
    
    // Update main image and thumbnails
    mainImage.src = cottageImages[currentImageIndex].src;
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === currentImageIndex);
    });
}

// Close modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    } else if (e.key === 'ArrowLeft') {
        prevImage();
    } else if (e.key === 'ArrowRight') {
        nextImage();
    }
});

// Close modal when clicking outside image
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Add swipe support for mobile
let touchStartX = 0;
let touchEndX = 0;

document.getElementById('modalImage').addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
});

document.getElementById('modalImage').addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    
    if (touchStartX - touchEndX > swipeThreshold) {
        // Swipe left - next image
        nextImage();
    } else if (touchEndX - touchStartX > swipeThreshold) {
        // Swipe right - previous image
        prevImage();
    }
}

// Initialize with first image active
document.addEventListener('DOMContentLoaded', function() {
    // Update any initial active states
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        if (thumb.classList.contains('active')) {
            currentImageIndex = i;
        }
    });
});
</script>
</body>
</html>