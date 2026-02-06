<?php
/**
 * Sidebar Menu Component - FIXED
 * Komponen menu sidebar yang dapat digunakan di semua halaman
 * 
 * FIXED:
 * - Admin & HRD sekarang bisa akses Karyawan, Penilaian, Reward, Punishment
 * - Direktur hanya lihat menu yang read-only
 * - Sesuaikan dengan permission yang benar
 */

// Pastikan auth_helper sudah di-load
if (!function_exists('canCreate')) {
    require_once __DIR__ . '/auth_helper.php';
}

// Pastikan functions sudah di-load
if (!function_exists('isAdminOrHRD')) {
    require_once __DIR__ . '/auth_helper.php';
}

// Set menu items dengan permissions
// Base URL fallback (in case BASE_URL not defined)
$base_url = defined('BASE_URL') ? BASE_URL : '/sistem-reward-punishment/';

$menu_items = [
    [
        'label' => 'Dashboard',
        'url' => (strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false) ? '#' : $base_url . 'dashboard.php',
        'icon' => 'fa-tachometer-alt',
        'requires' => 'none'
    ]
];

// Data Master section - FIX: ADMIN & HRD BISA AKSES
if (isAdminOrHRD()) {
    $menu_items[] = [
        'section' => 'DATA MASTER',
        'type' => 'header'
    ];
    
    $menu_items[] = [
        'label' => 'Karyawan',
        'url' => $base_url . 'karyawan/',
        'icon' => 'fa-users',
        'requires' => 'manage_employees'
    ];
    
    $menu_items[] = [
        'label' => 'Penilaian',
        'url' => $base_url . 'penilaian/',
        'icon' => 'fa-chart-line',
        'requires' => 'manage_evaluations'
    ];
}

// Rewards & Punishments section - FIX: ADMIN & HRD BISA AKSES
if (isAdminOrHRD()) {
    $menu_items[] = [
        'section' => 'REWARDS & PUNISHMENTS',
        'type' => 'header'
    ];
    
    $menu_items[] = [
        'label' => 'Reward',
        'url' => $base_url . 'reward/',
        'icon' => 'fa-trophy',
        'icon_color' => '#10b981',
        'requires' => 'manage_rewards'
    ];
    
    $menu_items[] = [
        'label' => 'Punishment',
        'url' => $base_url . 'punishment/',
        'icon' => 'fa-exclamation-triangle',
        'icon_color' => '#dc3545',
        'requires' => 'manage_punishments'
    ];
}

// Laporan section - ADMIN, HRD, & DIREKTUR bisa lihat
if (isAdminOrHRD() || isDirektur()) {
    $menu_items[] = [
        'section' => 'LAPORAN & ANALISIS',
        'type' => 'header'
    ];
    
    $menu_items[] = [
        'label' => 'Laporan',
        'url' => $base_url . 'laporan/',
        'icon' => 'fa-file-alt',
        'requires' => 'generate_reports'
    ];
}

// Administrasi section (Admin & HRD only)
if (isAdminOrHRD()) {
    $menu_items[] = [
        'section' => 'ADMINISTRASI',
        'type' => 'header'
    ];
    
    $menu_items[] = [
        'label' => 'Manajemen User',
        'url' => $base_url . 'users/',
        'icon' => 'fa-user-cog',
        'requires' => 'manage_users'
    ];
    
    $menu_items[] = [
        'label' => 'Backup Database',
        'url' => $base_url . 'backup/',
        'icon' => 'fa-database',
        'requires' => 'backup_data'
    ];
}

// Akun section - semua bisa akses
$menu_items[] = [
    'section' => 'AKUN',
    'type' => 'header'
];

$menu_items[] = [
    'label' => 'Profil',
    'url' => $base_url . 'profile.php',
    'icon' => 'fa-user-circle',
    'requires' => 'none'
];

$menu_items[] = [
    'label' => 'Permission Test',
    'url' => $base_url . 'permission_test.php',
    'icon' => 'fa-shield-alt',
    'requires' => 'none'
];


?>

<!-- Sidebar Menu -->
<nav class="sidebar" id="sidebar" data-current-page="<?php echo $current_page; ?>" data-current-dir="<?php echo $current_dir; ?>">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-shield-alt" style="font-size: 2rem; color: white;"></i>
        </div>
        <h3 class="sidebar-title">Reward & Punishment</h3>
        <p class="sidebar-subtitle">Sistem Manajemen</p>

        <!-- HRD Profile Section - Compact Horizontal -->
        <div class="sidebar-user-info">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($current_user['nama'] ?? 'User'); ?></p>
                    <p class="user-role">
                        <?php
                            if (isAdminOrHRD()) {
                                if (isAdmin()) {
                                    echo '<span class="badge bg-success">Admin</span>';
                                } else {
                                    echo '<span class="badge bg-danger">HRD Admin</span>';
                                }
                            } elseif (isDirektur()) {
                                echo '<span class="badge bg-primary">Direktur</span>';
                            } else {
                                echo '<span class="badge bg-secondary">User</span>';
                            }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div class="sidebar-content">

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <?php foreach ($menu_items as $item): ?>
                <?php if (isset($item['type']) && $item['type'] == 'header'): ?>
                    <!-- Menu Section Header -->
                    <li class="menu-section-header">
                        <span><?php echo htmlspecialchars($item['section']); ?></span>
                    </li>
                <?php else: ?>
                    <!-- Menu Item -->
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars($item['url']); ?>"
                           class="nav-link <?php
                               $is_active = false;
                               if ($item['url'] === '#') {
                                   $is_active = true;
                               } elseif (strpos($item['url'], $current_dir) !== false ||
                                        strpos($item['url'], $current_page) !== false) {
                                   $is_active = true;
                               }
                               echo $is_active ? 'active' : '';
                           ?> <?php echo isset($item['class']) ? $item['class'] : ''; ?>">
                            <i class="fas <?php echo htmlspecialchars($item['icon']); ?> nav-icon"
                               <?php if (isset($item['icon_color'])): ?>
                               style="color: <?php echo htmlspecialchars($item['icon_color']); ?>;"
                               <?php endif; ?>></i>
                            <span class="nav-text"><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

        <!-- Logout Button Section -->
        <div class="sidebar-logout">
            <a href="<?php echo htmlspecialchars($base_url . 'logout.php'); ?>" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span class="logout-text">Logout</span>
            </a>
        </div>
    </div>
</nav>

<!-- Toggle Button untuk Mobile -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Inline styles and scripts moved to central assets (assets/css/dark-ui.css, assets/js/ui-enhancement.js) -->
