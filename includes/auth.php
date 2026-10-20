<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

function is_logged_in(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['LoginID']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function current_role(): string {
    return $_SESSION['user']['Role'] ?? '';
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to access this page.');
        redirect('auth/login.php');
    }
}

function require_role(string|array $allowedRoles): void {
    require_login();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = current_role();
    
    // Case-insensitive check
    $normalizedAllowed = array_map('strtolower', $roles);
    if (!in_array(strtolower($userRole), $normalizedAllowed, true)) {
        set_flash('danger', 'Access denied. You do not have permission to view that page.');
        // Redirect to user's proper role dashboard
        match (strtolower($userRole)) {
            'admin'   => redirect('admin/dashboard.php'),
            'warden'  => redirect('warden/dashboard.php'),
            'student' => redirect('student/dashboard.php'),
            default   => redirect('auth/login.php'),
        };
    }
}

function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    session_start();
    set_flash('success', 'You have been successfully logged out.');
    redirect('auth/login.php');
}
