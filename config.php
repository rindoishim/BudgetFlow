<?php
/**
 * BudgetFlow Configuration File
 * This file contains database credentials and initializes the database connection
 */

// Start session at the very top (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting Configuration
// IMPORTANT: Set to 0 in production for security!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Configuration
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_USER', 'if0_41015341');
define('DB_PASS', 'XYrtFwO9tFgvB');
define('DB_NAME', 'if0_41015341_budgetflow_db');

// Database Connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection and handle errors
if (!$conn) {
    // Log error to file instead of displaying (more secure for production)
    error_log("Database connection failed: " . mysqli_connect_error());
    
    // Display user-friendly message
    die("Unable to connect to the database. Please try again later.");
}

// Set charset to UTF-8 to prevent SQL injection and handle special characters
mysqli_set_charset($conn, "utf8mb4");

// Optional: Set timezone (adjust to your timezone)
date_default_timezone_set('Asia/Manila');

/**
 * Helper function to safely close database connection
 */
function closeConnection() {
    global $conn;
    if ($conn) {
        mysqli_close($conn);
    }
}

// Register shutdown function to close connection
register_shutdown_function('closeConnection');
?>
