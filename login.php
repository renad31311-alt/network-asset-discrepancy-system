<?php
session_start();
require 'db_connection.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'technician') {
                header("Location: pending_assets.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();

        } else {
            $error_message = "Incorrect password";
        }
    } else {
        $error_message = "No account found with this email";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Network Asset Discrepancy System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f7fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-card {
      max-width: 400px;
      width: 100%;
      border-radius: 14px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .login-header {
      background-color: #1F4E79;
      color: white;
      border-radius: 14px 14px 0 0;
      padding: 24px;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="card login-card">
    <div class="login-header">
      <h4 class="mb-0">Network Asset Discrepancy System</h4>
      <small>Login</small>
    </div>
    <div class="card-body p-4">

      <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" name="email" class="form-control" id="email" placeholder="example@nwc.sa" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" name="password" class="form-control" id="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>

    </div>
  </div>

</body>
</html>
