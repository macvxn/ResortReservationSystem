<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

$profile = getUserProfile($_SESSION['user_id']);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = clean($_POST['full_name']);
    $id_number = clean($_POST['id_number']); // NEW
    $phone = clean($_POST['phone_number']);
    $address = clean($_POST['address']);
    
    // Validation
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
    <title>Update Profile - Resort Reservation</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>📝 Update Profile</h2>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>

        <!-- Current Verification Status -->
        <div class="card" style="background: #f8f9fa; margin: 20px 0;">
            <strong>Verification Status:</strong>
            <?php echo getVerificationBadge($profile['verification_status']); ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>⚠️ Important:</strong> Make sure all information matches your government-issued ID exactly.
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Full Name <span style="color: red;">*</span></label>
                <input 
                    type="text" 
                    name="full_name" 
                    value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>"
                    required
                    placeholder="Juan Dela Cruz"
                >
                <small>Enter your full name exactly as it appears on your ID</small>
            </div>

            <div class="form-group">
                <label>ID Number <span style="color: red;">*</span></label>
                <input 
                    type="text" 
                    name="id_number" 
                    value="<?php echo htmlspecialchars($profile['id_number'] ?? ''); ?>"
                    required
                    placeholder="e.g., N01-23-456789 or A1234567"
                    maxlength="50"
                >
                <small>
                    Enter your government ID number (Driver's License, Passport, National ID, etc.)<br>
                    <strong>Examples:</strong> N01-23-456789 (Driver's License), P123456789 (Passport)
                </small>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input 
                    type="tel" 
                    name="phone_number" 
                    value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>"
                    placeholder="09123456789"
                    maxlength="11"
                >
                <small>10-11 digits (e.g., 09123456789)</small>
            </div>

            <div class="form-group">
                <label>Complete Address</label>
                <textarea 
                    name="address" 
                    rows="4"
                    placeholder="House No., Street, Barangay, City, Province"
                ><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input 
                    type="email" 
                    value="<?php echo htmlspecialchars($profile['email']); ?>"
                    disabled
                    style="background: #f5f5f5; cursor: not-allowed;"
                >
                <small>Email cannot be changed</small>
            </div>

            <button type="submit">Save Profile</button>
        </form>

        <?php if (!empty($profile['full_name']) && !empty($profile['id_number'])): ?>
            <div class="mt-20">
                <a href="upload-id.php" class="btn btn-success">
                    Next: Upload Government ID →
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>