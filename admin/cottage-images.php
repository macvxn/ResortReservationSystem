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

$success = '';
$error = '';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['images'])) {
    $uploaded_count = 0;
    $error_count = 0;
    
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] == 0) {
            $file = [
                'name' => $_FILES['images']['name'][$key],
                'type' => $_FILES['images']['type'][$key],
                'tmp_name' => $tmp_name,
                'error' => $_FILES['images']['error'][$key],
                'size' => $_FILES['images']['size'][$key]
            ];
            
            $upload_result = uploadFile($file, 'cottages');
            
            if ($upload_result['success']) {
                // Check if this is the first image (set as primary)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM cottage_images WHERE cottage_id = ?");
                $stmt->execute([$cottage_id]);
                $is_first = $stmt->fetchColumn() == 0;
                
                // Insert image
                $stmt = $pdo->prepare("
                    INSERT INTO cottage_images (cottage_id, image_path, is_primary, uploaded_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$cottage_id, $upload_result['filename'], $is_first ? 1 : 0]);
                
                $uploaded_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($uploaded_count > 0) {
        logAction($_SESSION['user_id'], 'cottage_images_uploaded', 'cottage_images', $cottage_id);
        $success = "$uploaded_count image(s) uploaded successfully!";
    }
    
    if ($error_count > 0) {
        $error = "$error_count image(s) failed to upload.";
    }
}

// Handle set primary
if (isset($_GET['set_primary'])) {
    $image_id = (int)$_GET['set_primary'];
    
    // Remove primary from all images of this cottage
    $stmt = $pdo->prepare("UPDATE cottage_images SET is_primary = 0 WHERE cottage_id = ?");
    $stmt->execute([$cottage_id]);
    
    // Set new primary
    $stmt = $pdo->prepare("UPDATE cottage_images SET is_primary = 1 WHERE image_id = ? AND cottage_id = ?");
    $stmt->execute([$image_id, $cottage_id]);
    
    logAction($_SESSION['user_id'], 'cottage_primary_image_set', 'cottage_images', $image_id);
    
    header("Location: cottage-images.php?id=$cottage_id&success=primary");
    exit();
}

// Handle delete image
if (isset($_GET['delete'])) {
    $image_id = (int)$_GET['delete'];
    
    // Get image path
    $stmt = $pdo->prepare("SELECT image_path, is_primary FROM cottage_images WHERE image_id = ? AND cottage_id = ?");
    $stmt->execute([$image_id, $cottage_id]);
    $image = $stmt->fetch();
    
    if ($image) {
        // Delete file
        $file_path = UPLOAD_PATH . 'cottages/' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM cottage_images WHERE image_id = ?");
        $stmt->execute([$image_id]);
        
        // If it was primary, set another image as primary
        if ($image['is_primary']) {
            $stmt = $pdo->prepare("
                UPDATE cottage_images 
                SET is_primary = 1 
                WHERE cottage_id = ? 
                ORDER BY uploaded_at ASC 
                LIMIT 1
            ");
            $stmt->execute([$cottage_id]);
        }
        
        logAction($_SESSION['user_id'], 'cottage_image_deleted', 'cottage_images', $image_id);
        
        header("Location: cottage-images.php?id=$cottage_id&success=deleted");
        exit();
    }
}

// Get all images
$stmt = $pdo->prepare("
    SELECT * FROM cottage_images 
    WHERE cottage_id = ? 
    ORDER BY is_primary DESC, uploaded_at DESC
");
$stmt->execute([$cottage_id]);
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Images - <?php echo htmlspecialchars($cottage['cottage_name']); ?></title>
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
        .cottage-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .cottage-header h3 {
            margin: 0 0 10px 0;
        }
        .upload-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px dashed #667eea;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .image-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .image-card.primary {
            border: 3px solid #28a745;
        }
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .image-info {
            padding: 15px;
        }
        .primary-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .image-actions {
            display: flex;
            gap: 10px;
            padding: 10px 15px;
            background: #f8f9fa;
        }
        .btn-small {
            padding: 8px 12px;
            font-size: 13px;
            flex: 1;
        }
        .drop-zone {
            border: 3px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .drop-zone:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .drop-zone.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-nav">
            <h2>📸 Cottage Image Manager</h2>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="cottages.php">← Back to Cottages</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php if ($_GET['success'] == 'primary'): ?>
                    ✓ Primary image updated successfully!
                <?php elseif ($_GET['success'] == 'deleted'): ?>
                    ✓ Image deleted successfully!
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Cottage Info -->
        <div class="cottage-header">
            <h3>🏠 <?php echo htmlspecialchars($cottage['cottage_name']); ?></h3>
            <p style="color: #666; margin: 5px 0;">
                Capacity: <?php echo $cottage['capacity']; ?> guests | 
                ₱<?php echo number_format($cottage['price_per_night'], 2); ?>/night
            </p>
            <p style="color: #999; font-size: 14px; margin: 5px 0;">
                Total Images: <?php echo count($images); ?>
            </p>
        </div>

        <!-- Upload Section -->
        <div class="upload-section">
            <h3>📤 Upload New Images</h3>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="drop-zone" id="dropZone">
                    <p style="font-size: 48px; margin: 0;">📁</p>
                    <p><strong>Click to select images</strong> or drag and drop</p>
                    <p style="color: #666; font-size: 14px;">JPG, PNG (max 5MB each)</p>
                    <input 
                        type="file" 
                        name="images[]" 
                        id="fileInput"
                        accept="image/jpeg,image/png"
                        multiple
                        style="display: none;"
                    >
                </div>
                
                <div id="previewContainer" style="margin-top: 20px; display: none;">
                    <h4>Selected Images:</h4>
                    <div id="previewGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;"></div>
                </div>

                <button type="submit" class="btn" id="uploadBtn" style="margin-top: 20px; display: none;">
                    Upload Images
                </button>
            </form>
        </div>

        <!-- Image Gallery -->
        <h3>🖼️ Current Images (<?php echo count($images); ?>)</h3>
        
        <?php if (empty($images)): ?>
            <div class="card">
                <p style="text-align: center; color: #666;">
                    No images uploaded yet. Upload some images above!
                </p>
            </div>
        <?php else: ?>
            <div class="image-grid">
                <?php foreach ($images as $image): ?>
                    <div class="image-card <?php echo $image['is_primary'] ? 'primary' : ''; ?>">
                        <?php if ($image['is_primary']): ?>
                            <span class="primary-badge">⭐ Primary</span>
                        <?php endif; ?>
                        
                        <img 
                            src="../uploads/cottages/<?php echo htmlspecialchars($image['image_path']); ?>" 
                            alt="Cottage Image"
                            onclick="viewImage(this.src)"
                            style="cursor: pointer;"
                        >
                        
                        <div class="image-info">
                            <small style="color: #666;">
                                Uploaded: <?php echo formatDateTime($image['uploaded_at']); ?>
                            </small>
                        </div>
                        
                        <div class="image-actions">
                            <?php if (!$image['is_primary']): ?>
                                <a 
                                    href="?id=<?php echo $cottage_id; ?>&set_primary=<?php echo $image['image_id']; ?>" 
                                    class="btn btn-success btn-small"
                                    onclick="return confirm('Set this as primary image?')"
                                >
                                    ⭐ Set Primary
                                </a>
                            <?php endif; ?>
                            
                            <a 
                                href="?id=<?php echo $cottage_id; ?>&delete=<?php echo $image['image_id']; ?>" 
                                class="btn btn-danger btn-small"
                                onclick="return confirm('Delete this image?')"
                            >
                                🗑️ Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="cottages.php" class="btn btn-secondary">← Back to Cottage List</a>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9);" onclick="closeImageModal()">
        <span style="position: absolute; top: 20px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
        <img id="modalImage" style="margin: auto; display: block; max-width: 90%; max-height: 90%; margin-top: 50px;">
    </div>

    <script>
        // Drag and drop
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const previewContainer = document.getElementById('previewContainer');
        const previewGrid = document.getElementById('previewGrid');

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            showPreviews(fileInput.files);
        });

        fileInput.addEventListener('change', (e) => {
            showPreviews(e.target.files);
        });

        function showPreviews(files) {
            if (files.length === 0) return;

            previewGrid.innerHTML = '';
            previewContainer.style.display = 'block';
            uploadBtn.style.display = 'block';

            for (let file of files) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.style.position = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px;">
                        <small style="display: block; margin-top: 5px; color: #666;">${file.name}</small>
                    `;
                    previewGrid.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        }

        function viewImage(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = 'block';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
    </script>
</body>
</html>