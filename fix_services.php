<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

// Admin access only
if (!isLoggedIn() || getUserType() !== 'admin') {
    echo "<div class='alert alert-danger'>Admin access required</div>";
    require_once 'includes/footer.php';
    exit;
}

echo "<h2>Services Status Fixer</h2>";

// Check for status column existence
$checkStatusColumn = "SHOW COLUMNS FROM services LIKE 'status'";
$statusColumnExists = $db->query($checkStatusColumn)->num_rows > 0;

if (!$statusColumnExists) {
    echo "<div class='alert alert-danger'>Status column does not exist in the services table!</div>";
    
    // Add the column
    $addColumn = "ALTER TABLE services ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
    if ($db->query($addColumn)) {
        echo "<div class='alert alert-success'>Added status column to services table</div>";
    } else {
        echo "<div class='alert alert-danger'>Failed to add status column: " . $db->error . "</div>";
    }
}

// Check service statuses
$checkServices = "SELECT service_id, title, status FROM services";
$serviceResults = $db->query($checkServices);
$services = $serviceResults->fetch_all(MYSQLI_ASSOC);

echo "<h3>Service Status Check</h3>";
echo "<table class='table table-striped'>";
echo "<thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Action</th></tr></thead>";
echo "<tbody>";

foreach ($services as $service) {
    echo "<tr>";
    echo "<td>" . $service['service_id'] . "</td>";
    echo "<td>" . htmlspecialchars($service['title']) . "</td>";
    echo "<td>" . (isset($service['status']) ? $service['status'] : 'NULL') . "</td>";
    echo "<td>";
    echo "<a href='?fix_id=" . $service['service_id'] . "&status=active' class='btn btn-sm btn-success me-1'>Set Active</a>";
    echo "<a href='?fix_id=" . $service['service_id'] . "&status=inactive' class='btn btn-sm btn-secondary'>Set Inactive</a>";
    echo "</td>";
    echo "</tr>";
}

echo "</tbody></table>";

// Handle status update actions
if (isset($_GET['fix_id']) && isset($_GET['status'])) {
    $serviceId = intval($_GET['fix_id']);
    $newStatus = $_GET['status'] === 'active' ? 'active' : 'inactive';
    
    // Direct update with verification
    $updateQuery = "UPDATE services SET status = '$newStatus' WHERE service_id = $serviceId";
    if ($db->query($updateQuery)) {
        echo "<div class='alert alert-success'>Updated service #$serviceId to $newStatus</div>";
        
        // Verify the update worked
        $checkUpdate = "SELECT status FROM services WHERE service_id = $serviceId";
        $checkResult = $db->query($checkUpdate)->fetch_assoc();
        
        if ($checkResult['status'] === $newStatus) {
            echo "<div class='alert alert-success'>Verification: Status is now $newStatus</div>";
        } else {
            echo "<div class='alert alert-danger'>Update verification failed - status is " . $checkResult['status'] . "</div>";
        }
        
        echo "<p><a href='" . APP_URL . "/services'>Check the services page</a> to see if the inactive services are hidden.</p>";
        echo "<p><a href='fix_services.php'>Return to this page</a> to continue fixing services.</p>";
    } else {
        echo "<div class='alert alert-danger'>Failed to update service: " . $db->error . "</div>";
    }
}

// Force clear MySQL query cache
$db->query("RESET QUERY CACHE");

$baseQuery = "
    SELECT s.*, c.name AS category_name, sp.business_name
    WHERE sp.is_verified = 1
    AND s.is_available = 1
    AND s.status = 'active'";

require_once 'includes/footer.php';
?>