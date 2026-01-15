<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
require_once '../config/database.php'; // ADD THIS LINE

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to browse cottages',
        'count' => 0,
        'cottages' => []
    ]);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'count' => 0,
    'cottages' => []
];

try {
    // Get search parameter ONLY (since we simplified the design)
    $search = $_GET['search'] ?? '';
    
    // Sanitize search input
    $search = trim($search);
    
    // Build query
    $sql = "SELECT * FROM cottages WHERE is_active = TRUE";
    $params = [];

    if (!empty($search)) {
        // Check if search is numeric (capacity search)
        if (is_numeric($search) && intval($search) > 0) {
            // Search by minimum capacity
            $sql .= " AND capacity >= ?";
            $params[] = intval($search);
        } else {
            // Search by name or description (case-insensitive)
            $sql .= " AND (LOWER(cottage_name) LIKE LOWER(?) OR LOWER(description) LIKE LOWER(?))";
            $searchTerm = "%" . $search . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
    }

    $sql .= " ORDER BY cottage_name ASC";

    // Debug: Uncomment to see query
    // error_log("Cottages Query: " . $sql);
    // error_log("Params: " . print_r($params, true));

    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement");
    }
    
    $stmt->execute($params);
    $cottages = $stmt->fetchAll();

    // Get primary images for each cottage
    $cottage_data = [];
    foreach ($cottages as $cottage) {
        $stmt = $pdo->prepare("SELECT image_path FROM cottage_images WHERE cottage_id = ? AND is_primary = TRUE LIMIT 1");
        $stmt->execute([$cottage['cottage_id']]);
        $image = $stmt->fetch();
        
        $image_path = $image ? $image['image_path'] : 'default_cottage.jpg';
        
        // Validate image file exists
        $full_image_path = '../uploads/cottages/' . $image_path;
        if (!file_exists($full_image_path) || !is_file($full_image_path)) {
            $image_path = 'default_cottage.jpg';
        }
        
        $cottage_data[] = [
            'id' => (int)$cottage['cottage_id'],
            'name' => htmlspecialchars($cottage['cottage_name']),
            'description' => htmlspecialchars(substr($cottage['description'], 0, 120)) . '...',
            'price' => (float)$cottage['price_per_night'],
            'price_formatted' => '₱' . number_format($cottage['price_per_night'], 0),
            'capacity' => (int)$cottage['capacity'],
            'bedrooms' => (int)($cottage['bedrooms'] ?? 1),
            'image' => $image_path
        ];
    }

    $response['success'] = true;
    $response['count'] = count($cottage_data);
    $response['cottages'] = $cottage_data;
    
    // Debug: Uncomment to see response
    // error_log("Cottages found: " . count($cottage_data));

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("AJAX Cottages PDO Error: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("AJAX Cottages Error: " . $e->getMessage());
}

echo json_encode($response);
exit();