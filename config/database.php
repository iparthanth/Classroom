<?php

/**
 * Database Configuration
 */

require_once __DIR__ . '/../includes/database.php';

// Database credentials
// On Railway, values come from the MySQL plugin environment variables.
// Locally (XAMPP), it falls back to the default credentials below.
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');     // Default XAMPP MySQL username
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');     // Default XAMPP MySQL password
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'elearning_system'); // Your database name

try {
    $db = new Database(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );

    // Set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>