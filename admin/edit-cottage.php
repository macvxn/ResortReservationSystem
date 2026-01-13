<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

$cottage_id = $_GET['id'] ?? 0;
if (!$cottage_id) {
    header("Location: cottages.php");
    exit();
}

// Get cottage details
$stmt = $pdo->prepare("SELECT * FROM cottages WHERE cottage_id = ?");
$stmt->execute([$cottage_id]);
$cottage = $stmt->fetch();

if (!$cottage) {
    header("Location: cottages.php?error=not_found");
    exit();
}

// Get cottage images
$stmt = $pdo->prepare("SELECT * FROM cottage_images WHERE cottage_id = ? ORDER BY is_primary DESC");
$stmt->execute([$cottage_id]);
$existing_images = $stmt->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cottage_name = clean($_POST['cottage_name'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    $price_per_night = floatval($_POST['price_per_night'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($cottage_name)) {
        $errors[] = "Cottage name is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    
    if ($capacity < 1) {
        $errors[] = "Capacity must be at least 1.";
    }
    
    if ($capacity > 50) {
        $errors[] = "Capacity cannot exceed 50.";
    }
    
    if ($price_per_night <= 0) {
        $errors[] = "Price must be greater than 0.";
    }
    
    if ($price_per_night > 50000) {
        $errors[] = "Price cannot exceed ₱50,000 per night.";
    }
    
    // Handle new image uploads
    $new_images = [];
    if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['name'])) {
        for ($i = 0; $i < count($_FILES['new_images']['name']); $i++) {
            if ($_FILES['new_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['new_images']['name'][$i],
                    'type' => $_FILES['new_images']['type'][$i],
                    'tmp_name' => $_FILES['new_images']['tmp_name'][$i],
                    'error' => $_FILES['new_images']['error'][$i],
                    'size' => $_FILES['new_images']['size'][$i]
                ];
                
                $upload_result = uploadFile($file, 'cottages');
                if ($upload_result['success']) {
                    $new_images[] = $upload_result['filename'];
                } else {
                    $errors[] = "Failed to upload image: " . $upload_result['message'];
                }
            }
        }
    }
    
    // Handle primary image selection
    $primary_image_id = intval($_POST['primary_image'] ?? 0);
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update cottage
            $stmt = $pdo->prepare("
                UPDATE cottages 
                SET cottage_name = ?, 
                    description = ?, 
                    capacity = ?, 
                    price_per_night = ?, 
                    is_active = ?,
                    updated_at = NOW()
                WHERE cottage_id = ?
            ");
            $stmt->execute([$cottage_name, $description, $capacity, $price_per_night, $is_active, $cottage_id]);
            
            // Add new images
            foreach ($new_images as $image) {
                $is_primary = (count($existing_images) === 0 && $new_images[0] === $image) ? 1 : 0;
                $stmt = $pdo->prepare("
                    INSERT INTO cottage_images (cottage_id, image_path, is_primary)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$cottage_id, $image, $is_primary]);
            }
            
            // Update primary image if changed
            if ($primary_image_id > 0) {
                // Reset all to non-primary
                $stmt = $pdo->prepare("UPDATE cottage_images SET is_primary = 0 WHERE cottage_id = ?");
                $stmt->execute([$cottage_id]);
                
                // Set new primary
                $stmt = $pdo->prepare("UPDATE cottage_images SET is_primary = 1 WHERE image_id = ? AND cottage_id = ?");
                $stmt->execute([$primary_image_id, $cottage_id]);
            }
            
            // Handle image deletions
            if (isset($_POST['delete_images'])) {
                $delete_ids = $_POST['delete_images'];
                foreach ($delete_ids as $image_id) {
                    $stmt = $pdo->prepare("SELECT image_path FROM cottage_images WHERE image_id = ?");
                    $stmt->execute([$image_id]);
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
                    }
                }
            }
            
            // Log action
            logAction($_SESSION['user_id'], 'UPDATE_COTTAGE', 'cottages', $cottage_id);
            
            $pdo->commit();
            
            $success = true;
            $_SESSION['success'] = "Cottage updated successfully!";
            header("Location: edit-cottage.php?id=" . $cottage_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to update cottage: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cottage - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container" style="max-width: 1000px;">
        <h2>
            <i class="fas fa-edit"></i>
            Edit Cottage: <?= htmlspecialchars($cottage['cottage_name']) ?>
        </h2>
        
        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Cottage updated successfully!
        </div>
        <?php endif; ?>
        
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following:</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Edit Cottage Form -->
        <form method="POST" enctype="multipart/form-data" id="editCottageForm">
            <!-- Cottage Basic Info -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="cottage_name">
                        <i class="fas fa-home"></i> Cottage Name *
                    </label>
                    <input type="text" 
                           id="cottage_name" 
                           name="cottage_name" 
                           class="form-control"
                           value="<?= htmlspecialchars($cottage['cottage_name']) ?>"
                           required
                           maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-file-alt"></i> Description *
                    </label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              rows="5"
                              required><?= htmlspecialchars($cottage['description']) ?></textarea>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           class="form-check-input"
                           <?= $cottage['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">
                        <i class="fas fa-toggle-on"></i> Cottage is active and visible to users
                    </label>
                </div>
            </div>
            
            <!-- Capacity & Pricing -->
            <div class="form-section">
                <h3><i class="fas fa-calculator"></i> Capacity & Pricing</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="capacity">
                            <i class="fas fa-users"></i> Maximum Capacity *
                        </label>
                        <input type="number" 
                               id="capacity" 
                               name="capacity" 
                               class="form-control"
                               min="1" 
                               max="50"
                               value="<?= $cottage['capacity'] ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_night">
                            <i class="fas fa-tag"></i> Price per Night *
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" 
                                   id="price_per_night" 
                                   name="price_per_night" 
                                   class="form-control"
                                   min="1" 
                                   max="50000"
                                   step="0.01"
                                   value="<?= $cottage['price_per_night'] ?>"
                                   required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Existing Images -->
            <div class="form-section">
                <h3><i class="fas fa-images"></i> Existing Images</h3>
                
                <?php if (empty($existing_images)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No images uploaded yet. Please add at least one image below.
                </div>
                <?php else: ?>
                <div class="existing-images-grid">
                    <?php foreach ($existing_images as $image): ?>
                    <div class="existing-image">
                        <div class="image-container">
                            <img src="../uploads/cottages/<?= htmlspecialchars($image['image_path']) ?>" 
                                 alt="Cottage Image"
                                 onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found'">
                            <?php if ($image['is_primary']): ?>
                            <div class="primary-badge">Primary</div>
                            <?php endif; ?>
                        </div>
                        <div class="image-actions">
                            <div class="form-check">
                                <input type="radio" 
                                       id="primary_<?= $image['image_id'] ?>" 
                                       name="primary_image" 
                                       value="<?= $image['image_id'] ?>"
                                       class="form-check-input"
                                       <?= $image['is_primary'] ? 'checked' : '' ?>>
                                <label for="primary_<?= $image['image_id'] ?>" class="form-check-label">
                                    Set as Primary
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" 
                                       id="delete_<?= $image['image_id'] ?>" 
                                       name="delete_images[]" 
                                       value="<?= $image['image_id'] ?>"
                                       class="form-check-input delete-checkbox">
                                <label for="delete_<?= $image['image_id'] ?>" class="form-check-label text-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Add New Images -->
            <div class="form-section">
                <h3><i class="fas fa-plus-circle"></i> Add New Images</h3>
                <p class="form-help">Upload additional images (optional)</p>
                
                <div class="image-upload-area" id="imageUploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt fa-3x"></i>
                    </div>
                    <div class="upload-text">
                        <h4>Drag & drop new images here</h4>
                        <p>or click to browse</p>
                        <p class="upload-note">Maximum 10 additional images</p>
                    </div>
                    <input type="file" 
                           id="new_images" 
                           name="new_images[]" 
                           class="file-input"
                           accept=".jpg,.jpeg,.png"
                           multiple>
                </div>
                
                <!-- New Images Preview -->
                <div class="image-preview" id="imagePreview"></div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Update Cottage
                </button>
                <a href="cottages.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="?id=<?= $cottage_id ?>" class="btn btn-warning">
                    <i class="fas fa-redo"></i> Reset Form
                </a>
            </div>
        </form>
        
        <!-- Danger Zone -->
        <div class="form-section danger-zone">
            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
            
            <div class="danger-actions">
                <a href="?delete=<?= $cottage_id ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('WARNING: This will disable the cottage. Existing reservations will remain. Are you sure?')">
                    <i class="fas fa-ban"></i> Disable Cottage
                </a>
                
                <button type="button" 
                        class="btn btn-danger"
                        onclick="if(confirm('WARNING: This will permanently delete the cottage and all its images. This action cannot be undone!')) { 
                            window.location.href = 'delete-cottage.php?id=<?= $cottage_id ?>'; 
                        }">
                    <i class="fas fa-trash"></i> Permanently Delete
                </button>
            </div>
            
            <div class="danger-note">
                <p><strong>Note:</strong> Disabling only hides the cottage from users. Permanent deletion removes all data.</p>
            </div>
        </div>
    </div>
    
    <script>
    // Similar JavaScript as add-cottage.php for image upload preview
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('new_images');
        const imagePreview = document.getElementById('imagePreview');
        const imageUploadArea = document.getElementById('imageUploadArea');
        
        // ... (same drag & drop and preview logic as add-cottage.php)
        
        // Form validation
        document.getElementById('editCottageForm').addEventListener('submit', function(e) {
            const deleteCheckboxes = document.querySelectorAll('.delete-checkbox:checked');
            const remainingImages = <?= count($existing_images) ?> - deleteCheckboxes.length;
            const newImages = imageInput.files.length;
            
            if (remainingImages + newImages === 0) {
                e.preventDefault();
                alert('Cottage must have at least one image.');
                return false;
            }
            
            if (remainingImages + newImages > 10) {
                e.preventDefault();
                alert('Maximum 10 images allowed. Please delete some images first.');
                return false;
            }
            
            if (!confirm('Save changes to this cottage?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
    
    <style>
        /* Existing Images Grid */
        .existing-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .existing-image {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        
        .image-container {
            position: relative;
            height: 150px;
            overflow: hidden;
        }
        
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-actions {
            padding: 15px;
            background: #f8f9fa;
        }
        
        .form-check {
            margin-bottom: 10px;
        }
        
        .form-check:last-child {
            margin-bottom: 0;
        }
        
        .text-danger {
            color: #dc3545 !important;
        }
        
        /* Danger Zone */
        .danger-zone {
            border: 2px solid #dc3545;
            background: #fff5f5;
        }
        
        .danger-zone h3 {
            color: #dc3545;
            border-bottom-color: #dc3545;
        }
        
        .danger-actions {
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }
        
        .danger-note {
            padding: 10px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        
        .danger-note p {
            margin: 0;
            color: #856404;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .existing-images-grid {
                grid-template-columns: 1fr;
            }
            
            .danger-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>