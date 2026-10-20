<?php
require_once __DIR__ . '/config.php';

// Database credentials for XAMPP
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hostelhub_db');

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If database does not exist, attempt auto-creation from database.sql
        if ($e->getCode() == 1049) {
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Now connect to the new DB
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                // Auto-import database.sql if present
                $sqlFile = dirname(__DIR__) . '/database.sql';
                if (file_exists($sqlFile)) {
                    $sqlContent = file_get_contents($sqlFile);
                    $pdo->exec($sqlContent);
                }
            } catch (Exception $initEx) {
                die("HostelHub Database Setup Error: " . htmlspecialchars($initEx->getMessage()));
            }
        } else {
            die("HostelHub Database Connection Error: " . htmlspecialchars($e->getMessage()) . "<br>Please ensure Apache and MySQL are running in your XAMPP Control Panel.");
        }
    }

    return $pdo;
}

$pdo = getDBConnection();
