<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$role = $_SESSION['role']; // 'engineer' or 'admin'

require 'db_connection.php';

$discrepancies = $conn->query("
    SELECT d.discrepancy_id, na.asset_code, na.zone, fs.survey_date,
           d.length_diff_pct, d.diameter_mismatch, d.material_mismatch,
           d.severity_score, d.review_status, u.full_name AS technician_name
    FROM discrepancies d
    JOIN network_assets na ON d.asset_id = na.asset_id
    JOIN field_surveys fs ON d.survey_id = fs.survey_id
    JOIN users u ON fs.technician_id = u.user_id
    ORDER BY d.severity_score DESC
");

$discrepancy_rows = $discrepancies->fetch_all(MYSQLI_ASSOC);
// Note: the chart below no longer uses PHP data directly - it calls
// our own api/get_discrepancies.php instead (see the script at the
// bottom of this page).
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Network Asset Discrepancy System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

    <?php if ($role == 'admin'): ?>
      <div class="alert alert-info">
        You are logged in as <strong>System Admin</strong>.
        From here you can view full reports and manage users.
      </div>
      <a href="manage_users.php" class="btn btn-primary">Manage Users</a>

    <?php elseif ($role == 'engineer'): ?>
      <div class="alert alert-info">
        You are logged in as <strong>Reviewing Engineer</strong>.
        From here you can review detected discrepancies.
      </div>
    <?php endif; ?>

    <!-- Quick Stats: new, computed by the summary stats API, not shown anywhere else -->
    <div id="quickStats" class="row g-3 mt-1 mb-1"></div>

    <!-- Interactive map: new, powered by api/get_map_data.php -->
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="card-title mb-3">Asset Map</h6>
        <div id="assetMap" style="height: 350px; border-radius: 8px;"></div>
      </div>
    </div>

    <!-- Severity chart -->
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="card-title mb-3">Severity Score by Asset</h6>
        <?php if (count($discrepancy_rows) === 0): ?>
          <p class="text-muted">No data to chart yet.</p>
        <?php else: ?>
          <canvas id="severityChart" height="100"></canvas>
        <?php endif; ?>
      </div>
    </div>

    <!-- Discrepancies table -->
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="card-title mb-3">Discrepancies (sorted from most to least severe)</h6>

        <?php if (count($discrepancy_rows) === 0): ?>
          <p class="text-muted">No discrepancies recorded yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Asset Code</th>
                  <th>Zone</th>
                  <th>Survey Date</th>
                  <th>Length Diff %</th>
                  <th>Diameter Mismatch</th>
                  <th>Material Mismatch</th>
                  <th>Severity Score</th>
                  <th>Technician</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($discrepancy_rows as $row):
                  $severity = $row['severity_score'];
                  if ($severity >= 50) {
                      $badge_class = "bg-danger";
                  } elseif ($severity >= 20) {
                      $badge_class = "bg-warning text-dark";
                  } else {
                      $badge_class = "bg-success";
                  }
                ?>
                <tr onclick="window.location='discrepancy_details.php?id=<?php echo $row['discrepancy_id']; ?>'" style="cursor:pointer;">
                  <td><?php echo htmlspecialchars($row['asset_code']); ?></td>
                  <td><?php echo htmlspecialchars($row['zone']); ?></td>
                  <td><?php echo htmlspecialchars($row['survey_date']); ?></td>
                  <td><?php echo $row['length_diff_pct']; ?>%</td>
                  <td><?php echo $row['diameter_mismatch'] ? 'Yes' : 'No'; ?></td>
                  <td><?php echo $row['material_mismatch'] ? 'Yes' : 'No'; ?></td>
                  <td><span class="badge <?php echo $badge_class; ?>"><?php echo $severity; ?></span></td>
                  <td><?php echo htmlspecialchars($row['technician_name']); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>

  <script>
    // Build the interactive map, using data from our own API
    fetch('api/get_map_data.php')
      .then(response => response.json())
      .then(result => {
        const assets = result.data;

        // Default center: average of all asset coordinates (falls back to Riyadh if empty)
        const centerLat = assets.length ? assets.reduce((s, a) => s + a.latitude, 0) / assets.length : 24.7136;
        const centerLng = assets.length ? assets.reduce((s, a) => s + a.longitude, 0) / assets.length : 46.6753;

        const map = L.map('assetMap').setView([centerLat, centerLng], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        assets.forEach(asset => {
          let color = '#6c757d'; // gray = not surveyed yet
          if (asset.severity_score !== null) {
            color = asset.severity_score >= 50 ? '#dc3545'
                  : asset.severity_score >= 20 ? '#ffc107'
                  : '#198754';
          }

          L.circleMarker([asset.latitude, asset.longitude], {
            radius: 10,
            fillColor: color,
            color: '#fff',
            weight: 2,
            fillOpacity: 0.9
          })
          .bindPopup(`<strong>${asset.asset_code}</strong><br>${asset.zone}<br>Severity: ${asset.severity_score ?? 'Not surveyed yet'}`)
          .addTo(map);
        });
      });
  </script>

  <script>
    // Fetch the summary statistics (new data, calculated by SQL aggregates
    // on the server, not available anywhere else on the page) and render
    // them as quick-glance cards at the top of the dashboard.
    fetch('api/get_summary_stats.php')
      .then(response => response.json())
      .then(stats => {
        const statusLabels = { pending: 'Pending', in_review: 'In Review', resolved: 'Resolved' };
        const cards = [
          { label: 'Total Discrepancies', value: stats.total_discrepancies, color: '#1F4E79' },
          { label: 'Average Severity', value: stats.average_severity, color: '#6c757d' },
          { label: 'Pending Review', value: stats.review_status_breakdown.pending, color: '#dc3545' },
          { label: 'Most Critical Zone', value: stats.most_critical_zone ?? '—', color: '#198754' }
        ];

        const container = document.getElementById('quickStats');
        container.innerHTML = cards.map(c => `
          <div class="col-md-3">
            <div class="card">
              <div class="card-body text-center">
                <div class="text-muted small">${c.label}</div>
                <div class="fs-4 fw-bold" style="color:${c.color}">${c.value}</div>
              </div>
            </div>
          </div>
        `).join('');
      });
  </script>

  <?php if (count($discrepancy_rows) > 0): ?>
  <script>
    // The chart no longer gets its data from PHP directly.
    // Instead, it calls our own internal API and asks for the data.
    fetch('api/get_discrepancies.php')
      .then(response => response.json())
      .then(result => {
        const labels = result.data.map(row => row.asset_code);
        const values = result.data.map(row => row.severity_score);

        const ctx = document.getElementById('severityChart');
        new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Severity Score',
              data: values,
              backgroundColor: values.map(v => v >= 50 ? '#dc3545' : (v >= 20 ? '#ffc107' : '#198754'))
            }]
          },
          options: {
            scales: {
              y: { beginAtZero: true, max: 100 }
            },
            plugins: {
              legend: { display: false }
            }
          }
        });
      });
  </script>
  <?php endif; ?>

</body>
</html>
