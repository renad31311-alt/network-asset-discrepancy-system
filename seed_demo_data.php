<?php
// =========================================================
// One-time demo data seeder: adds 6 varied assets (different
// zones, materials, severity levels) so the dashboard, chart,
// and map all show rich, realistic-looking data.
// Run this once, then you can delete it.
// =========================================================

require 'db_connection.php';

// Find an existing technician to credit the surveys to
$tech_result = $conn->query("SELECT user_id FROM users WHERE role = 'technician' LIMIT 1");
if ($tech_result->num_rows === 0) {
    die("No technician user found. Please add one first via Manage Users.");
}
$technician_id = $tech_result->fetch_assoc()['user_id'];

// Each entry: [asset_code, zone, lat, lng, recorded_length, recorded_diameter, material, year,
//              surveyed_length, surveyed_diameter, surveyed_material]
$demo_assets = [
    ["PIPE-002", "Al Malaz District",      24.6901, 46.7219, 150, 250, "Steel",    1998, 120, 250, "Steel"],
    ["PIPE-003", "Al Olaya District",      24.6939, 46.6852, 300, 400, "PVC",      2010, 300, 400, "PVC"],
    ["PIPE-004", "Al Rawdah District",     24.7658, 46.7900, 180, 200, "HDPE",     2015, 100, 250, "Steel"],
    ["PIPE-005", "Al Naseem District",     24.6408, 46.8336, 220, 300, "PVC",      2008, 210, 300, "PVC"],
    ["PIPE-006", "Al Sulimaniyah District",24.6977, 46.6951, 160, 150, "Concrete", 1995, 140, 150, "Concrete"],
    ["PIPE-007", "Diriyah Road Zone",      24.7513, 46.5750, 250, 350, "Steel",    2001, 200, 350, "PVC"],
];

$python_script_path = __DIR__ . DIRECTORY_SEPARATOR . "calculate_discrepancy.py";
$added = 0;

foreach ($demo_assets as $a) {
    list($code, $zone, $lat, $lng, $rlen, $rdia, $rmat, $ryear, $slen, $sdia, $smat) = $a;

    // 1) Insert the asset (skip if it already exists, e.g. script run twice)
    $stmt = $conn->prepare("INSERT IGNORE INTO network_assets (asset_code, recorded_length, recorded_diameter, material, installation_year, zone, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddsisdd", $code, $rlen, $rdia, $rmat, $ryear, $zone, $lat, $lng);
    $stmt->execute();
    $stmt->close();

    // 2) Get this asset's ID
    $stmt = $conn->prepare("SELECT asset_id FROM network_assets WHERE asset_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $asset_id = $stmt->get_result()->fetch_assoc()['asset_id'];
    $stmt->close();

    // 3) Insert the field survey
    $survey_date = date("Y-m-d");
    $stmt = $conn->prepare("INSERT INTO field_surveys (asset_id, technician_id, surveyed_length, surveyed_diameter, surveyed_material, survey_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiddss", $asset_id, $technician_id, $slen, $sdia, $smat, $survey_date);
    $stmt->execute();
    $survey_id = $conn->insert_id;
    $stmt->close();

    // 4) Call the same Python script used everywhere else to calculate the discrepancy
    $command = sprintf(
        "python3 %s %s %s %s %s %s %s",
        escapeshellarg($python_script_path),
        escapeshellarg($rlen), escapeshellarg($rdia), escapeshellarg($rmat),
        escapeshellarg($slen), escapeshellarg($sdia), escapeshellarg($smat)
    );
    $result = json_decode(shell_exec($command), true);

    if ($result) {
        $dm = $result['diameter_mismatch'] ? 1 : 0;
        $mm = $result['material_mismatch'] ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO discrepancies (asset_id, survey_id, length_diff_pct, diameter_mismatch, material_mismatch, severity_score) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidiid", $asset_id, $survey_id, $result['length_diff_pct'], $dm, $mm, $result['severity_score']);
        $stmt->execute();
        $stmt->close();
        $added++;
        echo "<p style='font-family:sans-serif; color:green;'>✅ $code added - severity score: {$result['severity_score']}</p>";
    } else {
        echo "<p style='font-family:sans-serif; color:red;'>❌ $code - Python calculation failed</p>";
    }
}

echo "<h4 style='font-family:sans-serif;'>Done - $added assets added with surveys and discrepancies.</h4>";

$conn->close();
?>
