<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireLogin();

$user_profile = getUserProfile($_SESSION['user_id']);
$is_verified = $user_profile['verification_status'] === 'verified';

// Get search/filter parameters
$search = $_GET['search'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$min_capacity = $_GET['min_capacity'] ?? '';

// Build query
$sql = "SELECT * FROM cottages WHERE is_active = TRUE";
$params = [];

if (!empty($search)) {
    $sql .= " AND (cottage_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($max_price) && is_numeric($max_price)) {
    $sql .= " AND price_per_night <= ?";
    $params[] = $max_price;
}

if (!empty($min_capacity) && is_numeric($min_capacity)) {
    $sql .= " AND capacity >= ?";
    $params[] = $min_capacity;
}

$sql .= " ORDER BY cottage_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cottages = $stmt->fetchAll();

// Get primary images for each cottage
$cottage_images = [];
foreach ($cottages as $cottage) {
    $stmt = $pdo->prepare("SELECT image_path FROM cottage_images WHERE cottage_id = ? AND is_primary = TRUE LIMIT 1");
    $stmt->execute([$cottage['cottage_id']]);
    $image = $stmt->fetch();
    $cottage_images[$cottage['cottage_id']] = $image ? $image['image_path'] : 'default_cottage.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cottage Browsing - Resort Reservation System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-home"></i> Browse Our Cottages</h2>
        
        <!-- Verification Status Alert -->
        <?php if (!$is_verified): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Verification Required:</strong> You can browse cottages, but you need to be verified to make reservations. 
            <?php if ($user_profile['verification_status'] === 'unverified'): ?>
                <a href="upload-id.php">Upload your ID now</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Search and Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-search"></i> Search:</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search by name or description...">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Max Price:</label>
                        <input type="number" name="max_price" value="<?= htmlspecialchars($max_price) ?>" 
                               placeholder="Max price per night">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Min Guests:</label>
                        <input type="number" name="min_capacity" value="<?= htmlspecialchars($min_capacity) ?>" 
                               placeholder="Minimum capacity">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="cottages.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Results Count -->
        <div class="results-count">
            Found <?= count($cottages) ?> cottage(s)
        </div>
        
        <!-- Cottage Grid -->
        <?php if (empty($cottages)): ?>
        <div class="no-results">
            <i class="fas fa-house-circle-xmark fa-3x"></i>
            <h3>No cottages found</h3>
            <p>Try adjusting your search filters</p>
        </div>
        <?php else: ?>
        <div class="cottage-grid">
            <?php foreach ($cottages as $cottage): ?>
            <div class="cottage-card">
                <!-- Cottage Image -->
                <div class="cottage-image">
                    <img src="../uploads/cottages/<?= htmlspecialchars($cottage_images[$cottage['cottage_id']]) ?>" 
                         alt="<?= htmlspecialchars($cottage['cottage_name']) ?>"
                         onerror="this.src='https://via.placeholder.com/300x200?text=Cottage+Image'">
                    <div class="price-tag">
                        ₱<?= number_format($cottage['price_per_night'], 2) ?>/night
                    </div>
                </div>
                
                <!-- Cottage Info -->
                <div class="cottage-info">
                    <h3><?= htmlspecialchars($cottage['cottage_name']) ?></h3>
                    <p class="description"><?= htmlspecialchars(substr($cottage['description'], 0, 100)) ?>...</p>
                    
                    <div class="cottage-meta">
                        <span><i class="fas fa-users"></i> Up to <?= $cottage['capacity'] ?> guests</span>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="cottage-details.php?id=<?= $cottage['cottage_id'] ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        
                        <?php if ($is_verified): ?>
                        <a href="reserve.php?cottage_id=<?= $cottage['cottage_id'] ?>" class="btn btn-success">
                            <i class="fas fa-calendar-check"></i> Reserve Now
                        </a>
                        <?php else: ?>
                        <button class="btn btn-secondary" disabled title="Verify your account to reserve">
                            <i class="fas fa-lock"></i> Reserve
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="../js/main.js"></script>
</body>
</html>