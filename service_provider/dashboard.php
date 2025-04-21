<?php
$pageTitle = 'Service Provider Dashboard';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];

// Get service provider's services
$servicesQuery = "
    SELECT * FROM services
    WHERE provider_id = $providerId
";
$services = $db->query($servicesQuery)->fetch_all(MYSQLI_ASSOC);

// Get upcoming bookings
$bookingsQuery = "
    SELECT b.*, s.title AS service_title, p.name AS pet_name, p.type AS pet_type,
           u.first_name, u.last_name, u.email, u.phone
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN pets p ON b.pet_id = p.pet_id
    JOIN pet_owners po ON p.owner_id = po.owner_id
    JOIN users u ON po.user_id = u.user_id
    WHERE s.provider_id = $providerId AND b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC, b.start_time ASC
    LIMIT 5
";
$upcomingBookings = $db->query($bookingsQuery)->fetch_all(MYSQLI_ASSOC);

// Get pending payments
$paymentsQuery = "
    SELECT p.*, b.booking_id, b.booking_date, b.start_time, s.title AS service_title, 
           u.first_name, u.last_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    JOIN pets pet ON b.pet_id = pet.pet_id
    JOIN pet_owners po ON pet.owner_id = po.owner_id
    JOIN users u ON po.user_id = u.user_id
    WHERE s.provider_id = $providerId AND p.status = 'pending'
    ORDER BY b.booking_date ASC
";
$pendingPayments = $db->query($paymentsQuery)->fetch_all(MYSQLI_ASSOC);

// Get service statistics
$totalBookingsQuery = "
    SELECT COUNT(*) as count 
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE s.provider_id = $providerId
";
$totalBookings = $db->query($totalBookingsQuery)->fetch_assoc()['count'];

$totalRevenueQuery = "
    SELECT SUM(p.amount) as total
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    WHERE s.provider_id = $providerId AND p.status = 'completed'
";
$totalRevenue = $db->query($totalRevenueQuery)->fetch_assoc()['total'] ?? 0;
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
        <p class="text-muted">Manage your services and bookings from your dashboard.</p>
    </div>
</div>

<div class="row">
    <!-- Dashboard Summary Cards -->
    <div class="col-md-4 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            My Services</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($services); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-concierge-bell fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Bookings</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalBookings; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Total Revenue</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($totalRevenue); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- My Services Section -->
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">My Services</h6>
                <a href="<?php echo APP_URL; ?>/service_provider/services.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Service
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($services)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-concierge-bell fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">You haven't added any services yet.</p>
                    <a href="<?php echo APP_URL; ?>/service_provider/services.php" class="btn btn-primary mt-3">Add Your First Service</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['title']; ?></td>
                                <td><?php echo formatCurrency($service['price']); ?></td>
                                <td><?php echo $service['duration']; ?> mins</td>
                                <td>
                                    <?php if ($service['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
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

    <!-- Upcoming Bookings Section -->
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Upcoming Bookings</h6>
                <a href="<?php echo APP_URL; ?>/service_provider/bookings.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingBookings)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">You don't have any upcoming bookings.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingBookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['service_title']; ?></td>
                                <td>
                                    <div><?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></div>
                                    <small class="text-muted"><?php echo $booking['pet_name']; ?> (<?php echo $booking['pet_type']; ?>)</small>
                                </td>
                                <td>
                                    <?php echo formatDate($booking['booking_date']); ?><br>
                                    <small class="text-muted">
                                        <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($booking['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($booking['status'] == 'confirmed'): ?>
                                    <span class="badge bg-success">Confirmed</span>
                                    <?php elseif ($booking['status'] == 'completed'): ?>
                                    <span class="badge bg-info">Completed</span>
                                    <?php elseif ($booking['status'] == 'cancelled'): ?>
                                    <span class="badge bg-danger">Cancelled</span>
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

<!-- Pending Payments Section -->
<?php if (!empty($pendingPayments)): ?>
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-warning">Pending Payments</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                <td><?php echo $payment['service_title']; ?></td>
                                <td><?php echo formatDate($payment['booking_date']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>