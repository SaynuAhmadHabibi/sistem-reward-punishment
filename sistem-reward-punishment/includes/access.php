<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== AUTH BASIC ===== */
function isLogin(): bool {
    return isset($_SESSION['user_id'], $_SESSION['user_role']);
}

function getRole(): string {
    return strtolower($_SESSION['user_role'] ?? '');
}

/* ===== ROLE CHECK ===== */
function isAdmin(): bool {
    return in_array(getRole(), ['admin', 'hrd_admin']);
}

function canViewReport(): bool {
    return in_array(getRole(), ['admin', 'hrd_admin', 'direktur']);
}

/* ===== REDIRECT ===== */
function redirect(string $url) {
    header("Location: $url");
    exit;
}
