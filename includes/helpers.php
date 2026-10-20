<?php
require_once __DIR__ . '/../config/config.php';

// Sanitize user inputs
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

// Redirect helper
function redirect(string $path): void {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: " . $path);
    } else {
        header("Location: " . BASE_URL . '/' . ltrim($path, '/'));
    }
    exit;
}

// Flash Messages
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function display_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $type = htmlspecialchars($_SESSION['flash']['type']);
        $message = htmlspecialchars($_SESSION['flash']['message']);
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['flash']);
    }
}

// Format Currency in INR
function format_currency(float|int|string $amount): string {
    return '₹ ' . number_format((float)$amount, 2);
}

// Format Date / Datetime
function format_date(?string $datetime, bool $includeTime = false): string {
    if (empty($datetime)) {
        return '—';
    }
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    return date($includeTime ? 'd M Y, h:i A' : 'd M Y', $ts);
}

// Status Badges
function get_status_badge(string $status): string {
    $normalized = strtolower(trim($status));
    $badgeClass = match ($normalized) {
        'active', 'paid', 'resolved', 'checked in', 'available' => 'bg-success',
        'pending', 'in progress', 'double', 'triple'           => 'bg-warning text-dark',
        'overdue', 'rejected', 'full', 'inactive'              => 'bg-danger',
        'maintenance', 'vacated', 'transferred', 'checked out' => 'bg-secondary',
        default                                                => 'bg-info text-dark'
    };
    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>';
}

// Active link detector
function is_active_nav(string $path): string {
    $current = $_SERVER['REQUEST_URI'];
    return (strpos($current, $path) !== false) ? 'active' : '';
}
