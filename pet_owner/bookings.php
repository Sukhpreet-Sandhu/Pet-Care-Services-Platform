<?php
$pageTitle = 'My Bookings';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$petId = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;

// Build query
$query = "
    SELECT b.*, s.title AS service_title, s.price, sp.business_name, sp.provider_id, 
           p.name AS pet_name, p.type AS pet_type, p.pet_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    JOIN pets p ON b.pet_id = p.pet_id
    WHERE p.owner_id = $ownerId
";

// Add filters
if ($status !== 'all') {
    $query .= " AND b.status = '$status'";
}

if ($petId > 0) {
    $query .= " AND p.pet_id = $petId";
}

// Add sorting
$query .= " ORDER BY b.booking_date DESC, b.start_time DESC";

// Execute query
$bookings = $db->query($query)->fetch_all(MYSQLI_ASSOC);

// Get owner's pets for filter
$petsQuery = "SELECT pet_id, name FROM pets WHERE owner_id = $ownerId ORDER BY name";
$pets = $db->query($petsQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Bookings</h2>
    <a href="<?php echo APP_URL; ?>/services" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Book New Service
    </a>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="get" class="row g-3">
            <div class="col-md-5">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Bookings</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="pet_id" class="form-label">Pet</label>
                <select class="form-select" id="pet_id" name="pet_id">
                    <option value="0">All Pets</option>
                    <?php foreach ($pets as $pet): ?>
                    <option value="<?php echo $pet['pet_id']; ?>" <?php echo $petId === intval($pet['pet_id']) ? 'selected' : ''; ?>>
                        <?php echo $pet['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($bookings)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-calendar-alt fa-4x text-gray-300 mb-3"></i>
        <h4>No bookings found</h4>
        <p class="text-muted">You haven't booked any services yet or no bookings match your filter criteria.</p>
        <a href="<?php echo APP_URL; ?>/services" class="btn btn-primary mt-2">Browse Services</a>
    </div>
</div>
<?php else: ?>

<!-- Upcoming Bookings -->
<?php
$upcomingBookings = array_filter($bookings, function($booking) {
    return $booking['booking_date'] >= date('Y-m-d') && $booking['status'] != 'cancelled';
});
?>

<?php if (!empty($upcomingBookings)): ?>
<h4 class="mb-3">Upcoming Bookings</h4>
<div class="row">
    <?php foreach ($upcomingBookings as $booking): ?>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo $booking['service_title']; ?></h5>
                <span class="badge <?php 
                    if ($booking['status'] == 'pending') echo 'bg-warning';
                    elseif ($booking['status'] == 'confirmed') echo 'bg-success';
                    elseif ($booking['status'] == 'completed') echo 'bg-info';
                    elseif ($booking['status'] == 'cancelled') echo 'bg-danger';
                ?>">
                    <?php echo ucfirst($booking['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($booking['booking_date']); ?></p>
                    <p class="mb-1"><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></p>
                    <p class="mb-1"><strong>Provider:</strong> <a href="<?php echo APP_URL; ?>/service_provider/profile_public.php?id=<?php echo $booking['provider_id']; ?>"><?php echo $booking['business_name']; ?></a></p>
                    <p class="mb-0"><strong>Pet:</strong> <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $booking['pet_id']; ?>"><?php echo $booking['pet_name']; ?></a> (<?php echo $booking['pet_type']; ?>)</p>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><?php echo formatCurrency($booking['price']); ?></span>
                    <a href="<?php echo APP_URL; ?>/pet_owner/booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline-primary">View Details</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Past Bookings -->
<?php
$pastBookings = array_filter($bookings, function($booking) {
    return $booking['booking_date'] < date('Y-m-d') || $booking['status'] == 'cancelled' || $booking['status'] == 'completed';
});
?>

<?php if (!empty($pastBookings)): ?>
<h4 class="mb-3">Past Bookings</h4>
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Service</th>
                <th>Pet</th>
                <th>Date & Time</th>
                <th>Provider</th>
                <th>Status</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pastBookings as $booking): ?>
            <tr>
                <td><?php echo $booking['service_title']; ?></td>
                <td>
                    <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $booking['pet_id']; ?>">
                        <?php echo $booking['pet_name']; ?>
                    </a>
                </td>
                <td>
                    <?php echo formatDate($booking['booking_date']); ?><br>
                    <small class="text-muted">
                        <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                        <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                    </small>
                </td>
                <td>
                    <a href="<?php echo APP_URL; ?>/service_provider/profile_public.php?id=<?php echo $booking['provider_id']; ?>">
                        <?php echo $booking['business_name']; ?>
                    </a>
                </td>
                <td>
                    <span class="badge <?php 
                        if ($booking['status'] == 'pending') echo 'bg-warning';
                        elseif ($booking['status'] == 'confirmed') echo 'bg-success';
                        elseif ($booking['status'] == 'completed') echo 'bg-info';
                        elseif ($booking['status'] == 'cancelled') echo 'bg-danger';
                    ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </td>
                <td><?php echo formatCurrency($booking['price']); ?></td>
                <td>
                    <a href="<?php echo APP_URL; ?>/pet_owner/booking_details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>