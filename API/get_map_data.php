<?php
// =========================================================
// Internal API: Get Map Data
// =========================================================
// WHAT THIS DOES:
// Returns every network asset's location (latitude/longitude),
// along with its highest recorded severity score (if it has any
// discrepancies at all). This is new data that doesn't exist
// anywhere else on the site - the table shows discrepancies as
// rows, but never "where is this asset on a map?".
//
// WHO USES IT:
// The interactive map on the dashboard, built with Leaflet.js.
// =========================================================

session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

require '../db_connection.php';

// LEFT JOIN so assets with no discrepancy yet still show up on the map
// (with severity_score = null), and MAX() picks the worst one if an
// asset somehow has more than one discrepancy recorded.
$result = $conn->query("
    SELECT na.asset_code, na.zone, na.latitude, na.longitude,
           MAX(d.severity_score) AS severity_score
    FROM network_assets na
    LEFT JOIN discrepancies d ON na.asset_id = d.asset_id
    WHERE na.latitude IS NOT NULL AND na.longitude IS NOT NULL
    GROUP BY na.asset_id, na.asset_code, na.zone, na.latitude, na.longitude
");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "asset_code" => $row['asset_code'],
        "zone" => $row['zone'],
        "latitude" => (float)$row['latitude'],
        "longitude" => (float)$row['longitude'],
        "severity_score" => $row['severity_score'] !== null ? (float)$row['severity_score'] : null
    ];
}

echo json_encode(["status" => "success", "data" => $data], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
