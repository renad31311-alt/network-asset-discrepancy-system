<?php
require 'db_connection.php';

$full_name = "System Admin";
$email     = "admin@test.com";
$plain_password = "123456";
$role      = "admin";

$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo "<p style='font-family:sans-serif; color:green;'>✅ Test user added successfully</p>";
    echo "<p style='font-family:sans-serif;'>Email: $email</p>";
    echo "<p style='font-family:sans-serif;'>Password: $plain_password</p>";
} else {
    echo "<p style='font-family:sans-serif; color:red;'>❌ Error: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
