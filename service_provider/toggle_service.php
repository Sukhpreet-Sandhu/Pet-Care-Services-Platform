<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];

if (isset($_GET['id'])) {
    $serviceId = intval($_GET['id']);
    
    // Get current service details
    $query = "SELECT * FROM services WHERE service_id = $serviceId AND provider_id = $providerId";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        $service = $result->fetch_assoc();
        echo "<div class='container mt-4'>";
        echo "<h2>Toggle Service Status</h2>";
        echo "<div class='alert alert-info'>";
        echo "<p><strong>Service:</strong> " . htmlspecialchars($service['title']) . "</p>";
        echo "<p><strong>Current Status:</strong> " . htmlspecialchars($service['status']) . "</p>";
        echo "</div>";
        
        // If form is submitted, update the status
        if (isset($_POST['new_status'])) {
            $newStatus = $_POST['new_status'];
            
            $updateQuery = "UPDATE services SET status = '$newStatus' WHERE service_id = $serviceId AND provider_id = $providerId";
            if ($db->query($updateQuery)) {
                echo "<div class='alert alert-success'>Service status updated to '$newStatus'</div>";
                echo "<p>Check the <a href='" . APP_URL . "/services'>public services page</a> to verify the service is hidden.</p>";
            } else {
                echo "<div class='alert alert-danger'>Failed to update status: " . $db->error . "</div>";
            }
        }
        
        // Show the form
        echo "<form method='post' action=''>";
        echo "<div class='form-group mb-3'>";
        echo "<label for='new_status'>Set New Status:</label>";
        echo "<select name='new_status' id='new_status' class='form-control'>";
        echo "<option value='active'" . ($service['status'] === 'active' ? ' selected' : '') . ">Active</option>";
        echo "<option value='inactive'" . ($service['status'] === 'inactive' ? ' selected' : '') . ">Inactive</option>";
        echo "</select>";
        echo "</div>";
        echo "<button type='submit' class='btn btn-primary'>Update Status</button>";
        echo "</form>";
        
        echo "<div class='mt-3'><a href='" . APP_URL . "/service_provider/services.php' class='btn btn-secondary'>Back to Services</a></div>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>Service not found or you don't have permission to modify it.</div>";
    }
} else {
    echo "<div class='alert alert-danger'>No service ID provided.</div>";
}

require_once '../includes/footer.php';
?>