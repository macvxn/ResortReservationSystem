<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$cottage_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cottage_id) {
    header("Location: cottages.php");
    exit();
}

// Get cottage details
$stmt = $pdo->prepare("SELECT * FROM cottages WHERE cottage_id = ?");
$stmt->execute([$cottage_id]);
$cottage = $stmt->fetch();

if (!$cottage) {
    $_SESSION['error'] = "Cottage not found";
    header("Location: cottages.php");
    exit();
}

// Get images
$stmt = $pdo->prepare("
    SELECT * FROM cottage_images 
    WHERE cottage_id = ? 
    ORDER BY is_primary DESC, uploaded_at ASC
");
$stmt->execute([$cottage_id]);
$images = $stmt->fetchAll();

// Get reservation stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending_admin_review' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reservations
    WHERE cottage_id = ?
");
$stmt->execute([$cottage_id]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cottage['cottage_name']); ?> - View</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px;
            margin: -20px -20px 20px -20px;
            border-radius: 10px 10px 0 0;
            color: white;
        }
        .admin-nav h2 { color: white; margin: 0 0 10px 0; }
        .cottage-view {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .gallery-image {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
        }
        .gallery-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .gallery-image.primary::after {
            content: '⭐ Primary';
            position: absolute;
            top: 10px;
            left: 10px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .detail-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 16px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-nav">
            <h2>🏠 Cottage Details</h2>
            <div class="nav-links">
                <a href="cottages.php">← Back to List</a>
                <a href="edit-cottage.php?id=<?php echo $cottage_id; ?>">✏️ Edit</a>
                <a href="cottage-images.php?id=<?php echo $cottage_id; ?>">📸 Manage Images</a>
            </div>
        </div>

        <div class="cottage-view">
            <h1><?php echo htmlspecialchars($cottage['cottage_name']); ?></h1>
            
            <div style="margin: 20px 0;">
                <span class="status-badge status-<?php echo $cottage['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $cottage['is_active'] ? '✓ Active' : '✗ Inactive'; ?>
                </span>
            </div>

            <!-- Image Gallery -->
            <?php if (!empty($images)): ?>
                <h3>📷 Images (<?php echo count($images); ?>)</h3>
                <div class="image-gallery">
                    <?php foreach ($images as $image): ?>
                        <div class="gallery-image <?php echo $image['is_primary'] ? 'primary' : ''; ?>">
                            <img 
                                src="../uploads/cottages/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                alt="Cottage Image"
                                onclick="window.open(this.src, '_blank')"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No images uploaded for this cottage.
                    <a href="cottage-images.php?id=<?php echo $cottage_id; ?>">Upload images</a>
                </div>
            <?php endif; ?>

            <!-- Details -->
            <h3>📋 Details</h3>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-label">Capacity</div>
                    <div class="detail-value">👥 <?php echo $cottage['capacity']; ?> guests</div>
                </div>

                <div class="detail-card">
                    <div class="detail-label">Price per Night</div>
                    <div class="detail-value">₱<?php echo number_format($cottage['price_per_night'], 2); ?></div>
                </div>

                <div class="detail-card">
                    <div class="detail-label">Total Reservations</div>
                    <div class="detail-value"><?php echo $stats['total']; ?></div>
                </div>

                <div class="detail-card">
                    <div class="detail-label">Status</div>
                    <div class="detail-value"><?php echo $cottage['is_active'] ? 'Active' : 'Inactive'; ?></div>
                </div>
            </div>

            <!-- Description -->
            <h3>📝 Description</h3>
            <div class="detail-card">
                <?php echo nl2br(htmlspecialchars($cottage['description'])); ?>
            </div>

            <!-- Reservation Stats -->
            <h3>📊 Reservation Statistics</h3>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-label">Approved</div>
                    <div class="detail-value" style="color: #28a745;">✓ <?php echo $stats['approved']; ?></div>
                </div>

                <div class="detail-card">
                    <div class="detail-label">Pending</div>
                    <div class="detail-value" style="color: #ffc107;">⏳ <?php echo $stats['pending']; ?></div>
                </div>

                <div class="detail-card">
                    <div class="detail-label">Rejected</div>
                    <div class="detail-value" style="color: #dc3545;">✗ <?php echo $stats['rejected']; ?></div>
                </div>
            </div>

            <!-- Metadata -->
            <h3>ℹ️ Metadata</h3>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-label">Created</div>
                    <div class="detail-value"><?php echo formatDateTime($cottage['created_at']); ?></div>
                </div>

                <div class="detail-card">
                    <div class="detail-label">Last Updated</div>
                    <div class="detail-value"><?php echo formatDateTime($cottage['updated_at']); ?></div>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 15px;">
            <a href="cottages.php" class="btn btn-secondary">← Back to List</a>
            <a href="edit-cottage.php?id=<?php echo $cottage_id; ?>" class="btn">✏️ Edit Cottage</a>
            <a href="cottage-images.php?id=<?php echo $cottage_id; ?>" class="btn">📸 Manage Images</a>
        </div>
    </div>
</body>
</html>