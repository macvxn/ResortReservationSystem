<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

$profile = getUserProfile($_SESSION['user_id']);
$error = '';
$success = '';

// Set AdminLTE page variables
$page_title = 'Update Profile - Aura Luxe Resort';

// Classic POST handling (fallback when JS is disabled)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $full_name = clean($_POST['full_name']);
    $id_number = clean($_POST['id_number']);
    $phone = clean($_POST['phone_number']);
    $address = clean($_POST['address']);
    
    // Validation (same as original)
    if (empty($full_name)) {
        $error = "Full name is required";
    } elseif (strlen($full_name) < 3) {
        $error = "Full name must be at least 3 characters";
    } elseif (empty($id_number)) {
        $error = "ID number is required";
    } elseif (strlen($id_number) < 5) {
        $error = "ID number must be at least 5 characters";
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error = "Phone number must be 10-11 digits";
    } else {
        // Update profile
        $stmt = $pdo->prepare("
            UPDATE user_profiles 
            SET full_name = ?, id_number = ?, phone_number = ?, address = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        
        if ($stmt->execute([$full_name, $id_number, $phone, $address, $_SESSION['user_id']])) {
            logAction($_SESSION['user_id'], 'profile_updated', 'user_profiles', $profile['profile_id']);
            $success = "Profile updated successfully!";
            
            // Refresh profile data
            $profile = getUserProfile($_SESSION['user_id']);
            
            // Redirect to ID upload if not yet uploaded
            if (empty($profile['id_image_path'])) {
                header("Location: upload-id.php?profile_updated=1");
                exit();
            }
        } else {
            $error = "Failed to update profile";
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
        
        .bg-secondary {
            background-color: var(--secondary-aqua) !important;
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
        
        /* Form styling */
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-turquoise);
            box-shadow: 0 0 0 0.2rem rgba(64, 224, 208, 0.25);
        }
        
        .form-control:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
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
        
        /* Error styling */
        .error-message {
            color: var(--accent-watermelon);
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .has-error .form-control {
            border-color: var(--accent-watermelon);
        }
        
        .has-error .error-message {
            display: block;
        }
        
        /* Back link */
        .back-link {
            color: var(--primary-turquoise);
            font-weight: 600;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
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
                                <i class="fas fa-user-edit mr-2"></i>Update Profile
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
                <!-- Verification Status -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Verification Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong>Status:</strong>
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
                                                $status_class = 'badge-unverified';
                                                $status_text = '✗ Rejected';
                                                break;
                                            default:
                                                $status_class = 'badge-unverified';
                                                $status_text = '⚠️ Not Verified';
                                        }
                                        ?>
                                        <span class="verification-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                    <?php if ($profile['verification_status'] == 'verified'): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> You can make reservations
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Classic POST Messages (fallback for non-JS) -->
                <div id="classic-messages">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-check"></i> Success!</h5>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- AJAX Messages Container -->
                <div id="ajax-messages" style="display: none;"></div>

                <!-- Info Alert -->
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-info-circle mr-2"></i>Important</h5>
                    Make sure all information matches your government-issued ID exactly.
                </div>

                <!-- Profile Form -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Personal Information</h3>
                            </div>
                            <div class="card-body">
                                <form id="profileForm" method="POST" action="profile.php" novalidate>
                                    <!-- CSRF Protection (if you have it) -->
                                    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token'] ?? ''; ?>">
                                    
                                    <div class="form-group">
                                        <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="full_name"
                                                name="full_name" 
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>"
                                                required
                                                placeholder="Juan Dela Cruz"
                                                data-original-value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>"
                                            >
                                        </div>
                                        <small class="form-text text-muted">
                                            Enter your full name exactly as it appears on your ID
                                        </small>
                                        <div class="error-message" data-for="full_name"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="id_number">ID Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-id-card"></i>
                                                </span>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="id_number"
                                                name="id_number" 
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($profile['id_number'] ?? ''); ?>"
                                                required
                                                placeholder="e.g., N01-23-456789 or A1234567"
                                                maxlength="50"
                                                data-original-value="<?php echo htmlspecialchars($profile['id_number'] ?? ''); ?>"
                                            >
                                        </div>
                                        <small class="form-text text-muted">
                                            Enter your government ID number (Driver's License, Passport, National ID, etc.)<br>
                                            <strong>Examples:</strong> N01-23-456789 (Driver's License), P123456789 (Passport)
                                        </small>
                                        <div class="error-message" data-for="id_number"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone_number">Phone Number</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-phone"></i>
                                                </span>
                                            </div>
                                            <input 
                                                type="tel" 
                                                id="phone_number"
                                                name="phone_number" 
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>"
                                                placeholder="09123456789"
                                                maxlength="11"
                                                data-original-value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>"
                                            >
                                        </div>
                                        <small class="form-text text-muted">10-11 digits (e.g., 09123456789)</small>
                                        <div class="error-message" data-for="phone_number"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="address">Complete Address</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-home"></i>
                                                </span>
                                            </div>
                                            <textarea 
                                                id="address"
                                                name="address" 
                                                class="form-control" 
                                                rows="4"
                                                placeholder="House No., Street, Barangay, City, Province"
                                                data-original-value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>"
                                            ><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="error-message" data-for="address"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                            </div>
                                            <input 
                                                type="email" 
                                                id="email"
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($profile['email']); ?>"
                                                disabled
                                            >
                                        </div>
                                        <small class="form-text text-muted">Email cannot be changed</small>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <span class="btn-text">
                                                <i class="fas fa-save mr-1"></i>Save Profile
                                            </span>
                                            <span class="submit-loader">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                        
                                        <a href="dashboard.php" class="btn btn-secondary ml-2">
                                            <i class="fas fa-times mr-1"></i>Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Step (if profile complete) -->
                <?php if (!empty($profile['full_name']) && !empty($profile['id_number'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h4 class="mb-3" style="color: var(--accent-coral);">
                                        <i class="fas fa-arrow-right mr-2"></i>Next Step
                                    </h4>
                                    <p class="mb-4">Complete your verification by uploading your government ID</p>
                                    <a href="upload-id.php" class="btn btn-success btn-lg">
                                        <i class="fas fa-upload mr-2"></i>Upload Government ID
                                    </a>
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

<!-- Profile AJAX Script -->
<script>
$(document).ready(function() {
    const form = $('#profileForm');
    const submitBtn = $('#submitBtn');
    const ajaxMessages = $('#ajax-messages');
    const classicMessages = $('#classic-messages');
    
    // Hide classic messages initially (they'll show if JS is disabled)
    classicMessages.hide();
    
    // Clear all error states
    function clearErrors() {
        $('.form-group').removeClass('has-error');
        $('.error-message').text('').hide();
        ajaxMessages.empty().hide();
    }
    
    // Show error for specific field
    function showError(field, message) {
        const fieldElement = $('#' + field);
        const formGroup = fieldElement.closest('.form-group');
        const errorElement = formGroup.find('.error-message[data-for="' + field + '"]');
        
        formGroup.addClass('has-error');
        errorElement.text(message).show();
    }
    
    // Show success/error message
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
        
        clearErrors();
        submitBtn.addClass('ajax-loading');
        
        // Collect form data
        const formData = new FormData(this);
        
        // Add AJAX identifier
        formData.append('ajax', '1');
        
        // Send AJAX request
        $.ajax({
            url: 'ajax_profile_update.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                submitBtn.removeClass('ajax-loading');
                
                if (response.success) {
                    // Show success message
                    showMessage('success', 'Success!', response.message);
                    
                    // Update original values for changed detection
                    form.find('input, textarea').each(function() {
                        const $this = $(this);
                        const originalValue = $this.val();
                        $this.data('original-value', originalValue);
                    });
                    
                    // Handle redirect if needed
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    }
                } else {
                    // Show general error message
                    if (response.message) {
                        showMessage('error', 'Error!', response.message);
                    }
                    
                    // Show field-specific errors
                    if (response.errors) {
                        for (const field in response.errors) {
                            showError(field, response.errors[field]);
                        }
                        
                        // Scroll to first error
                        const firstError = $('.has-error').first();
                        if (firstError.length) {
                            $('html, body').animate({
                                scrollTop: firstError.offset().top - 100
                            }, 500);
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                submitBtn.removeClass('ajax-loading');
                showMessage('error', 'Network Error!', 'Unable to connect to server. Please try again.');
                console.error('AJAX Error:', error);
                
                // Fallback: submit form normally
                if (xhr.status === 0 || xhr.status === 404) {
                    showMessage('error', 'Connection Failed', 'Submitting form normally...');
                    setTimeout(function() {
                        form[0].submit();
                    }, 2000);
                }
            }
        });
    });
    
    // Real-time validation
    $('#full_name, #id_number, #phone_number').on('blur', function() {
        const field = $(this).attr('id');
        const value = $(this).val().trim();
        
        // Clear previous error
        $(this).closest('.form-group').removeClass('has-error');
        $(this).closest('.form-group').find('.error-message').text('').hide();
        
        // Validate based on field
        switch(field) {
            case 'full_name':
                if (!value) {
                    showError(field, "Full name is required");
                } else if (value.length < 3) {
                    showError(field, "Full name must be at least 3 characters");
                }
                break;
                
            case 'id_number':
                if (!value) {
                    showError(field, "ID number is required");
                } else if (value.length < 5) {
                    showError(field, "ID number must be at least 5 characters");
                }
                break;
                
            case 'phone_number':
                if (value && !/^[0-9]{10,11}$/.test(value)) {
                    showError(field, "Phone number must be 10-11 digits");
                }
                break;
        }
    });
    
    // Check if form has been modified
    function checkFormModified() {
        let modified = false;
        
        form.find('input, textarea').each(function() {
            const $this = $(this);
            if ($this.data('original-value') !== $this.val()) {
                modified = true;
                return false; // break loop
            }
        });
        
        return modified;
    }
    
    // Warn before leaving if form modified
    $(window).on('beforeunload', function() {
        if (checkFormModified()) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Reset form modified state on submit
    form.on('submit', function() {
        $(window).off('beforeunload');
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