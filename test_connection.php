<?php
require 'db_connection.php';

echo "<h2 style='font-family:sans-serif; color:green;'>✅ Database connection successful!</h2>";

$result = $conn->query("SHOW TABLES");

echo "<p style='font-family:sans-serif;'>Tables in the database:</p><ul style='font-family:sans-serif;'>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

$conn->close();
?>
