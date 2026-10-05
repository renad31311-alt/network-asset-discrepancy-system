<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'technician') {
    header("Location: dashboard.php");
    exit();
}

$full_name = $_SESSION['full_name'];

$pending = $conn->query("
    SELECT na.asset_id, na.asset_code, na.zone, na.material, na.installation_year
    FROM network_assets na
    LEFT JOIN field_surveys fs ON na.asset_id = fs.asset_id
    WHERE fs.survey_id IS NULL
    ORDER BY na.asset_code
");

$my_surveys = $conn->query("
    SELECT na.asset_code, fs.survey_date, fs.surveyed_length, fs.surveyed_diameter
    FROM field_surveys fs
    JOIN network_assets na ON fs.asset_id = na.asset_id
    WHERE fs.technician_id = " . (int)$_SESSION['user_id'] . "
    ORDER BY fs.survey_date DESC
");
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pending Assets</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f7fa; }
    .topbar { background-color: #1F4E79; color: white; padding: 16px 24px; }
  </style>
</head>
<body>

  <div class="topbar d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Network Asset Discrepancy System</h5>
    <div>
      <span class="me-3">Welcome, <?php echo htmlspecialchars($full_name); ?></span>
      <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>

  <div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">Assets Pending Field Survey</h6>
      <a href="add_survey.php" class="btn btn-primary btn-sm">+ Add Field Survey</a>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <?php if ($pending->num_rows === 0): ?>
          <p class="text-muted mb-0">All assets have been surveyed ✅ No pending tasks right now.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Asset Code</th>
                  <th>Zone</th>
                  <th>Recorded Material</th>
                  <th>Installation Year</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $pending->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['asset_code']); ?></td>
                  <td><?php echo htmlspecialchars($row['zone']); ?></td>
                  <td><?php echo htmlspecialchars($row['material']); ?></td>
                  <td><?php echo htmlspecialchars($row['installation_year']); ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">My Survey History</h6>
        <?php if ($my_surveys->num_rows === 0): ?>
          <p class="text-muted mb-0">You haven't submitted any field surveys yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Asset Code</th>
                  <th>Survey Date</th>
                  <th>Surveyed Length</th>
                  <th>Surveyed Diameter</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $my_surveys->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['asset_code']); ?></td>
                  <td><?php echo htmlspecialchars($row['survey_date']); ?></td>
                  <td><?php echo $row['surveyed_length']; ?></td>
                  <td><?php echo $row['surveyed_diameter']; ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</body>
</html>
