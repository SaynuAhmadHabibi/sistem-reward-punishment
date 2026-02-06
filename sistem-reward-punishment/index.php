<?php
require_once 'includes/functions.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('login.php');
}

// Redirect ke dashboard jika sudah login
redirect('dashboard.php');
?>