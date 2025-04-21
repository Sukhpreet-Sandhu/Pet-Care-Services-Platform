<?php
// Direct database connection without relying on includes
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pet_care_platform';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update all inactive accounts to active
$updateQuery = "UPDATE users SET status = 'active' WHERE status = 'pending' OR status = 'inactive'";
$result = $conn->query($updateQuery);

// Count affected rows using a separate query
$countQuery = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
$countResult = $conn->query($countQuery);
$count = $countResult->fetch_assoc()['count'];

// Close connection
$conn->close();

echo "<h2>Account Activation</h2>";
echo "<p>All user accounts have been activated.</p>";
echo "<p>Total active accounts: $count</p>";
echo "<p><a href='http://localhost/pet_care_platform/login.php'>Go to Login Page</a></p>";
?>