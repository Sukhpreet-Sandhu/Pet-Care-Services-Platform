<?php
$pageTitle = 'Booking Details';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Get booking ID
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookingId <= 0) {
    setFlashMessage('error', 'Invalid booking ID');
    redirect(APP_URL . '/pet_owner/bookings.php');
}

// Get booking details
$bookingQuery = "
    SELECT b.*, s.title AS service_title, s.description AS service_description, s.price, s.duration,
           sp.provider_id, sp.business_name, sp.description AS provider_description, sp.avg_rating,
           p.pet_id, p.name AS pet_name, p.type AS pet_type, p.breed, p.image AS pet_image,
           u.first_name, u.last_name, u.email, u.phone
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    JOIN users u ON sp.user_id = u.user_id
    JOIN pets p ON b.pet_id = p.pet_id
    WHERE b.booking_id = $bookingId AND p.owner_id = $ownerId
";

$bookingResult = $db->query($bookingQuery);

if ($bookingResult->num_rows === 0) {
    setFlashMessage('error', 'Booking not found or you do not have permission to view this booking');
    redirect(APP_URL . '/pet_owner/bookings.php');
}

$booking = $bookingResult->fetch_assoc();

// Get payment details
$paymentQuery = "
    SELECT * FROM payments
    WHERE booking_id = $bookingId
";
$paymentResult = $db->query($paymentQuery);
$payment = $paymentResult->num_rows > 0 ? $paymentResult->fetch_assoc() : null;

// Get review if exists
$reviewQuery = "
    SELECT * FROM reviews
    WHERE booking_id = $bookingId
";
$reviewResult = $db->query($reviewQuery);
$review = $reviewResult->num_rows > 0 ? $reviewResult->fetch_assoc() : null;

// Handle cancel booking
if (isset($_POST['cancel_booking']) && $booking['status'] !== 'cancelled' && $booking['status'] !== 'completed') {
    $cancelQuery = "
        UPDATE bookings
        SET status = 'cancelled'
        WHERE booking_id = $bookingId
    ";
    
    if ($db->query($cancelQuery)) {
        // Update payment status if exists
        if ($payment) {
            $updatePaymentQuery = "
                UPDATE payments
                SET status = 'cancelled'
                WHERE payment_id = {$payment['payment_id']}
            ";
            $db->query($updatePaymentQuery);
        }
        
        setFlashMessage('success', 'Booking cancelled successfully');
        redirect(APP_URL . '/pet_owner/booking_details.php?id=' . $bookingId);
    } else {
        setFlashMessage('error', 'Failed to cancel booking');
    }
}

