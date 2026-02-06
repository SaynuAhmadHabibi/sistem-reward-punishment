<?php
session_start();

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // password asli

    $user = loginUser($username, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        redirect('index.php', 'Login berhasil! Selamat datang ' . $user['nama']);
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Reward & Punishment</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dark-ui.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <!-- Header -->
            <div class="login-header text-center">
                <h2 class="system-title">Sistem Reward & Punishment</h2>
                <p class="system-subtitle">Metode TOPSIS</p>
            </div>

            <!-- Login Form -->
            <div class="login-form-section">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>

            <!-- Demo Accounts -->
            <div class="demo-accounts-section">
                <h6 class="text-center mb-3">Akun Demo</h6>
                <div class="demo-accounts">
                    <div class="account-item">
                        <strong>ADMIN</strong><br>
                        <small>admin / admin123</small>
                    </div>
                    <div class="account-item">
                        <strong>HRD</strong><br>
                        <small>hrd_admin / hrd_admin123</small>
                    </div>
                    <div class="account-item">
                        <strong>DIREKTUR</strong><br>
                        <small>direktur / direktur123</small>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="login-footer text-center">
                <small>&copy; 2026 Sistem Reward & Punishment</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/ui-enhancement.js"></script>
</body>
</html>
