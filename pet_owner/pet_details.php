<?php
$pageTitle = 'Pet Details';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Get pet ID
$petId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($petId <= 0) {
    setFlashMessage('error', 'Invalid pet ID');
    redirect(APP_URL . '/pet_owner/pets.php');
}

// Get pet details
$petQuery = "
    SELECT * FROM pets
    WHERE pet_id = $petId AND owner_id = $ownerId
";
$petResult = $db->query($petQuery);

if ($petResult->num_rows === 0) {
    setFlashMessage('error', 'Pet not found or you do not have permission to view this pet');
    redirect(APP_URL . '/pet_owner/pets.php');
}

$pet = $petResult->fetch_assoc();

// Get pet's booking history
$bookingsQuery = "
    SELECT b.*, s.title AS service_title, s.price, sp.business_name, sp.provider_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    WHERE b.pet_id = $petId
    ORDER BY b.booking_date DESC, b.start_time DESC
";
$bookings = $db->query($bookingsQuery)->fetch_all(MYSQLI_ASSOC);

// Get upcoming bookings
$upcomingBookingsQuery = "
    SELECT b.*, s.title AS service_title, s.price, sp.business_name, sp.provider_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    WHERE b.pet_id = $petId AND b.booking_date >= CURDATE() AND b.status != 'cancelled'
    ORDER BY b.booking_date ASC, b.start_time ASC
";
$upcomingBookings = $db->query($upcomingBookingsQuery)->fetch_all(MYSQLI_ASSOC);

// Get past bookings
$pastBookingsQuery = "
    SELECT b.*, s.title AS service_title, s.price, sp.business_name, sp.provider_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    WHERE b.pet_id = $petId AND (b.booking_date < CURDATE() OR b.status = 'completed')
    ORDER BY b.booking_date DESC, b.start_time DESC
    LIMIT 10
";
$pastBookings = $db->query($pastBookingsQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $pet['name']; ?>'s Profile</h2>
    <div>
        <a href="<?php echo APP_URL; ?>/pet_owner/edit_pet.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <a href="<?php echo APP_URL; ?>/pet_owner/pets.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Pets
        </a>
    </div>
</div>

<div class="row">
    <!-- Pet Details -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-center mb-4">
                    <?php if (!empty($pet['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . $pet['image']; ?>" alt="<?php echo $pet['name']; ?>" class="img-fluid rounded" style="max-height: 250px;">
                    <?php else: ?>
                    <img src="<?php echo APP_URL; ?>/assets/images/pet-placeholder.jpg" alt="<?php echo $pet['name']; ?>" class="img-fluid rounded" style="max-height: 250px;">
                    <?php endif; ?>
                </div>
                
                <h4 class="card-title text-center mb-4"><?php echo $pet['name']; ?></h4>
                
                <div class="mb-3 text-center">
                    <span class="badge bg-primary"><?php echo $pet['type']; ?></span>
                    <?php if (!empty($pet['breed'])): ?>
                    <span class="badge bg-secondary"><?php echo $pet['breed']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="pet-details">
                    <?php if (!empty($pet['age'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Age:</span>
                        <span><?php echo $pet['age']; ?> years</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($pet['weight'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Weight:</span>
                        <span><?php echo $pet['weight']; ?> kg</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($pet['special_needs'])): ?>
                    <div class="mb-3">
                        <p class="fw-bold mb-1">Special Needs:</p>
                        <p class="mb-0"><?php echo nl2br($pet['special_needs']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($pet['medical_conditions'])): ?>
                    <div class="mb-3">
                        <p class="fw-bold mb-1">Medical Conditions:</p>
                        <p class="mb-0"><?php echo nl2br($pet['medical_conditions']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Bookings -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingBookings)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0"><?php echo $pet['name']; ?> doesn't have any upcoming appointments.</p>
                    <a href="<?php echo APP_URL; ?>/services" class="btn btn-primary mt-3">Book a Service</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Provider</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingBookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['service_title']; ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/service_provider/profile_public.php?id=<?php echo $booking['provider_id']; ?>">
                                        <?php echo $booking['business_name']; ?>
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
        
        <!-- Past Bookings -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Service History</h5>
                <?php if (count($pastBookings) > 0): ?>
                <a href="<?php echo APP_URL; ?>/pet_owner/booking_history.php?pet_id=<?php echo $pet['pet_id']; ?>" class="btn btn-sm btn-outline-primary">View All</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($pastBookings)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-history fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0"><?php echo $pet['name']; ?> doesn't have any service history yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Provider</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastBookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['service_title']; ?></td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/service_provider/profile_public.php?id=<?php echo $booking['provider_id']; ?>">
                                        <?php echo $booking['business_name']; ?>
                                    </a>
                                </td>
                                <td><?php echo formatDate($booking['booking_date']); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'completed'): ?>
                                    <span class="badge bg-info">Completed</span>
                                    <?php elseif ($booking['status'] == 'cancelled'): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo ucfirst($booking['status']); ?></span>
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

<?php require_once '../includes/footer.php'; ?>