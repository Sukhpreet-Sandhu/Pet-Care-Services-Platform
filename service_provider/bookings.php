<?php
$pageTitle = 'Manage Bookings';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];

// Handle booking status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $bookingId = intval($_GET['id']);
    $action = $_GET['action'];
    
    // First, verify this booking belongs to this provider
    $checkQuery = "
        SELECT b.* FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        WHERE b.booking_id = $bookingId AND s.provider_id = $providerId
    ";
    $checkResult = $db->query($checkQuery);
    
    if ($checkResult->num_rows > 0) {
        // Check if notifications table exists with required columns
        $checkColumnQuery = "SHOW COLUMNS FROM notifications LIKE 'user_id'";
        try {
            $columnResult = $db->query($checkColumnQuery);
            $hasUserIdColumn = $columnResult->num_rows > 0;
        } catch (Exception $e) {
            // Table doesn't exist or other error
            $hasUserIdColumn = false;
        }

        if ($action === 'confirm') {
            $updateQuery = "UPDATE bookings SET status = 'confirmed' WHERE booking_id = $bookingId";
            if ($db->query($updateQuery)) {
                // Only send notification if the table and column exist
                if ($hasUserIdColumn) {
                    try {
                        $notifyQuery = "
                            INSERT INTO notifications (user_id, title, message, type)
                            SELECT po.user_id, 'Booking Confirmed', CONCAT('Your booking for ', s.title, ' on ', b.booking_date, ' has been confirmed.'), 'booking_confirmed'
                            FROM bookings b
                            JOIN services s ON b.service_id = s.service_id
                            JOIN pets p ON b.pet_id = p.pet_id
                            JOIN pet_owners po ON p.owner_id = po.owner_id
                            WHERE b.booking_id = $bookingId
                        ";
                        $db->query($notifyQuery);
                    } catch (Exception $e) {
                        // Log error but don't stop the flow
                        error_log('Failed to create notification: ' . $e->getMessage());
                    }
                }
                
                setFlashMessage('success', 'Booking confirmed successfully');
            } else {
                setFlashMessage('error', 'Failed to confirm booking');
            }
        } elseif ($action === 'complete') {
            $updateQuery = "UPDATE bookings SET status = 'completed' WHERE booking_id = $bookingId";
            if ($db->query($updateQuery)) {
                // Only send notification if the table exists
                if ($hasUserIdColumn) {
                    $notifyQuery = "
                        INSERT INTO notifications (user_id, title, message, type)
                        SELECT po.user_id, 'Service Completed', CONCAT('Your booking for ', s.title, ' has been marked as completed.'), 'booking_completed'
                        FROM bookings b
                        JOIN services s ON b.service_id = s.service_id
                        JOIN pets p ON b.pet_id = p.pet_id
                        JOIN pet_owners po ON p.owner_id = po.owner_id
                        JOIN users u ON po.user_id = u.user_id
                        WHERE b.booking_id = $bookingId
                    ";
                    $db->query($notifyQuery);
                }
                
                setFlashMessage('success', 'Booking marked as completed');
            } else {
                setFlashMessage('error', 'Failed to update booking');
            }
        } elseif ($action === 'cancel') {
            $updateQuery = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = $bookingId";
            if ($db->query($updateQuery)) {
                // Only send notification if the table exists
                if ($hasUserIdColumn) {
                    $notifyQuery = "
                        INSERT INTO notifications (user_id, title, message, type)
                        SELECT po.user_id, 'Booking Cancelled', CONCAT('Your booking for ', s.title, ' on ', b.booking_date, ' has been cancelled by the provider.'), 'booking_cancelled'
                        FROM bookings b
                        JOIN services s ON b.service_id = s.service_id
                        JOIN pets p ON b.pet_id = p.pet_id
                        JOIN pet_owners po ON p.owner_id = po.owner_id
                        JOIN users u ON po.user_id = u.user_id
                        WHERE b.booking_id = $bookingId
                    ";
                    $db->query($notifyQuery);
                }
                
                setFlashMessage('success', 'Booking cancelled successfully');
            } else {
                setFlashMessage('error', 'Failed to cancel booking');
            }
        }
    } else {
        setFlashMessage('error', 'Invalid booking or permission denied');
    }
    
    redirect(APP_URL . '/service_provider/bookings.php');
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Build base query
$query = "
    SELECT b.*, s.title AS service_title, p.name AS pet_name, p.type AS pet_type,
           u.first_name, u.last_name, u.email, u.phone
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN pets p ON b.pet_id = p.pet_id
    JOIN pet_owners po ON p.owner_id = po.owner_id
    JOIN users u ON po.user_id = u.user_id
    WHERE s.provider_id = $providerId
";

// Add filters
if (!empty($status)) {
    $query .= " AND b.status = '$status'";
}

if (!empty($fromDate)) {
    $query .= " AND b.booking_date >= '$fromDate'";
}

if (!empty($toDate)) {
    $query .= " AND b.booking_date <= '$toDate'";
}

if ($serviceId > 0) {
    $query .= " AND b.service_id = $serviceId";
}

// Add ordering
$query .= " ORDER BY b.booking_date DESC, b.start_time DESC";

// Execute query
$result = $db->query($query);
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Get services for filter dropdown
$servicesQuery = "SELECT service_id, title FROM services WHERE provider_id = $providerId ORDER BY title";
$services = $db->query($servicesQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2>Manage Bookings</h2>
        <p class="text-muted">View and manage your service bookings</p>
    </div>
</div>

<!-- Filters -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filter Bookings</h6>
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $fromDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $toDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="service_id" class="form-label">Service</label>
                        <select class="form-select" id="service_id" name="service_id">
                            <option value="0">All Services</option>
                            <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['service_id']; ?>" <?php echo $serviceId === intval($service['service_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($service['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?php echo APP_URL; ?>/service_provider/bookings.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bookings Table -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Bookings</h6>
            </div>
            <div class="card-body">
                <?php if (empty($bookings)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">No bookings found matching your criteria</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Service</th>
                                <th>Customer</th>
                                <th>Pet</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['pet_name']); ?> 
                                    <span class="badge bg-info"><?php echo htmlspecialchars($booking['pet_type']); ?></span>
                                </td>
                                <td>
                                    <?php echo formatDate($booking['booking_date']); ?><br>
                                    <small class="text-muted">
                                        <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] === 'pending' ? 'warning' : 
                                            ($booking['status'] === 'confirmed' ? 'success' : 
                                                ($booking['status'] === 'completed' ? 'info' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($booking['total_price'] ?? 0, 2); ?></td>
                                <td>
                                    <!-- Actions -->
                                    <?php if ($booking['status'] === 'pending'): ?>
                                    <a href="?action=confirm&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Confirm this booking?');">Confirm</a>
                                    <a href="?action=cancel&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                                    <?php elseif ($booking['status'] === 'confirmed'): ?>
                                    <a href="?action=complete&id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-info" onclick="return confirm('Mark this booking as completed?');">Complete</a>
                                    <?php endif; ?>
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