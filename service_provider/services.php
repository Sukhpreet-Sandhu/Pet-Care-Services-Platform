<?php
$pageTitle = 'My Services';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];

// Handle service deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $serviceId = intval($_GET['id']);
    
    // Check if service has bookings
    $checkQuery = "SELECT COUNT(*) as count FROM bookings WHERE service_id = $serviceId";
    $checkResult = $db->query($checkQuery);
    $count = $checkResult->fetch_assoc()['count'];
    
    if ($count > 0) {
        setFlashMessage('error', 'Cannot delete this service because it has associated bookings');
    } else {
        $deleteQuery = "DELETE FROM services WHERE service_id = $serviceId AND provider_id = $providerId";
        if ($db->query($deleteQuery)) {
            setFlashMessage('success', 'Service deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete service');
        }
    }
    
    redirect(APP_URL . '/service_provider/services.php');
}

// Handle status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $serviceId = intval($_GET['id']);
    
    $statusQuery = "SELECT status FROM services WHERE service_id = $serviceId AND provider_id = $providerId";
    $statusResult = $db->query($statusQuery);
    
    if ($statusResult->num_rows > 0) {
        $service = $statusResult->fetch_assoc();
        $newStatus = ($service['status'] === 'active') ? 'inactive' : 'active';
        
        // More direct update with explicit values
        $updateQuery = "UPDATE services SET status = '$newStatus' WHERE service_id = $serviceId AND provider_id = $providerId";
        
        if ($db->query($updateQuery)) {
            // Verify the update worked
            $checkQuery = "SELECT status FROM services WHERE service_id = $serviceId";
            $checkResult = $db->query($checkQuery)->fetch_assoc();
            
            if ($checkResult['status'] === $newStatus) {
                setFlashMessage('success', "Service status updated to '$newStatus' successfully");
            } else {
                setFlashMessage('error', 'Service status update failed to save correctly');
            }
        } else {
            setFlashMessage('error', 'Failed to update service status: ' . $db->error);
        }
    }
    
    redirect(APP_URL . '/service_provider/services.php');
}

// Handle availability toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_availability' && isset($_GET['id'])) {
    $serviceId = intval($_GET['id']);
    
    $availabilityQuery = "SELECT is_available FROM services WHERE service_id = $serviceId AND provider_id = $providerId";
    $availabilityResult = $db->query($availabilityQuery);
    
    if ($availabilityResult->num_rows > 0) {
        $service = $availabilityResult->fetch_assoc();
        $newAvailability = $service['is_available'] ? 0 : 1;
        
        $updateQuery = "UPDATE services SET is_available = $newAvailability WHERE service_id = $serviceId AND provider_id = $providerId";
        if ($db->query($updateQuery)) {
            setFlashMessage('success', 'Service availability updated successfully');
        } else {
            setFlashMessage('error', 'Failed to update service availability');
        }
    }
    
    redirect(APP_URL . '/service_provider/services.php');
}

// Get all services for this provider
$servicesQuery = "
    SELECT s.*, c.name as category_name
    FROM services s
    JOIN service_categories c ON s.category_id = c.category_id
    WHERE s.provider_id = $providerId
    ORDER BY s.created_at DESC
";
$services = $db->query($servicesQuery)->fetch_all(MYSQLI_ASSOC);

// Get service categories for the form
$categoriesQuery = "SELECT * FROM service_categories ORDER BY name";
$categories = $db->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2>My Services</h2>
        <p class="text-muted">Manage the services you offer to pet owners</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">My Services</h6>
                <a href="<?php echo APP_URL; ?>/service_provider/add_service.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Add New Service
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($services)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-concierge-bell fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">You haven't added any services yet.</p>
                    <a href="<?php echo APP_URL; ?>/service_provider/add_service.php" class="btn btn-primary mt-3">Add Your First Service</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>Service</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['title']); ?></td>
                                <td><?php echo htmlspecialchars($service['category_name']); ?></td>
                                <td><?php echo substr(htmlspecialchars($service['description']), 0, 50) . '...'; ?></td>
                                <td><?php echo formatCurrency($service['price']); ?></td>
                                <td><?php echo $service['duration']; ?> mins</td>
                                <td>
                                    <span class="badge bg-<?php echo $service['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo APP_URL; ?>/service_provider/edit_service.php?id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/service_provider/services.php?action=toggle_status&id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-outline-<?php echo $service['status'] === 'active' ? 'warning' : 'success'; ?>">
                                            <i class="fas fa-<?php echo $service['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/service_provider/services.php?action=delete&id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this service? This cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>