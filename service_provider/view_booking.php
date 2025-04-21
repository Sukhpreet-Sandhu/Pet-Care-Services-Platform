<?php
$pageTitle = 'Booking Details';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Invalid booking ID');
    redirect(APP_URL . '/service_provider/bookings.php');
}

$bookingId = intval($_GET['id']);

// Get booking details
$bookingQuery = "
    SELECT b.*, s.title AS service_title, s.description AS service_description, 
           s.price, s.duration, p.name AS pet_name, p.type AS pet_type, 
           p.breed, p.age, p.image AS pet_image, p.special_requirements,
           u.first_name, u.last_name, u.email, u.phone, u.address
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN pets p ON b.pet_id = p.pet_id
    JOIN pet_owners po ON p.owner_id = po.owner_id
    JOIN users u ON po.user_id = u.user_id
    WHERE b.booking_id = $bookingId 
    AND s.provider_id = $providerId
";

$bookingResult = $db->query($bookingQuery);

// Check if booking exists and belongs to the provider's services
if ($bookingResult->num_rows === 0) {
    setFlashMessage('error', 'Booking not found or access denied');
    redirect(APP_URL . '/service_provider/bookings.php');
}

$booking = $bookingResult->fetch_assoc();

// Handle booking status updates
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $notes = trim($_POST['provider_notes'] ?? '');
    
    if ($action === 'confirm') {
        $updateQuery = "UPDATE bookings SET status = 'confirmed', provider_notes = ? WHERE booking_id = $bookingId";
        $stmt = $db->prepare($updateQuery);
        $stmt->bind_param('s', $notes);
        
        if ($stmt->execute()) {
            // Send notification to pet owner
            $notifyQuery = "
                INSERT INTO notifications (user_id, title, message, type, related_id)
                SELECT po.user_id, 'Booking Confirmed', CONCAT('Your booking for ', s.title, ' on ', b.booking_date, ' has been confirmed.'), 'booking_confirmed', b.booking_id
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN pets p ON b.pet_id = p.pet_id
                JOIN pet_owners po ON p.owner_id = po.owner_id
                WHERE b.booking_id = $bookingId
            ";
            $db->query($notifyQuery);
            
            setFlashMessage('success', 'Booking confirmed successfully');
        } else {
            setFlashMessage('error', 'Failed to confirm booking');
        }
    } elseif ($action === 'complete') {
        $updateQuery = "UPDATE bookings SET status = 'completed', provider_notes = ? WHERE booking_id = $bookingId";
        $stmt = $db->prepare($updateQuery);
        $stmt->bind_param('s', $notes);
        
        if ($stmt->execute()) {
            // Send notification to pet owner
            $notifyQuery = "
                INSERT INTO notifications (user_id, title, message, type, related_id)
                SELECT po.user_id, 'Service Completed', CONCAT('Your booking for ', s.title, ' has been marked as completed.'), 'booking_completed', b.booking_id
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN pets p ON b.pet_id = p.pet_id
                JOIN pet_owners po ON p.owner_id = po.owner_id
                WHERE b.booking_id = $bookingId
            ";
            $db->query($notifyQuery);
            
            setFlashMessage('success', 'Booking marked as completed');
        } else {
            setFlashMessage('error', 'Failed to update booking');
        }
    } elseif ($action === 'cancel') {
        $cancelReason = trim($_POST['cancel_reason'] ?? '');
        $updateQuery = "UPDATE bookings SET status = 'cancelled', provider_notes = ?, cancel_reason = ? WHERE booking_id = $bookingId";
        $stmt = $db->prepare($updateQuery);
        $stmt->bind_param('ss', $notes, $cancelReason);
        
        if ($stmt->execute()) {
            // Send notification to pet owner
            $notifyQuery = "
                INSERT INTO notifications (user_id, title, message, type, related_id)
                SELECT po.user_id, 'Booking Cancelled', CONCAT('Your booking for ', s.title, ' on ', b.booking_date, ' has been cancelled.'), 'booking_cancelled', b.booking_id
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN pets p ON b.pet_id = p.pet_id
                JOIN pet_owners po ON p.owner_id = po.owner_id
                WHERE b.booking_id = $bookingId
            ";
            $db->query($notifyQuery);
            
            setFlashMessage('success', 'Booking cancelled successfully');
        } else {
            setFlashMessage('error', 'Failed to cancel booking');
        }
    }
    
    redirect(APP_URL . '/service_provider/view_booking.php?id=' . $bookingId);
}

