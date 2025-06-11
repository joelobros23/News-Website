<?php
// admin/includes/db_connect.php

// --- Database Configuration ---
// Replace with your actual database credentials
define('DB_SERVER', 'localhost'); // Or your cPanel MySQL server hostname
define('DB_USERNAME', 'root'); // Your cPanel MySQL username
define('DB_PASSWORD', ''); // Your cPanel MySQL password
define('DB_NAME', 'news_week_db');     // The name of your database

// --- Establish Database Connection using PDO (Recommended) ---
$dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
    // Database connected successfully echo removed for cleaner output
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage(), 0);
    die("ERROR: Could not connect to the database. Please try again later. If the issue persists, contact support.");
}

// --- MySQLi Connection (Commented out as PDO is used) ---
/*
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_errno) {
    error_log("MySQLi Connection Error: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error, 0);
    die("ERROR: Could not connect to the database. Please try again later.");
}
if (!$mysqli->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $mysqli->error, 0);
}
// echo "Database connected successfully using MySQLi!"; // Removed
*/

if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    // http_response_code(403);
    // die("Direct access not allowed.");
}
?>
