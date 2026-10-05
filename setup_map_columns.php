<?php
// =========================================================
// One-time setup script:
// 1) Adds latitude/longitude columns to network_assets
// 2) Sets a sample coordinate for the existing test asset (PIPE-001)
// Run this once, then you can delete it.
// =========================================================

require 'db_connection.php';

// --- Step 1: add the columns (safe to run even if they already exist) ---
$conn->query("ALTER TABLE network_assets ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL");
$conn->query("ALTER TABLE network_assets ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL");

// --- Step 2: set a sample coordinate for PIPE-001 (Riyadh area, adjust as you like) ---
$stmt = $conn->prepare("UPDATE network_assets SET latitude = ?, longitude = ? WHERE asset_code = 'PIPE-001'");
$lat = 24.7742650;
$lng = 46.7385860;
$stmt->bind_param("dd", $lat, $lng);
$stmt->execute();

echo "<p style='font-family:sans-serif; color:green;'>✅ Columns added and PIPE-001 coordinates set successfully</p>";
echo "<p style='font-family:sans-serif;'>You can now add latitude/longitude for other assets the same way, or directly in phpMyAdmin.</p>";

$stmt->close();
$conn->close();
?>
