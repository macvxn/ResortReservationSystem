<?php
require_once '../includes/functions.php';
require_once '../config/session.php';
requireLogin();

$user_profile = getUserProfile($_SESSION['user_id']);
$is_verified = $user_profile['verification_status'] === 'verified';

// Set AdminLTE page variables
$page_title = 'Browse Cottages - Aura Luxe Resort';

// Get search parameter for initial load
$search = $_GET['search'] ?? '';

// Build query for initial load
$sql = "SELECT * FROM cottages WHERE is_active = TRUE";
$params = [];

if (!empty($search)) {
    // Check if search is numeric (could be capacity)
    if (is_numeric($search)) {
        // Search by capacity
        $sql .= " AND capacity >= ?";
        $params[] = intval($search);
    } else {
        // Search by name or description
        $sql .= " AND (cottage_name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
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
        
        /* Card styling */
        .card {
            border: 1px solid rgba(64, 224, 208, 0.2);
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(64, 224, 208, 0.15);
            border-color: var(--primary-turquoise);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border-bottom: none;
            padding: 15px 20px;
        }
        
        /* Cottage card specific */
        .cottage-card-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        
        .cottage-card-body {
            padding: 20px;
            background-color: var(--background-cream);
        }
        
        .cottage-title {
            color: var(--primary-turquoise);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cottage-price {
            background: linear-gradient(135deg, var(--accent-coral), #ff9a80);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .cottage-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .cottage-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            color: #777;
            font-size: 0.9rem;
        }
        
        .cottage-meta i {
            color: var(--primary-turquoise);
            margin-right: 5px;
        }
        
        /* Search card */
        .search-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid rgba(64, 224, 208, 0.2);
            text-align: center;
        }
        
        .search-card h3 {
            color: var(--primary-turquoise);
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 1.4rem;
        }
        
        .search-help-text {
            color: #666;
            font-size: 0.9rem;
            margin-top: 10px;
            font-style: italic;
        }
        
        /* Results header */
        .results-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Alert customization */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 10px;
        }
        
        .alert-warning {
            background-color: rgba(255, 211, 0, 0.1);
            border-left-color: var(--accent-yellow);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left-color: #28a745;
        }
        
        /* Button customization */
        .btn {
            border-radius: 30px;
            padding: 8px 20px;
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
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Search input styling */
        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input-group {
            position: relative;
        }
        
        .search-input {
            border-radius: 30px;
            padding: 15px 50px 15px 20px;
            border: 2px solid rgba(64, 224, 208, 0.3);
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .search-input:focus {
            border-color: var(--primary-turquoise);
            box-shadow: 0 0 0 0.2rem rgba(64, 224, 208, 0.25);
            outline: none;
        }
        
        .search-btn {
            position: absolute;
            right: 5px;
            top: 5px;
            height: calc(100% - 10px);
            border-radius: 25px;
            padding: 0 25px;
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(64, 224, 208, 0.3);
        }
        
        .clear-btn {
            position: absolute;
            right: 150px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            display: none;
        }
        
        .clear-btn:hover {
            color: var(--accent-watermelon);
        }
        
        /* Loading animation */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 30px;
        }
        
        .loading-spinner i {
            font-size: 2rem;
            color: var(--primary-turquoise);
        }
        
        /* No results */
        .no-results {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        
        .no-results i {
            font-size: 4rem;
            color: var(--primary-turquoise);
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .cottage-card-img {
                height: 180px;
            }
            
            .results-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .cottage-meta {
                flex-direction: column;
                gap: 8px;
            }
            
            .search-btn {
                position: relative;
                right: auto;
                top: auto;
                height: auto;
                width: 100%;
                margin-top: 10px;
                padding: 12px;
            }
            
            .clear-btn {
                right: 10px;
            }
        }
        
        /* Grid layout for cottages */
        .cottages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        @media (max-width: 576px) {
            .cottages-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* AJAX loading overlay */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
        }
        
        .loading-text {
            margin-top: 15px;
            color: var(--primary-turquoise);
            font-weight: 600;
        }
        
        /* Page header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-turquoise), var(--secondary-aqua));
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(64, 224, 208, 0.2);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        
        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        /* Search examples */
        .search-examples {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .search-example {
            background: rgba(64, 224, 208, 0.1);
            color: var(--primary-turquoise);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(64, 224, 208, 0.2);
        }
        
        .search-example:hover {
            background: rgba(64, 224, 208, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem; color: var(--primary-turquoise) !important;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <div class="loading-text">Searching cottages...</div>
    </div>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12">
                        <div class="page-header">
                            <h1 class="m-0">
                                <i class="fas fa-home mr-2"></i>Browse Our Cottages
                            </h1>
                            <p class="mb-0 mt-2">
                                Discover your perfect getaway at Aura Luxe Resort
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Verification Status Alert -->
                <?php if (!$is_verified): ?>
                    <div class="alert alert-warning alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-exclamation-triangle mr-2"></i>Verification Required</h5>
                        <p>You can browse cottages, but you need to be verified to make reservations.</p>
                        <?php if ($user_profile['verification_status'] === 'unverified'): ?>
                            <a href="upload-id.php" class="btn btn-sm btn-warning mt-2">
                                <i class="fas fa-upload mr-1"></i>Upload Your ID Now
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Search Card -->
                <div class="search-card">
                    <h3><i class="fas fa-search mr-2"></i>Find Your Perfect Cottage</h3>
                    <div class="search-container">
                        <form id="searchForm" method="GET">
                            <div class="search-input-group">
                                <input 
                                    type="text" 
                                    id="searchInput"
                                    name="search" 
                                    class="form-control search-input" 
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Search by cottage name, description, or number of guests..."
                                    autocomplete="off"
                                >
                                <button type="button" id="clearBtn" class="clear-btn" title="Clear search">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button type="submit" class="btn btn-primary search-btn">
                                    <i class="fas fa-search mr-1"></i>Search
                                </button>
                            </div>
                            <div class="search-help-text">
                                Examples: "Beachfront", "Family", "2 guests", "Luxury"
                            </div>
                            <div class="search-examples">
                                <span class="search-example" data-search="Family">Family Cottage</span>
                                <span class="search-example" data-search="4">4 Guests</span>
                                <span class="search-example" data-search="Luxury">Luxury</span>
                                <span class="search-example" data-search="Beach">Beach View</span>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Header -->
                <div class="results-header">
                    <div>
                        <h4 class="mb-0">
                            <i class="fas fa-home mr-2"></i>Available Cottages
                            <?php if ($search): ?>
                                <span class="ml-2" style="font-size: 0.9rem; opacity: 0.9;">
                                    for "<?php echo htmlspecialchars($search); ?>"
                                </span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div id="resultsCount">
                        <span class="badge badge-light" style="font-size: 1rem; padding: 8px 15px;">
                            <?php echo count($cottages); ?> cottage(s) found
                        </span>
                    </div>
                </div>

                <!-- Cottages Grid (Initial Load) -->
                <div id="cottagesContainer">
                    <?php if (empty($cottages)): ?>
                        <div class="no-results">
                            <i class="fas fa-house-circle-xmark"></i>
                            <h3>
                                <?php if ($search): ?>
                                    No cottages found for "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    No cottages available
                                <?php endif; ?>
                            </h3>
                            <p>Try a different search term or clear your search</p>
                            <?php if ($search): ?>
                                <button type="button" id="clearSearchBtn" class="btn btn-primary mt-3">
                                    <i class="fas fa-times mr-1"></i>Clear Search
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="cottages-grid">
                            <?php foreach ($cottages as $cottage): ?>
                                <div class="card cottage-card">
                                    <!-- Cottage Image -->
                                    <img 
                                        src="../uploads/cottages/<?php echo htmlspecialchars($cottage_images[$cottage['cottage_id']]); ?>" 
                                        class="cottage-card-img"
                                        alt="<?php echo htmlspecialchars($cottage['cottage_name']); ?>"
                                        onerror="this.src='https://via.placeholder.com/400x300/40E0D0/ffffff?text=Aura+Luxe+Resort'">
                                    
                                    <div class="cottage-card-body">
                                        <!-- Title and Price -->
                                        <div class="cottage-title">
                                            <span><?php echo htmlspecialchars($cottage['cottage_name']); ?></span>
                                            <span class="cottage-price">
                                                ₱<?php echo number_format($cottage['price_per_night'], 0); ?>/night
                                            </span>
                                        </div>
                                        
                                        <!-- Description -->
                                        <p class="cottage-description">
                                            <?php echo htmlspecialchars(substr($cottage['description'], 0, 120)); ?>...
                                        </p>
                                        
                                        <!-- Meta Information -->
                                        <div class="cottage-meta">
                                            <span>
                                                <i class="fas fa-users"></i>
                                                Up to <?php echo $cottage['capacity']; ?> guests
                                            </span>
                                            <span>
                                                <i class="fas fa-bed"></i>
                                                <?php echo $cottage['bedrooms'] ?? 1; ?> bedroom(s)
                                            </span>
                                            <span>
                                                <i class="fas fa-tag"></i>
                                                ₱<?php echo number_format($cottage['price_per_night'], 0); ?>/night
                                            </span>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="d-flex justify-content-between">
                                            <a href="cottage-details.php?id=<?php echo $cottage['cottage_id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-eye mr-1"></i>Details
                                            </a>
                                            
                                            <?php if ($is_verified): ?>
                                                <a href="reserve.php?cottage_id=<?php echo $cottage['cottage_id']; ?>" 
                                                   class="btn btn-success">
                                                    <i class="fas fa-calendar-check mr-1"></i>Reserve
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled 
                                                        data-toggle="tooltip" 
                                                        title="Verify your account to make reservations">
                                                    <i class="fas fa-lock mr-1"></i>Reserve
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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

<script>
$(document).ready(function() {
    const searchForm = $('#searchForm');
    const searchInput = $('#searchInput');
    const clearBtn = $('#clearBtn');
    const clearSearchBtn = $('#clearSearchBtn');
    const cottagesContainer = $('#cottagesContainer');
    const resultsCount = $('#resultsCount');
    const loadingOverlay = $('#loadingOverlay');
    const searchExamples = $('.search-example');
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Show/hide clear button based on input value
    function toggleClearButton() {
        if (searchInput.val().trim()) {
            clearBtn.show();
        } else {
            clearBtn.hide();
        }
    }
    
    // Initial check
    toggleClearButton();
    
    // Clear button click handler
    clearBtn.on('click', function() {
        searchInput.val('');
        searchInput.focus();
        toggleClearButton();
        searchForm.trigger('submit');
    });
    
    // Clear search button from no results
    if (clearSearchBtn.length) {
        clearSearchBtn.on('click', function() {
            searchInput.val('');
            searchForm.trigger('submit');
        });
    }
    
    // Search examples click handler
    searchExamples.on('click', function() {
        const searchTerm = $(this).data('search');
        searchInput.val(searchTerm);
        toggleClearButton();
        searchForm.trigger('submit');
    });
    
    // Input event for clear button visibility
    searchInput.on('input', toggleClearButton);
    
    // Handle form submission with AJAX
    searchForm.on('submit', function(e) {
        e.preventDefault();
        
        // Show loading overlay
        loadingOverlay.fadeIn();
        
        // Collect form data
        const searchTerm = searchInput.val().trim();
        const formData = searchTerm ? { search: searchTerm } : {};
        
        // Send AJAX request
        $.ajax({
            url: 'ajax_cottages.php',
            type: 'GET',
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Hide loading overlay
                loadingOverlay.fadeOut();
                
                if (response.success) {
                    // Update results count
                    resultsCount.html(`
                        <span class="badge badge-light" style="font-size: 1rem; padding: 8px 15px;">
                            ${response.count} cottage(s) found
                        </span>
                    `);
                    
                    // Update cottages container
                    if (response.count === 0) {
                        let noResultsHtml = `
                            <div class="no-results">
                                <i class="fas fa-house-circle-xmark"></i>
                                <h3>${searchTerm ? `No cottages found for "${searchTerm}"` : 'No cottages available'}</h3>
                                <p>Try a different search term or clear your search</p>`;
                        
                        if (searchTerm) {
                            noResultsHtml += `
                                <button type="button" id="clearSearchBtn" class="btn btn-primary mt-3">
                                    <i class="fas fa-times mr-1"></i>Clear Search
                                </button>`;
                        }
                        
                        noResultsHtml += `</div>`;
                        cottagesContainer.html(noResultsHtml);
                        
                        // Re-attach event listener for clear button
                        if (searchTerm) {
                            $('#clearSearchBtn').on('click', function() {
                                searchInput.val('');
                                searchForm.trigger('submit');
                            });
                        }
                    } else {
                        let cottagesHtml = '<div class="cottages-grid">';
                        
                        response.cottages.forEach(function(cottage) {
                            const isVerified = <?php echo $is_verified ? 'true' : 'false'; ?>;
                            
                            cottagesHtml += `
                                <div class="card cottage-card">
                                    <img src="../uploads/cottages/${cottage.image}" 
                                         class="cottage-card-img"
                                         alt="${cottage.name}"
                                         onerror="this.src='https://via.placeholder.com/400x300/40E0D0/ffffff?text=Aura+Luxe+Resort'">
                                    
                                    <div class="cottage-card-body">
                                        <div class="cottage-title">
                                            <span>${cottage.name}</span>
                                            <span class="cottage-price">
                                                ₱${cottage.price_formatted}/night
                                            </span>
                                        </div>
                                        
                                        <p class="cottage-description">
                                            ${cottage.description}
                                        </p>
                                        
                                        <div class="cottage-meta">
                                            <span>
                                                <i class="fas fa-users"></i>
                                                Up to ${cottage.capacity} guests
                                            </span>
                                            <span>
                                                <i class="fas fa-bed"></i>
                                                ${cottage.bedrooms || 1} bedroom(s)
                                            </span>
                                            <span>
                                                <i class="fas fa-tag"></i>
                                                ₱${cottage.price_formatted}/night
                                            </span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <a href="cottage-details.php?id=${cottage.id}" 
                                               class="btn btn-primary">
                                                <i class="fas fa-eye mr-1"></i>Details
                                            </a>
                                            
                                            ${isVerified ? 
                                                `<a href="reserve.php?cottage_id=${cottage.id}" 
                                                   class="btn btn-success">
                                                    <i class="fas fa-calendar-check mr-1"></i>Reserve
                                                </a>` 
                                                : 
                                                `<button class="btn btn-secondary" disabled 
                                                        data-toggle="tooltip" 
                                                        title="Verify your account to make reservations">
                                                    <i class="fas fa-lock mr-1"></i>Reserve
                                                </button>`
                                            }
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        cottagesHtml += '</div>';
                        cottagesContainer.html(cottagesHtml);
                        
                        // Re-initialize tooltips
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                    
                    // Update browser URL without reloading page
                    const newUrl = window.location.pathname + (searchTerm ? `?search=${encodeURIComponent(searchTerm)}` : '');
                    window.history.pushState({path: newUrl}, '', newUrl);
                } else {
                    // Handle error
                    alert('Error loading cottages: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                loadingOverlay.fadeOut();
                alert('Network error. Please try again.');
                // Fallback: submit form normally
                searchForm[0].submit();
            }
        });
    });
    
    // Handle browser back/forward buttons
    $(window).on('popstate', function() {
        // Extract search parameter from URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search') || '';
        searchInput.val(searchParam);
        toggleClearButton();
        
        // Trigger form submission
        searchForm.trigger('submit');
    });
    
    // Real-time search with debounce
    let searchTimeout;
    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchForm.trigger('submit');
        }, 500);
    });
});
</script>
</body>
</html>