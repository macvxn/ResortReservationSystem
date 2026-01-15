<?php
// Navbar for User Dashboard
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Header -->
<nav class="main-header navbar navbar-expand-md navbar-light" style="background: linear-gradient(135deg, #40E0D0 0%, #00FFFF 100%);">
    <div class="container-fluid">
        <!-- Brand/logo -->
        <a href="dashboard.php" class="navbar-brand" style="color: white; font-weight: 600;">
            <i class="fas fa-umbrella-beach mr-1"></i>Aura Luxe Resort
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" 
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation" 
                style="border-color: rgba(255,255,255,0.5);">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>

        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <a href="dashboard.php" class="nav-link" style="color: white;">
                        <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                    <a href="profile.php" class="nav-link" style="color: white;">
                        <i class="fas fa-user mr-1"></i>Profile
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'cottages.php') ? 'active' : ''; ?>">
                    <a href="cottages.php" class="nav-link" style="color: white;">
                        <i class="fas fa-home mr-1"></i>Browse Cottages
                    </a>
                </li>
                <li class="nav-item <?php echo ($current_page == 'my-reservations.php') ? 'active' : ''; ?>">
                    <a href="my-reservations.php" class="nav-link" style="color: white;">
                        <i class="fas fa-calendar-check mr-1"></i>My Reservations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="nav-link" style="color: white;">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>