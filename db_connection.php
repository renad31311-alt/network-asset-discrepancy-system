<?php
// =========================================================
// Database connection file
// Every PHP page in the project includes this file to talk to MySQL
// =========================================================

// --- Connection settings ---
$db_host = "localhost";
$db_port = 3307;              // Note: custom MySQL port used in this setup (3307, not 3306)
$db_username = "root";        // XAMPP default user
$db_password = "";            // No password by default in XAMPP
$db_name = "network_asset_discrepancy_system";

// --- Create the connection ---
$conn = new mysqli($db_host, $db_username, $db_password, $db_name, $db_port);

// --- Check the connection succeeded ---
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// --- Set charset so Arabic/English text displays correctly ---
$conn->set_charset("utf8mb4");
?>
