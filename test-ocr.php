<?php
// test-upload.php
require_once 'config/session.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $result = uploadFile($_FILES['test_file'], 'payments');
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" required>
    <button type="submit">Test Upload</button>
</form>