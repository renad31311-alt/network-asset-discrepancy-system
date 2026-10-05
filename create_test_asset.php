<?php
require 'db_connection.php';

$stmt = $conn->prepare("INSERT INTO network_assets (asset_code, recorded_length, recorded_diameter, material, installation_year, zone) VALUES (?, ?, ?, ?, ?, ?)");

$asset_code = "PIPE-001";
$recorded_length = 200;
$recorded_diameter = 300;
$material = "PVC";
$installation_year = 2005;
$zone = "Al Nakheel District";

$stmt->bind_param("sddsis", $asset_code, $recorded_length, $recorded_diameter, $material, $installation_year, $zone);

if ($stmt->execute()) {
    echo "<p style='font-family:sans-serif; color:green;'>✅ Test asset added: $asset_code (recorded length: $recorded_length m)</p>";
} else {
    echo "<p style='font-family:sans-serif; color:red;'>❌ Error: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
