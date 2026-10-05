<?php
session_start();
require 'db_connection.php';

// --- Page protection: must be logged in, and only engineer/admin can review ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (!in_array($_SESSION['role'], ['engineer', 'admin'])) {
    header("Location: dashboard.php");
    exit();
}

// --- Which discrepancy are we viewing? ---
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}
$discrepancy_id = (int)$_GET['id'];

$success_message = "";

// --- When the engineer updates the review status ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = $_POST['review_status'];

    $stmt = $conn->prepare("UPDATE discrepancies SET review_status = ? WHERE discrepancy_id = ?");
    $stmt->bind_param("si", $new_status, $discrepancy_id);
    $stmt->execute();
    $stmt->close();

    $success_message = "Review status updated successfully";
}

// --- Fetch full details for this discrepancy ---
$stmt = $conn->prepare("
    SELECT d.*, na.asset_code, na.zone, na.recorded_length, na.recorded_diameter, na.material AS recorded_material,
           fs.survey_date, fs.surveyed_length, fs.surveyed_diameter, fs.surveyed_material,
           u.full_name AS technician_name
    FROM discrepancies d
    JOIN network_assets na ON d.asset_id = na.asset_id
    JOIN field_surveys fs ON d.survey_id = fs.survey_id
    JOIN users u ON fs.technician_id = u.user_id
    WHERE d.discrepancy_id = ?
");
$stmt->bind_param("i", $discrepancy_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    die("Discrepancy not found.");
}

$status_labels = [
    'pending'    => 'Pending Review',
    'in_review'  => 'In Review',
    'resolved'   => 'Resolved'
];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Discrepancy Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f7fa; }
    .topbar { background-color: #1F4E79; color: white; padding: 16px 24px; }
    .detail-label { color: #6c757d; font-size: 0.85rem; }
    .detail-value { font-weight: 600; }
  </style>
</head>
<body>

  <div class="topbar d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Network Asset Discrepancy System</h5>
    <div>
      <a href="dashboard.php" class="btn btn-sm btn-outline-light me-2">Dashboard</a>
      <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>

  <div class="container mt-4" style="max-width: 800px;">

    <?php if ($success_message): ?>
      <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <h5 class="mb-0"><?php echo htmlspecialchars($row['asset_code']); ?> - <?php echo htmlspecialchars($row['zone']); ?></h5>
          <span class="badge bg-secondary"><?php echo $status_labels[$row['review_status']]; ?></span>
        </div>

        <h6 class="text-muted mb-2">Recorded vs. Surveyed</h6>
        <table class="table table-sm">
          <thead>
            <tr>
              <th></th>
              <th>Recorded (System)</th>
              <th>Surveyed (Field)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Length</td>
              <td><?php echo $row['recorded_length']; ?> m</td>
              <td><?php echo $row['surveyed_length']; ?> m</td>
            </tr>
            <tr>
              <td>Diameter</td>
              <td><?php echo $row['recorded_diameter']; ?> mm</td>
              <td><?php echo $row['surveyed_diameter']; ?> mm</td>
            </tr>
            <tr>
              <td>Material</td>
              <td><?php echo htmlspecialchars($row['recorded_material']); ?></td>
              <td><?php echo htmlspecialchars($row['surveyed_material']); ?></td>
            </tr>
          </tbody>
        </table>

        <div class="row mt-3">
          <div class="col-md-3">
            <div class="detail-label">Length Diff</div>
            <div class="detail-value"><?php echo $row['length_diff_pct']; ?>%</div>
          </div>
          <div class="col-md-3">
            <div class="detail-label">Severity Score</div>
            <div class="detail-value"><?php echo $row['severity_score']; ?></div>
          </div>
          <div class="col-md-3">
            <div class="detail-label">Survey Date</div>
            <div class="detail-value"><?php echo $row['survey_date']; ?></div>
          </div>
          <div class="col-md-3">
            <div class="detail-label">Technician</div>
            <div class="detail-value"><?php echo htmlspecialchars($row['technician_name']); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">Update Review Status</h6>
        <form method="POST" action="discrepancy_details.php?id=<?php echo $discrepancy_id; ?>" class="d-flex gap-2">
          <select name="review_status" class="form-select">
            <option value="pending" <?php echo $row['review_status'] == 'pending' ? 'selected' : ''; ?>>Pending Review</option>
            <option value="in_review" <?php echo $row['review_status'] == 'in_review' ? 'selected' : ''; ?>>In Review</option>
            <option value="resolved" <?php echo $row['review_status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
          </select>
          <button type="submit" class="btn btn-primary">Update</button>
        </form>
      </div>
    </div>

    <a href="dashboard.php" class="btn btn-link mt-3">&larr; Back to Dashboard</a>

  </div>

</body>
</html>