// Handle review submission
if (isset($_POST['submit_review']) && $booking['status'] === 'completed' && !$review) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    // Validate input
    $errors = [];
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5';
    }
    
    if (empty($comment)) {
        $errors[] = 'Please provide a comment for your review';
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO reviews (booking_id, rating, comment, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("iis", $bookingId, $rating, $comment);
        
        if ($stmt->execute()) {
            // Update provider's average rating
            $updateRatingQuery = "
                UPDATE service_providers sp
                SET avg_rating = (
                    SELECT AVG(r.rating)
                    FROM reviews r
                    JOIN bookings b ON r.booking_id = b.booking_id
                    JOIN services s ON b.service_id = s.service_id
                    WHERE s.provider_id = {$booking['provider_id']}
                )
                WHERE provider_id = {$booking['provider_id']}
            ";
            $db->query($updateRatingQuery);
            
            setFlashMessage('success', 'Review submitted successfully');
            redirect(APP_URL . '/pet_owner/booking_details.php?id=' . $bookingId);
        } else {
            setFlashMessage('error', 'Failed to submit review');
        }
    } else {
        $errorMessage = implode('<br>', $errors);
        setFlashMessage('error', $errorMessage);
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Booking Details</h2>
    <a href="<?php echo APP_URL; ?>/pet_owner/bookings.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Bookings
    </a>
</div>

<div class="row">
    <!-- Booking Details -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Booking Information</h5>
                <span class="badge <?php 
                    if ($booking['status'] == 'pending') echo 'bg-warning';
                    elseif ($booking['status'] == 'confirmed') echo 'bg-success';
                    elseif ($booking['status'] == 'completed') echo 'bg-info';
                    elseif ($booking['status'] == 'cancelled') echo 'bg-danger';
                ?> fs-6">
                    <?php echo ucfirst($booking['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="mb-3">Service Details</h5>
                        <p class="mb-1"><strong>Service:</strong> <?php echo $booking['service_title']; ?></p>
                        <p class="mb-1"><strong>Price:</strong> <?php echo formatCurrency($booking['price']); ?></p>
                        <p class="mb-1"><strong>Duration:</strong> <?php echo $booking['duration']; ?> minutes</p>
                        <p class="mb-3"><strong>Description:</strong> <?php echo $booking['service_description']; ?></p>
                        
                        <h5 class="mb-3">Booking Details</h5>
                        <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($booking['booking_date']); ?></p>
                        <p class="mb-1"><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></p>
                        <p class="mb-1"><strong>Booking ID:</strong> #<?php echo $booking['booking_id']; ?></p>
                        <p class="mb-1"><strong>Booked On:</strong> <?php echo formatDate($booking['created_at']); ?></p>
                        
                        <?php if (!empty($booking['notes'])): ?>
                        <p class="mb-1"><strong>Special Instructions:</strong> <?php echo nl2br($booking['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="mb-3">Service Provider</h5>
                        <p class="mb-1"><strong>Business:</strong> <a href="<?php echo APP_URL; ?>/service_provider/profile_public.php?id=<?php echo $booking['provider_id']; ?>"><?php echo $booking['business_name']; ?></a></p>
                        <p class="mb-1"><strong>Contact Person:</strong> <?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo $booking['email']; ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?php echo $booking['phone']; ?></p>
                        <div class="mb-3">
                            <strong>Rating:</strong>
                            <div class="service-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= round($booking['avg_rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="ms-1">(<?php echo round($booking['avg_rating'], 1); ?>)</span>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">Pet Information</h5>
                        <div class="d-flex align-items-center mb-3">
                            <?php if (!empty($booking['pet_image'])): ?>
                            <img src="<?php echo UPLOAD_URL . $booking['pet_image']; ?>" alt="<?php echo $booking['pet_name']; ?>" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                            <?php else: ?>
                            <img src="<?php echo APP_URL; ?>/assets/images/pet-placeholder.jpg" alt="<?php echo $booking['pet_name']; ?>" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-0"><?php echo $booking['pet_name']; ?></h6>
                                <p class="text-muted mb-0">
                                    <?php echo $booking['pet_type']; ?>
                                    <?php if (!empty($booking['breed'])): ?>
                                    (<?php echo $booking['breed']; ?>)
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $booking['pet_id']; ?>" class="btn btn-sm btn-outline-primary">View Pet Profile</a>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <button type="submit" name="cancel_booking" class="btn btn-danger">Cancel Booking</button>
                    </form>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] === 'pending' && $payment && $payment['status'] === 'pending'): ?>
                    <a href="<?php echo APP_URL; ?>/pet_owner/make_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-warning">
                        <i class="fas fa-credit-card me-1"></i> Complete Payment
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Payment Information -->
        <?php if ($payment): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Payment ID:</strong> #<?php echo $payment['payment_id']; ?></p>
                        <p class="mb-1"><strong>Amount:</strong> <?php echo formatCurrency($payment['amount']); ?></p>
                        <p class="mb-1"><strong>Status:</strong> 
                            <span class="badge <?php 
                                if ($payment['status'] == 'pending') echo 'bg-warning';
                                elseif ($payment['status'] == 'completed') echo 'bg-success';
                                elseif ($payment['status'] == 'cancelled') echo 'bg-danger';
                            ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst($payment['payment_method']); ?></p>
                        <?php if ($payment['status'] === 'completed'): ?>
                        <p class="mb-1"><strong>Transaction ID:</strong> <?php echo $payment['transaction_id']; ?></p>
                        <p class="mb-1"><strong>Paid On:</strong> <?php echo formatDate($payment['payment_date']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($payment['status'] === 'pending'): ?>
                <div class="mt-3">
                    <a href="<?php echo APP_URL; ?>/pet_owner/make_payment.php?id=<?php echo $payment['payment_id']; ?>" class="btn btn-warning">
                        <i class="fas fa-credit-card me-1"></i> Complete Payment
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Review Section -->
        <?php if ($booking['status'] === 'completed'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Review</h5>
            </div>
            <div class="card-body">
                <?php if ($review): ?>
                <div class="mb-3">
                    <h6>Your Rating</h6>
                    <div class="service-rating mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $review['rating']): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <p class="mb-1"><strong>Your Review:</strong></p>
                    <p class="mb-1"><?php echo nl2br($review['comment']); ?></p>
                    <p class="text-muted small">Submitted on <?php echo formatDate($review['created_at']); ?></p>
                </div>
                <?php else: ?>
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="rating" class="form-label">Rating</label>
                        <div class="rating-input">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating1" value="1" required>
                                <label class="form-check-label" for="rating1">1</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating2" value="2">
                                <label class="form-check-label" for="rating2">2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating3" value="3">
                                <label class="form-check-label" for="rating3">3</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating4" value="4">
                                <label class="form-check-label" for="rating4">4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating5" value="5">
                                <label class="form-check-label" for="rating5">5</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="comment" class="form-label">Your Review</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Booking Status -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Booking Status</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-calendar-check me-2 <?php echo $booking['status'] != 'cancelled' ? 'text-success' : 'text-muted'; ?>"></i>
                            Booking Placed
                        </div>
                        <small><?php echo formatDate($booking['created_at']); ?></small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-credit-card me-2 <?php echo ($payment && $payment['status'] == 'completed') ? 'text-success' : 'text-muted'; ?>"></i>
                            Payment
                        </div>
                        <?php if ($payment && $payment['status'] == 'completed'): ?>
                        <small><?php echo formatDate($payment['payment_date']); ?></small>
                        <?php elseif ($payment && $payment['status'] == 'pending'): ?>
                        <span class="badge bg-warning">Pending</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Not Started</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle me-2 <?php echo $booking['status'] == 'confirmed' ? 'text-success' : 'text-muted'; ?>"></i>
                            Confirmation
                        </div>
                        <?php if ($booking['status'] == 'confirmed'): ?>
                        <span class="badge bg-info">Completed</span>
                        <?php elseif ($booking['status'] == 'cancelled'): ?>
                        <span class="badge bg-danger">Cancelled</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Pending</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Contact Provider -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Contact Provider</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Have questions about your booking? Contact the service provider directly.</p>
                <div class="mb-2">
                    <i class="fas fa-user me-2"></i> <?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?>
                </div>
                <div class="mb-2">
                    <i class="fas fa-envelope me-2"></i> <a href="mailto:<?php echo $booking['email']; ?>"><?php echo $booking['email']; ?></a>
                </div>
                <div class="mb-3">
                    <i class="fas fa-phone me-2"></i> <a href="tel:<?php echo $booking['phone']; ?>"><?php echo $booking['phone']; ?></a>
                </div>
                <a href="<?php echo APP_URL; ?>/service_provider/profile_public.php?id=<?php echo $booking['provider_id']; ?>" class="btn btn-outline-primary w-100">
                    <i class="fas fa-store me-1"></i> View Provider Profile
                </a>
            </div>
        </div>
        
        <!-- Similar Services -->
        <?php
        // Get similar services from the same provider
        $similarServicesQuery = "
            SELECT s.service_id, s.title, s.price, s.duration
            FROM services s
            WHERE s.provider_id = {$booking['provider_id']}
            AND s.service_id != {$booking['service_id']}
            AND s.status = 'active'
            LIMIT 3
        ";
        $similarServices = $db->query($similarServicesQuery)->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($similarServices)):
        ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Other Services by This Provider</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($similarServices as $service): ?>
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo $service['title']; ?></h6>
                                <p class="text-muted mb-0 small"><?php echo $service['duration']; ?> minutes</p>
                            </div>
                            <div class="text-end">
                                <p class="mb-1 fw-bold"><?php echo formatCurrency($service['price']); ?></p>
                                <a href="<?php echo APP_URL; ?>/services/details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-3">
                    <a href="<?php echo APP_URL; ?>/service_provider/profile_public.php?id=<?php echo $booking['provider_id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                        View All Services
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>