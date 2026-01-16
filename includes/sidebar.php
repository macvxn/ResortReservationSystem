<?php
// admin/includes/sidebar.php
// Preserve existing session check
require_once '../config/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background: linear-gradient(180deg, #1a2a3a 0%, #2c3e50 100%);">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link" style="border-bottom: 1px solid rgba(64, 224, 208, 0.2);">
        <span class="brand-text font-weight-light" style="color: #40E0D0; font-weight: 600 !important;">
            <i class="fas fa-umbrella-beach mr-2" style="color: #FFD300;"></i>Aura Luxe Admin
        </span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex" style="border-bottom: 1px solid rgba(64, 224, 208, 0.2);">
            <div class="image">
                <i class="fas fa-user-circle img-circle elevation-2" style="color: #40E0D0; font-size: 2rem;"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block" style="color: #FFF5E1;">
                    <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                    <small style="color: #40E0D0; display: block;">
                        <i class="fas fa-shield-alt mr-1"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Administrator'); ?>
                    </small>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt" style="color: #00FFFF;"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Bookings Management -->
                <li class="nav-item">
                    <a href="bookings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-check" style="color: #40E0D0;"></i>
                        <p>Bookings</p>
                    </a>
                </li>

                <!-- Rooms Management -->
                <li class="nav-item">
                    <a href="rooms.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-bed" style="color: #FF7F50;"></i>
                        <p>Rooms</p>
                    </a>
                </li>

                <!-- Users Management -->
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users" style="color: #FC6C85;"></i>
                        <p>Users</p>
                    </a>
                </li>

                <!-- Packages Management -->
                <li class="nav-item">
                    <a href="packages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-gift" style="color: #FFD300;"></i>
                        <p>Packages</p>
                    </a>
                </li>

                <!-- Reports -->
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-bar" style="color: #00FFFF;"></i>
                        <p>Reports</p>
                    </a>
                </li>

                <!-- Settings -->
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cog" style="color: #40E0D0;"></i>
                        <p>Settings</p>
                    </a>
                </li>

                <!-- Divider -->
                <li class="nav-header mt-3" style="color: #FFF5E1; opacity: 0.7;">ACTIONS</li>

                <!-- Quick Actions -->
                <li class="nav-item">
                    <a href="bookings.php?action=add" class="nav-link">
                        <i class="nav-icon fas fa-plus-circle" style="color: #FFD300;"></i>
                        <p>New Booking</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="rooms.php?action=add" class="nav-link">
                        <i class="nav-icon fas fa-plus-square" style="color: #00FFFF;"></i>
                        <p>Add Room</p>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item mt-3">
                    <a href="../auth/logout.php" class="nav-link" style="color: #FC6C85;">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>