<?php
$pageTitle = 'Service Details';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Get service ID
$serviceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($serviceId <= 0) {
    setFlashMessage('error', 'Invalid service ID');
    redirect(APP_URL . '/services');
}

// Get service details
$query = "
    SELECT s.*, sp.provider_id, sp.business_name, sp.description AS provider_description, 
           sp.avg_rating, u.first_name, u.last_name, u.email, u.phone,
           sc.name AS category_name
    FROM services s
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    JOIN users u ON sp.user_id = u.user_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    WHERE s.service_id = $serviceId AND s.is_available = 1
";

$result = $db->query($query);

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Service not found');
    redirect(APP_URL . '/services');
}

$service = $result->fetch_assoc();

// Get provider availability
$availabilityQuery = "
    SELECT * FROM provider_availability
    WHERE provider_id = {$service['provider_id']}
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
";
$availability = $db->query($availabilityQuery)->fetch_all(MYSQLI_ASSOC);

// Get reviews for this service provider
$reviewsQuery = "
    SELECT r.rating, r.comment, r.created_at, u.first_name, u.last_name
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    JOIN pets p ON b.pet_id = p.pet_id
    JOIN pet_owners po ON p.owner_id = po.owner_id
    JOIN users u ON po.user_id = u.user_id
    WHERE s.provider_id = {$service['provider_id']}
    ORDER BY r.created_at DESC
    LIMIT 5
";
$reviews = $db->query($reviewsQuery)->fetch_all(MYSQLI_ASSOC);

// Get user's pets if logged in as pet owner
$userPets = [];
if (isLoggedIn() && getUserType() === 'pet_owner') {
    $petsQuery = "
        SELECT p.*
        FROM pets p
        JOIN pet_owners po ON p.owner_id = po.owner_id
        WHERE po.user_id = {$_SESSION['user_id']}
    ";
    $userPets = $db->query($petsQuery)->fetch_all(MYSQLI_ASSOC);
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_service'])) {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please log in to book a service');
        redirect(APP_URL . '/login.php');
    }
    
    if (getUserType() !== 'pet_owner') {
        setFlashMessage('error', 'Only pet owners can book services');
        redirect(APP_URL . '/services/details.php?id=' . $serviceId);
    }
    
    $petId = intval($_POST['pet_id'] ?? 0);
    $bookingDate = $_POST['booking_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if ($petId <= 0) {
        $errors[] = 'Please select a pet';
    }
    
    if (empty($bookingDate)) {
        $errors[] = 'Please select a booking date';
    }
    
    if (empty($startTime)) {
        $errors[] = 'Please select a start time';
    }
    
    // Calculate end time based on service duration
    $startDateTime = new DateTime($bookingDate . ' ' . $startTime);
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('PT' . $service['duration'] . 'M'));
    $endTime = $endDateTime->format('H:i:s');
    
    // If no errors, create booking
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO bookings (service_id, pet_id, booking_date, start_time, end_time, total_price, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisssds", $serviceId, $petId, $bookingDate, $startTime, $endTime, $service['price'], $notes);
        
        if ($stmt->execute()) {
            $bookingId = $db->getLastInsertId();
            
            // Create payment record
            $stmt = $db->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, status)
                VALUES (?, ?, 'pending', 'pending')
            ");
            $stmt->bind_param("id", $bookingId, $service['price']);
            $stmt->execute();
            
            setFlashMessage('success', 'Service booked successfully! Please complete the payment to confirm your booking.');
            redirect(APP_URL . '/pet_owner/bookings.php');
        } else {
            setFlashMessage('error', 'Failed to book service. Please try again.');
        }
    } else {
        $errorMessage = implode('<br>', $errors);
        setFlashMessage('error', $errorMessage);
    }
}
?>

<div class="row">
    <!-- Service Details -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title"><?php echo $service['title']; ?></h2>
                <span class="badge bg-primary mb-3"><?php echo $service['category_name']; ?></span>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="text-primary mb-0"><?php echo formatCurrency($service['price']); ?></h4>
                        <small class="text-muted">Duration: <?php echo $service['duration']; ?> minutes</small>
                    </div>
                    <div class="service-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= round($service['avg_rating'])): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span class="ms-1">(<?php echo round($service['avg_rating'], 1); ?>)</span>
                    </div>
                </div>
                
                <h5>Description</h5>
                <p><?php echo nl2br($service['description']); ?></p>
                
                <hr class="my-4">
                
                <h5>Service Provider</h5>
                <div class="d-flex align-items-center mb-3">
                    <img src="<?php echo APP_URL; ?>/assets/images/provider-placeholder.jpg" alt="<?php echo $service['business_name']; ?>" class="rounded-circle me-3" width="60">
                    <div>
                        <h5 class="mb-0"><?php echo $service['business_name']; ?></h5>
                        <p class="text-muted mb-0"><?php echo $service['first_name'] . ' ' . $service['last_name']; ?></p>
                    </div>
                </div>
                <p>
                    <?php 
                    $providerDescription = $service['provider_description'] ?? '';
                    echo strlen($providerDescription) > 0 
                        ? substr($providerDescription, 0, 200) . '...' 
                        : 'No description available.'; 
                    ?>
                </p>
                
                <hr class="my-4">
                
                <h5>Availability</h5>
                <div class="row">
                    <?php if (empty($availability)): ?>
                    <div class="col-12">
                        <p class="text-muted">No availability information provided.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($availability as $slot): ?>
                    <div class="col-md-6 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold"><?php echo $slot['day_of_week']; ?></span>
                            <span><?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <hr class="my-4">
                
                <h5>Reviews</h5>
                <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews yet.</p>
                <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><?php echo $review['first_name'] . ' ' . $review['last_name']; ?></h6>
                            <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                        </div>
                        <div class="service-rating mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $review['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-0"><?php echo nl2br($review['comment']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Booking Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Book This Service</h5>
            </div>
            <div class="card-body">
                <?php if (!isLoggedIn()): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Please <a href="<?php echo APP_URL; ?>/login.php">log in</a> to book this service.
                </div>
                <?php elseif (getUserType() !== 'pet_owner'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Only pet owners can book services.
                </div>
                <?php elseif (empty($userPets)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>You need to <a href="<?php echo APP_URL; ?>/pet_owner/pets.php">add a pet</a> before booking a service.
                </div>
                <?php else: ?>
                <form method="post" action="">
                    <input type="hidden" name="book_service" value="1">
                    
                    <div class="mb-3">
                        <label for="pet_id" class="form-label">Select Pet</label>
                        <select class="form-select" id="pet_id" name="pet_id" required>
                            <option value="">-- Select Pet --</option>
                            <?php foreach ($userPets as $pet): ?>
                            <option value="<?php echo $pet['pet_id']; ?>"><?php echo $pet['name']; ?> (<?php echo $pet['type']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="booking_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Time</label>
                        <select class="form-select" id="start_time" name="start_time" required>
                            <option value="">-- Select Time --</option>
                            <?php
                            // Generate time slots from 8 AM to 6 PM
                            $start = strtotime('8:00');
                            $end = strtotime('18:00');
                            $interval = 30 * 60; // 30 minutes in seconds
                            
                            for ($time = $start; $time <= $end; $time += $interval) {
                                echo '<option value="' . date('H:i:s', $time) . '">' . date('g:i A', $time) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Special Instructions (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Service Fee:</span>
                            <span><?php echo formatCurrency($service['price']); ?></span>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Book Now</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>