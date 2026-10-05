<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $plain_password = $_POST['password'];
    $role = $_POST['role'];

    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        $success_message = "User added successfully";
    } else {
        $error_message = "Could not add user: " . $stmt->error;
    }
    $stmt->close();
}

$users = $conn->query("SELECT user_id, full_name, email, role, created_at FROM users ORDER BY created_at DESC");

$role_labels = [
    'technician' => 'Field Technician',
    'engineer'   => 'Reviewing Engineer',
    'admin'      => 'System Admin'
];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users</title>
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
      <a href="dashboard.php" class="btn btn-sm btn-outline-light me-2">Dashboard</a>
      <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>

  <div class="container mt-4">

    <?php if ($success_message): ?>
      <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
      <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3">Add New User</h6>
        <form method="POST" action="manage_users.php" class="row g-3">

          <div class="col-md-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="col-md-2">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
              <option value="technician">Field Technician</option>
              <option value="engineer">Reviewing Engineer</option>
              <option value="admin">System Admin</option>
            </select>
          </div>

          <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Add</button>
          </div>

        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3">All Users</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created At</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $users->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo $role_labels[$row['role']] ?? $row['role']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

</body>
</html>
