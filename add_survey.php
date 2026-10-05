<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$technician_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $asset_id = $_POST['asset_id'];
    $surveyed_length = $_POST['surveyed_length'];
    $surveyed_diameter = $_POST['surveyed_diameter'];
    $surveyed_material = $_POST['surveyed_material'];
    $survey_date = date("Y-m-d");

    $stmt = $conn->prepare("SELECT recorded_length, recorded_diameter, material FROM network_assets WHERE asset_id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO field_surveys (asset_id, technician_id, surveyed_length, surveyed_diameter, surveyed_material, survey_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiddss", $asset_id, $technician_id, $surveyed_length, $surveyed_diameter, $surveyed_material, $survey_date);
    $stmt->execute();
    $survey_id = $conn->insert_id;
    $stmt->close();

    $python_script_path = __DIR__ . DIRECTORY_SEPARATOR . "calculate_discrepancy.py";
    $command = sprintf(
        "python3 %s %s %s %s %s %s %s",
        escapeshellarg($python_script_path),
        escapeshellarg($asset['recorded_length']),
        escapeshellarg($asset['recorded_diameter']),
        escapeshellarg($asset['material']),
        escapeshellarg($surveyed_length),
        escapeshellarg($surveyed_diameter),
        escapeshellarg($surveyed_material)
    );
    $python_output = shell_exec($command);
    $result = json_decode($python_output, true);

    if ($result) {
        $stmt = $conn->prepare("INSERT INTO discrepancies (asset_id, survey_id, length_diff_pct, diameter_mismatch, material_mismatch, severity_score) VALUES (?, ?, ?, ?, ?, ?)");
        $diameter_mismatch_int = $result['diameter_mismatch'] ? 1 : 0;
        $material_mismatch_int = $result['material_mismatch'] ? 1 : 0;
        $stmt->bind_param("iidiid",
            $asset_id,
            $survey_id,
            $result['length_diff_pct'],
            $diameter_mismatch_int,
            $material_mismatch_int,
            $result['severity_score']
        );
        $stmt->execute();
        $stmt->close();

        $success_message = "Survey saved. Discrepancy calculated: " . $result['length_diff_pct'] . "% (severity score: " . $result['severity_score'] . ")";
    } else {
        $error_message = "Error calculating discrepancy - make sure Python is installed correctly";
    }
}

$assets = $conn->query("SELECT asset_id, asset_code, zone FROM network_assets");
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Field Survey</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f7fa; }
    .topbar { background-color: #1F4E79; color: white; padding: 16px 24px; }
    .form-card { max-width: 500px; margin: 30px auto; border-radius: 14px; }
  </style>
</head>
<body>

  <div class="topbar d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Network Asset Discrepancy System</h5>
    <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
  </div>

  <div class="card form-card">
    <div class="card-body p-4">
      <h5 class="mb-3">Add New Field Survey</h5>

      <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
      <?php endif; ?>
      <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>

      <form method="POST" action="add_survey.php">

        <div class="mb-3">
          <label class="form-label">Select Asset (Pipe)</label>
          <select name="asset_id" class="form-select" required>
            <option value="">-- Select --</option>
            <?php while ($row = $assets->fetch_assoc()): ?>
              <option value="<?php echo $row['asset_id']; ?>">
                <?php echo $row['asset_code'] . " - " . $row['zone']; ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Actual Field Length (meters)</label>
          <input type="number" step="0.01" name="surveyed_length" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Actual Diameter (mm)</label>
          <input type="number" step="0.01" name="surveyed_diameter" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Actual Material Type</label>
          <input type="text" name="surveyed_material" class="form-control" placeholder="e.g. PVC" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Save Survey</button>
      </form>
    </div>
  </div>

</body>
</html>
