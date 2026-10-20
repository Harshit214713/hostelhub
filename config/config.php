<?php
// Prevent multiple inclusions
if (!defined('HOSTELHUB_CONFIG')) {
    define('HOSTELHUB_CONFIG', true);

    // Error reporting (friendly in production, detailed for debugging)
    error_reporting(E_ALL);
    ini_set('display_errors', '0');

    // Default Timezone
    date_default_timezone_set('Asia/Kolkata');

    // Start Session if not already active
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }

    // App Branding & Constants
    define('APP_NAME', 'HostelHub');
    define('APP_TITLE', 'College Hostel Management System');
    define('APP_INSTITUTION', 'K. K. Wagh Institute of Engineering Education & Research');
    define('THEME_COLOR', '#149d96');

    // Auto-detect Base URL for XAMPP
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // Find project root folder
    $docRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $serverDocRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    if (!empty($serverDocRoot) && strpos($docRoot, $serverDocRoot) === 0) {
        $subDir = substr($docRoot, strlen($serverDocRoot));
        $baseUrl = rtrim($protocol . $host . $subDir, '/');
    } else {
        // Fallback for direct localhost/hms or virtual hosts
        $baseUrl = rtrim($protocol . $host . '/hms', '/');
    }
    
    define('BASE_URL', $baseUrl);
}
