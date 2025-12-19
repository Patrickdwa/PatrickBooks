<?php
// conn.php - Database Configuration

// 1. MARIADB
define('DB_HOST', 'host-anda');
define('DB_PORT', '3306');
define('DB_NAME', 'perpustakaan');
define('DB_USER', 'username-anda');
define('DB_PASS', 'password-anda');

// 2. MONGODB
define('MONGO_HOST', 'host-anda');
define('MONGO_PORT', '27017');
define('MONGO_DB_LOGS', 'library_logs');
define('MONGO_USER', 'username-anda');
define('MONGO_PASS', 'password-anda');

// Connect MariaDB
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true, 
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("Database service unavailable.");
}

// Connect MongoDB
$mongoManager = null;
try {
    $uri = "mongodb://" . MONGO_USER . ":" . MONGO_PASS . "@" . MONGO_HOST . ":" . MONGO_PORT . "/" . MONGO_DB_LOGS . "?authSource=admin";
    $mongoManager = new MongoDB\Driver\Manager($uri);
} catch (Exception $e) {
    error_log("MongoDB Connection Error: " . $e->getMessage());
}
?>