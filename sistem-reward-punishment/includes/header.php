<?php
/**
 * Header Template
 * Template header untuk semua halaman
 */

// Cek jika functions.php sudah diinclude
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

// Set default page title jika tidak ada
if (!isset($page_title)) {
    $page_title = 'Sistem Reward & Punishment';
}

// Get current user info
$current_user = getCurrentUser();

// Get flash message
$flash_message = displayFlashMessage();

// Generate CSRF token untuk form
$csrf_token = generateCsrfToken();

// Get base URL
$base_url = BASE_URL;

// Set active menu berdasarkan current page
$current_page = basename($_SERVER['PHP_SELF']);
$request_uri = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- Meta Tags -->
    <title><?php echo htmlspecialchars($page_title); ?> - Sistem Reward & Punishment</title>
    <meta name="description" content="Sistem Reward & Punishment dengan metode TOPSIS">
    <meta name="keywords" content="reward, punishment, topsis, penilaian karyawan">
    <meta name="author" content="Sistem Reward & Punishment">
    <meta name="robots" content="index, follow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $base_url; ?>uploads/logo.png">
    <link rel="apple-touch-icon" href="<?php echo $base_url; ?>uploads/logo.png">
    
    <!-- CSS Libraries -->
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Dark UI CSS (Modern Dark Theme) -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/dark-ui.css">

    
    <!-- Page Specific CSS -->
    <?php if (isset($page_css)): ?>
        <style><?php echo $page_css; ?></style>
    <?php endif; ?>
    
    <!-- CSRF Token Meta Tag -->
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    
    <!-- Additional Head Content -->
    <?php if (isset($head_content)): ?>
        <?php echo $head_content; ?>
    <?php endif; ?>
</head>
<body>
    <!-- CSRF Token Field untuk AJAX -->
    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <?php if (isLoggedIn()): ?>
    <div class="wrapper">
        <!-- Include Sidebar Menu Component -->
        <?php include __DIR__ . '/sidebar_menu.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Content Container -->
            <div class="content-container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                            <?php if (isset($page_subtitle)): ?>
                                <p class="page-subtitle"><?php echo htmlspecialchars($page_subtitle); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($page_header_actions)): ?>
                            <div class="page-actions">
                                <?php echo $page_header_actions; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Breadcrumb -->
                    <?php if (isset($breadcrumb)): ?>
                        <nav aria-label="breadcrumb" class="mt-3">
                            <ol class="breadcrumb">
                                <?php foreach ($breadcrumb as $index => $item): ?>
                                    <li class="breadcrumb-item <?php echo ($index == count($breadcrumb) - 1) ? 'active' : ''; ?>">
                                        <?php if (isset($item['url'])): ?>
                                            <a href="<?php echo $item['url']; ?>"><?php echo $item['text']; ?></a>
                                        <?php else: ?>
                                            <?php echo $item['text']; ?>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    <?php endif; ?>
                </div>

                <!-- Flash Messages -->
                <?php if ($flash_message): ?>
                    <div class="flash-messages">
                        <?php echo $flash_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Main Content Area -->
                <div class="main-content-area">
    <?php else: ?>
    <!-- Non-logged in layout (for login page, etc.) -->
    <div class="auth-wrapper">
    <?php endif; ?>