<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireAdmin();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cottage_name = clean($_POST['cottage_name'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    $price_per_night = floatval($_POST['price_per_night'] ?? 0);
    
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
    
    // Handle image uploads
    $uploaded_images = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
                
                $upload_result = uploadFile($file, 'cottages');
                if ($upload_result['success']) {
                    $uploaded_images[] = [
                        'filename' => $upload_result['filename'],
                        'is_primary' => ($i === 0) // First image is primary
                    ];
                } else {
                    $errors[] = "Failed to upload image: " . $upload_result['message'];
                }
            }
        }
    }
    
    if (empty($uploaded_images)) {
        $errors[] = "At least one image is required.";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert cottage
            $stmt = $pdo->prepare("
                INSERT INTO cottages (cottage_name, description, capacity, price_per_night, is_active)
                VALUES (?, ?, ?, ?, TRUE)
            ");
            $stmt->execute([$cottage_name, $description, $capacity, $price_per_night]);
            
            $cottage_id = $pdo->lastInsertId();
            
            // Insert images
            foreach ($uploaded_images as $image) {
                $stmt = $pdo->prepare("
                    INSERT INTO cottage_images (cottage_id, image_path, is_primary)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$cottage_id, $image['filename'], $image['is_primary'] ? 1 : 0]);
            }
            
            // Log action
            logAction($_SESSION['user_id'], 'ADD_COTTAGE', 'cottages', $cottage_id);
            
            $pdo->commit();
            
            $success = true;
            $_SESSION['success'] = "Cottage added successfully!";
            header("Location: cottages.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to save cottage: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Cottage - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container" style="max-width: 800px;">
        <h2><i class="fas fa-plus-circle"></i> Add New Cottage</h2>
        
        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Cottage added successfully! <a href="cottages.php">View all cottages</a>
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
        
        <!-- Add Cottage Form -->
        <form method="POST" enctype="multipart/form-data" id="addCottageForm">
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
                           placeholder="e.g., Beachfront Villa, Mountain View Cabin"
                           value="<?= htmlspecialchars($_POST['cottage_name'] ?? '') ?>"
                           required
                           maxlength="100">
                    <small>Unique name for the cottage</small>
                </div>
                
                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-file-alt"></i> Description *
                    </label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              rows="5"
                              placeholder="Describe the cottage features, amenities, location..."
                              required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <small>Detailed description helps guests make decisions</small>
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
                               value="<?= htmlspecialchars($_POST['capacity'] ?? 2) ?>"
                               required>
                        <small>Number of guests the cottage can accommodate</small>
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
                                   value="<?= htmlspecialchars($_POST['price_per_night'] ?? 1000) ?>"
                                   required>
                        </div>
                        <small>Price in Philippine Peso (PHP)</small>
                    </div>
                </div>
            </div>
            
            <!-- Image Upload -->
            <div class="form-section">
                <h3><i class="fas fa-images"></i> Cottage Images</h3>
                <p class="form-help">Upload at least one image of the cottage. First image will be used as primary.</p>
                
                <div class="image-upload-area" id="imageUploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt fa-3x"></i>
                    </div>
                    <div class="upload-text">
                        <h4>Drag & drop images here</h4>
                        <p>or click to browse</p>
                        <p class="upload-note">Supported: JPG, PNG (max 5MB each)</p>
                    </div>
                    <input type="file" 
                           id="images" 
                           name="images[]" 
                           class="file-input"
                           accept=".jpg,.jpeg,.png"
                           multiple
                           required>
                </div>
                
                <!-- Image Preview -->
                <div class="image-preview" id="imagePreview"></div>
                
                <div class="image-requirements">
                    <h5><i class="fas fa-info-circle"></i> Image Requirements:</h5>
                    <ul>
                        <li>Minimum 1 image, maximum 10 images</li>
                        <li>First image will be the primary/thumbnail</li>
                        <li>Recommended size: 1200x800 pixels</li>
                        <li>Supported formats: JPG, PNG</li>
                        <li>Max file size: 5MB per image</li>
                    </ul>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Cottage
                </button>
                <a href="cottages.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('images');
        const imagePreview = document.getElementById('imagePreview');
        const imageUploadArea = document.getElementById('imageUploadArea');
        
        // Drag and drop functionality
        imageUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        imageUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        imageUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                imageInput.files = e.dataTransfer.files;
                updateImagePreview(e.dataTransfer.files);
            }
        });
        
        // Click to upload
        imageUploadArea.addEventListener('click', function() {
            imageInput.click();
        });
        
        // File input change
        imageInput.addEventListener('change', function() {
            updateImagePreview(this.files);
        });
        
        // Update image preview
        function updateImagePreview(files) {
            imagePreview.innerHTML = '';
            
            if (!files.length) {
                imagePreview.innerHTML = '<p class="no-images">No images selected</p>';
                return;
            }
            
            // Validate number of files
            if (files.length > 10) {
                alert('Maximum 10 images allowed. Only the first 10 will be uploaded.');
            }
            
            const previewTitle = document.createElement('h5');
            previewTitle.textContent = 'Selected Images (' + Math.min(files.length, 10) + ')';
            previewTitle.className = 'preview-title';
            imagePreview.appendChild(previewTitle);
            
            const previewGrid = document.createElement('div');
            previewGrid.className = 'preview-grid';
            
            for (let i = 0; i < Math.min(files.length, 10); i++) {
                const file = files[i];
                
                // Validate file type
                if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                    alert('Only JPG and PNG images are allowed.');
                    continue;
                }
                
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image "' + file.name + '" is too large. Maximum size is 5MB.');
                    continue;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    if (i === 0) {
                        previewItem.innerHTML += '<div class="primary-badge">Primary</div>';
                    }
                    
                    previewItem.innerHTML += `
                        <img src="${e.target.result}" alt="Preview">
                        <div class="preview-info">
                            <p><strong>${file.name}</strong></p>
                            <p>${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                        </div>
                        <button type="button" class="remove-btn" onclick="removeImage(${i})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    previewGrid.appendChild(previewItem);
                };
                reader.readAsDataURL(file);
            }
            
            imagePreview.appendChild(previewGrid);
        }
        
        // Remove image from preview
        window.removeImage = function(index) {
            const dt = new DataTransfer();
            const files = imageInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            imageInput.files = dt.files;
            updateImagePreview(imageInput.files);
        };
        
        // Form validation
        document.getElementById('addCottageForm').addEventListener('submit', function(e) {
            const cottageName = document.getElementById('cottage_name').value.trim();
            const description = document.getElementById('description').value.trim();
            const capacity = document.getElementById('capacity').value;
            const price = document.getElementById('price_per_night').value;
            const images = document.getElementById('images').files;
            
            if (cottageName.length < 3) {
                e.preventDefault();
                alert('Cottage name must be at least 3 characters.');
                return false;
            }
            
            if (description.length < 10) {
                e.preventDefault();
                alert('Description must be at least 10 characters.');
                return false;
            }
            
            if (capacity < 1 || capacity > 50) {
                e.preventDefault();
                alert('Capacity must be between 1 and 50.');
                return false;
            }
            
            if (price <= 0 || price > 50000) {
                e.preventDefault();
                alert('Price must be between ₱1 and ₱50,000.');
                return false;
            }
            
            if (images.length === 0) {
                e.preventDefault();
                alert('Please upload at least one image.');
                return false;
            }
            
            if (images.length > 10) {
                e.preventDefault();
                alert('Maximum 10 images allowed.');
                return false;
            }
            
            // Confirm submission
            if (!confirm('Save this cottage? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
    
    <style>
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-section h3 {
            color: #495057;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .form-help {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #007bff;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 16px;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .input-group {
            display: flex;
        }
        
        .input-group-text {
            background: #e9ecef;
            border: 1px solid #ced4da;
            padding: 12px 15px;
            border-right: none;
            border-radius: 5px 0 0 5px;
        }
        
        .input-group .form-control {
            border-radius: 0 5px 5px 0;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 13px;
        }
        
        /* Image Upload */
        .image-upload-area {
            border: 3px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            margin: 20px 0;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .image-upload-area:hover {
            background: #e9ecef;
            border-color: #0056b3;
        }
        
        .image-upload-area.dragover {
            background: #e7f3ff;
            border-color: #28a745;
        }
        
        .upload-icon {
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .upload-text h4 {
            margin: 0 0 5px 0;
            color: #495057;
        }
        
        .upload-text p {
            margin: 0;
            color: #6c757d;
        }
        
        .upload-note {
            font-size: 14px;
            margin-top: 10px !important;
            color: #6c757d;
        }
        
        .file-input {
            display: none;
        }
        
        /* Image Preview */
        .image-preview {
            margin-top: 20px;
        }
        
        .preview-title {
            color: #495057;
            margin-bottom: 15px;
        }
        
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .preview-item {
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        
        .preview-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }
        
        .preview-info {
            padding: 10px;
            font-size: 12px;
        }
        
        .preview-info p {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .primary-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .no-images {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        
        /* Image Requirements */
        .image-requirements {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .image-requirements h5 {
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .image-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #495057;
        }
        
        .image-requirements li {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn-lg {
            padding: 15px 30px;
            font-size: 18px;
        }
    </style>
</body>
</html>