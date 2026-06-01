<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$unreadMessagesCount = 0;
$pendingAppointmentsCount = 0;
$adminLogo = '../assets/images/logo.png';

if (isset($conn)) {
    try {
        $inqQuery = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'Unread'");
        if ($inqQuery) $unreadMessagesCount = $inqQuery->fetch_assoc()['count'] ?? 0;

        $apptQuery = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'Pending'");
        if ($apptQuery) $pendingAppointmentsCount = $apptQuery->fetch_assoc()['count'] ?? 0;

        $logoResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'admin_logo'");
        if ($logoResult && $logoResult->num_rows > 0) {
            $logoValue = $logoResult->fetch_assoc()['setting_value'];
            if (!empty($logoValue)) {
                $adminLogo = '../' . $logoValue;
            }
        }
    } catch (Exception $e) {
        // Ignore missing table errors during initial setup
    }
}

$admin_permissions = $_SESSION['admin_permissions'] ?? [];
function has_permission($module) {
    global $admin_permissions;
    return $admin_permissions === 'all' || in_array($module, (array)$admin_permissions);
}
?>
<div class="mobile-admin-header">
    <div class="mobile-logo">
        <img src="<?= htmlspecialchars($adminLogo) ?>" alt="Max Diagnostic Center">
    </div>
    <button class="mobile-menu-btn" id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="<?= htmlspecialchars($adminLogo) ?>" alt="Max Diagnostic Center">
        </div>
        <button class="toggle-btn" id="toggleSidebarBtn" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> <span class="nav-text">Dashboard</span></a></li>
        <?php if (has_permission('doctors')): ?>
            <li><a href="doctors.php" class="<?= $currentPage == 'doctors.php' ? 'active' : '' ?>"><i class="fas fa-user-md"></i> <span class="nav-text">Doctors</span></a></li>
        <?php endif; ?>
        <?php if (has_permission('appointments')): ?>
        <li><a href="appointments.php" class="<?= $currentPage == 'appointments.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> <span class="nav-text">Appointments</span><?php if ($pendingAppointmentsCount > 0): ?> <span class="sidebar-badge"><?= $pendingAppointmentsCount ?></span><?php endif; ?></a></li>
        <?php endif; ?>
        <?php if (has_permission('patients')): ?>
        <li><a href="patients.php" class="<?= $currentPage == 'patients.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> <span class="nav-text">Patients</span></a></li>
        <?php endif; ?>
        <?php if (has_permission('reports')): ?>
        <li><a href="reports.php" class="<?= $currentPage == 'reports.php' ? 'active' : '' ?>"><i class="fas fa-file-medical"></i> <span class="nav-text">Reports</span></a></li>
        <?php endif; ?>
        <?php if (has_permission('messages')): ?>
        <li><a href="messages.php" class="<?= $currentPage == 'messages.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> <span class="nav-text">Messages</span><?php if ($unreadMessagesCount > 0): ?> <span class="sidebar-badge"><?= $unreadMessagesCount ?></span><?php endif; ?></a></li>
        <?php endif; ?>
        <?php if (has_permission('services')): ?>
        <li><a href="services.php" class="<?= $currentPage == 'services.php' ? 'active' : '' ?>"><i class="fas fa-notes-medical"></i> <span class="nav-text">Services</span></a></li>
        <?php endif; ?>
        <?php if (has_permission('facilities')): ?>
        <li><a href="facilities.php" class="<?= $currentPage == 'facilities.php' ? 'active' : '' ?>"><i class="fas fa-microscope"></i> <span class="nav-text">Facilities</span></a></li>
        <?php endif; ?>
        <?php if (has_permission('settings')): ?>
        <li><a href="settings.php" class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> <span class="nav-text">Settings</span></a></li>
        <?php endif; ?>
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin'): ?>
            <li><a href="users.php" class="<?= $currentPage == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> <span class="nav-text">Manage Users</span></a></li>
        <?php endif; ?>
        <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <span class="nav-text">View Site</span></a></li>
        <li><a href="#" id="darkModeToggle"><i class="fas fa-moon"></i> <span class="nav-text">Dark Mode</span></a></li>
        <li class="logout-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
    </ul>
</aside>

<script>
        // Apply dark mode immediately to prevent FOUC
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }

    document.addEventListener("DOMContentLoaded", function() {
            // Dark mode toggle functionality
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (darkModeToggle) {
                const icon = darkModeToggle.querySelector('i');
                const text = darkModeToggle.querySelector('.nav-text');
                
                if (document.body.classList.contains('dark-mode')) {
                    icon.classList.replace('fa-moon', 'fa-sun');
                    text.textContent = 'Light Mode';
                }
                
                darkModeToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('darkMode', 'enabled');
                        icon.classList.replace('fa-moon', 'fa-sun');
                        text.textContent = 'Light Mode';
                    } else {
                        localStorage.setItem('darkMode', 'disabled');
                        icon.classList.replace('fa-sun', 'fa-moon');
                        text.textContent = 'Dark Mode';
                    }
                });
            }

        const btn = document.getElementById('toggleSidebarBtn');
        if(btn) {
            btn.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
            });
        }
        if(localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
        
        // Mobile sidebar toggle
        const mobileBtn = document.getElementById('mobileMenuToggle');
        const overlay = document.getElementById('sidebarOverlay');
        const sidebar = document.getElementById('adminSidebar');
        
        if (mobileBtn && overlay && sidebar) {
            const toggleMobileMenu = () => {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            };
            mobileBtn.addEventListener('click', toggleMobileMenu);
            overlay.addEventListener('click', toggleMobileMenu);
        }
    });
</script>