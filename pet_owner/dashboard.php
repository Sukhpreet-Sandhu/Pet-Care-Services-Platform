<?php
$pageTitle = 'Dashboard';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Verify owner_id exists in session
if (!isset($_SESSION['owner_id'])) {
    setFlashMessage('error', 'Owner profile not found. Please contact support.');
    redirect(APP_URL . '/logout.php');
    exit;
}

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Initialize variables to prevent undefined errors
$pets = [];
$upcomingBookings = [];
$pendingPayments = [];
$educationalContent = [];

try {
    // Get owner's pets - using prepared statement
    $petsQuery = "
        SELECT * FROM pets
        WHERE owner_id = ?
    ";
    $stmt = $db->prepare($petsQuery);
    $stmt->bind_param("i", $ownerId);
    $stmt->execute();
    $petsResult = $stmt->get_result();
    $pets = $petsResult->fetch_all(MYSQLI_ASSOC);

    // Get upcoming bookings - using prepared statement
    $bookingsQuery = "
        SELECT b.*, s.title AS service_title, s.price, sp.business_name, p.name AS pet_name, p.type AS pet_type
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        JOIN pets p ON b.pet_id = p.pet_id
        WHERE p.owner_id = ? AND b.booking_date >= CURDATE()
        ORDER BY b.booking_date ASC, b.start_time ASC
        LIMIT 5
    ";
    $stmt = $db->prepare($bookingsQuery);
    $stmt->bind_param("i", $ownerId);
    $stmt->execute();
    $bookingsResult = $stmt->get_result();
    $upcomingBookings = $bookingsResult->fetch_all(MYSQLI_ASSOC);

    // Get pending payments - using prepared statement
    $paymentsQuery = "
        SELECT p.*, b.booking_id, b.booking_date, b.start_time, s.title AS service_title
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        JOIN services s ON b.service_id = s.service_id
        JOIN pets pet ON b.pet_id = pet.pet_id
        WHERE pet.owner_id = ? AND p.status = 'pending'
        ORDER BY b.booking_date ASC
    ";
    $stmt = $db->prepare($paymentsQuery);
    $stmt->bind_param("i", $ownerId);
    $stmt->execute();
    $paymentsResult = $stmt->get_result();
    $pendingPayments = $paymentsResult->fetch_all(MYSQLI_ASSOC);

    // Get recent educational content
    $educationalContentQuery = "
        SELECT ec.content_id, ec.title, ec.category, ec.image, ec.created_at
        FROM educational_content ec
        ORDER BY ec.created_at DESC
        LIMIT 3
    ";
    $educationalContent = $db->query($educationalContentQuery)->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log('Dashboard error: ' . $e->getMessage());
    setFlashMessage('error', 'There was a problem loading your dashboard. Please try again later.');
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
        <p class="text-muted">Manage your pets and bookings from your dashboard.</p>
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
                            My Pets</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($pets); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-paw fa-2x text-gray-300"></i>
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
                            Upcoming Bookings</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($upcomingBookings); ?></div>
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
                            Pending Payments</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($pendingPayments); ?></div>
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
    <!-- My Pets Section -->
    <div class="col-md-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">My Pets</h6>
                <a href="<?php echo APP_URL; ?>/pet_owner/pets.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Pet
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($pets)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-paw fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">You haven't added any pets yet.</p>
                    <a href="<?php echo APP_URL; ?>/pet_owner/pets.php" class="btn btn-primary mt-3">Add Your First Pet</a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($pets as $pet): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <?php if (!empty($pet['image'])): ?>
                            <img src="<?php echo UPLOAD_URL . $pet['image']; ?>" class="card-img-top" alt="<?php echo $pet['name']; ?>" style="height: 150px; object-fit: cover;">
                            <?php else: ?>
                            <img src="<?php echo APP_URL; ?>/assets/images/pet-placeholder.jpg" class="card-img-top" alt="<?php echo $pet['name']; ?>" style="height: 150px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $pet['name']; ?></h5>
                                <p class="card-text">
                                    <span class="badge bg-primary"><?php echo $pet['type']; ?></span>
                                    <?php if (!empty($pet['breed'])): ?>
                                    <span class="badge bg-secondary"><?php echo $pet['breed']; ?></span>
                                    <?php endif; ?>
                                </p>
                                <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                <a href="<?php echo APP_URL; ?>/pet_owner/bookings.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingBookings)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">You don't have any upcoming bookings.</p>
                    <a href="<?php echo APP_URL; ?>/services" class="btn btn-primary mt-3">Browse Services</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingBookings as $booking): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $booking['service_title']; ?></div>
                                    <small class="text-muted">For <?php echo $booking['pet_name']; ?></small>
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
                                <td>
                                    <a href="<?php echo APP_URL; ?>/pet_owner/booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
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
                                <th>Service</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['service_title']; ?></td>
                                <td><?php echo formatDate($payment['booking_date']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/pet_owner/make_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-warning">Pay Now</a>
                                </td>
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

<!-- Pet Care Tips Section -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Pet Care Tips</h6>
                <a href="<?php echo APP_URL; ?>/educational" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($educationalContent)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-book-reader fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">No educational content available at the moment.</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($educationalContent as $content): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <?php if (!empty($content['image'])): ?>
                            <img src="<?php echo UPLOAD_URL . $content['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($content['title']); ?>" style="height: 150px; object-fit: cover;">
                            <?php else: ?>
                            <img src="<?php echo APP_URL; ?>/assets/images/pet-care-tips.jpg" class="card-img-top" alt="Pet Care Tips" style="height: 150px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($content['category']); ?></span>
                                <h5 class="card-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                                <p class="card-text small text-muted"><?php echo formatDate($content['created_at']); ?></p>
                                <a href="<?php echo APP_URL; ?>/educational/article.php?id=<?php echo $content['content_id']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>