// Get booking notes if any
$notesQuery = "SELECT * FROM booking_notes WHERE booking_id = $bookingId ORDER BY created_at DESC";
$notes = $db->query($notesQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Booking #<?php echo $bookingId; ?></h2>
            <a href="<?php echo APP_URL; ?>/service_provider/bookings.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Bookings
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Booking Summary -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Booking Details</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_title']); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($booking['booking_date']); ?></p>
                        <p class="mb-1"><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time'])); ?></p>
                        <p class="mb-1"><strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $booking['status'] === 'pending' ? 'warning' : 
                                    ($booking['status'] === 'confirmed' ? 'success' : 
                                        ($booking['status'] === 'completed' ? 'info' : 'danger')); 
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Price:</strong> <?php echo formatCurrency($booking['total_price']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Booking Created:</strong> <?php echo formatDateTime($booking['created_at']); ?></p>
                        <?php if (!empty($booking['provider_notes'])): ?>
                        <p class="mb-1"><strong>Provider Notes:</strong> <?php echo htmlspecialchars($booking['provider_notes']); ?></p>
                        <?php endif; ?>
                        <?php if ($booking['status'] === 'cancelled' && !empty($booking['cancel_reason'])): ?>
                        <p class="mb-1"><strong>Cancellation Reason:</strong> <?php echo htmlspecialchars($booking['cancel_reason']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="font-weight-bold">Service Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($booking['service_description'])); ?></p>
                    </div>
                </div>
                
                <!-- Booking Actions -->
                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="font-weight-bold">Booking Actions</h6>
                        <form method="post" action="" id="bookingActionForm">
                            <div class="mb-3">
                                <label for="provider_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="provider_notes" name="provider_notes" rows="2"><?php echo htmlspecialchars($booking['provider_notes']); ?></textarea>
                                <div class="form-text">Add any notes about this booking (optional)</div>
                            </div>
                            
                            <div id="cancel_reason_container" class="mb-3 d-none">
                                <label for="cancel_reason" class="form-label">Cancellation Reason</label>
                                <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="2"></textarea>
                                <div class="form-text">Please provide a reason for cancellation</div>
                            </div>
                            
                            <input type="hidden" name="action" id="action_input">
                            
                            <div class="d-flex gap-2">
                                <?php if ($booking['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-success" onclick="submitAction('confirm')">
                                    <i class="fas fa-check me-1"></i> Confirm Booking
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] === 'confirmed'): ?>
                                <button type="button" class="btn btn-info" onclick="submitAction('complete')">
                                    <i class="fas fa-check-double me-1"></i> Mark as Completed
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                <button type="button" class="btn btn-danger" onclick="showCancelForm()">
                                    <i class="fas fa-times me-1"></i> Cancel Booking
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Pet & Owner Info -->
    <div class="col-lg-4">
        <!-- Pet Information -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Pet Information</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($booking['pet_image'])): ?>
                <div class="text-center mb-3">
                    <img src="<?php echo UPLOAD_URL . $booking['pet_image']; ?>" alt="<?php echo $booking['pet_name']; ?>" class="img-fluid rounded" style="max-height: 150px;">
                </div>
                <?php endif; ?>
                
                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($booking['pet_name']); ?></p>
                <p class="mb-1"><strong>Type:</strong> <?php echo htmlspecialchars($booking['pet_type']); ?></p>
                <p class="mb-1"><strong>Breed:</strong> <?php echo htmlspecialchars($booking['breed']); ?></p>
                <p class="mb-1"><strong>Age:</strong> <?php echo htmlspecialchars($booking['age']); ?> years</p>
                
                <?php if (!empty($booking['special_requirements'])): ?>
                <div class="mt-3">
                    <h6 class="font-weight-bold">Special Requirements</h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['special_requirements'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Owner Information -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Owner Information</h6>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
                <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($booking['address']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Booking Notes -->
<?php if (!empty($notes)): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Booking Notes</h6>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($notes as $note): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo $note['author_type'] === 'provider' ? 'You' : 'Pet Owner'; ?>:</strong>
                                <?php echo htmlspecialchars($note['note']); ?>
                            </div>
                            <small class="text-muted"><?php echo formatDateTime($note['created_at']); ?></small>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function submitAction(action) {
    if (confirm('Are you sure you want to ' + action + ' this booking?')) {
        document.getElementById('action_input').value = action;
        document.getElementById('bookingActionForm').submit();
    }
}

function showCancelForm() {
    document.getElementById('cancel_reason_container').classList.remove('d-none');
    document.getElementById('action_input').value = 'cancel';
    
    // Add an event listener to the form to ensure the reason is provided
    document.getElementById('bookingActionForm').addEventListener('submit', function(e) {
        const cancelReason = document.getElementById('cancel_reason').value.trim();
        if (document.getElementById('action_input').value === 'cancel' && cancelReason === '') {
            e.preventDefault();
            alert('Please provide a reason for cancellation');
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>