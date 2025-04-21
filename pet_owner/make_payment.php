<?php
$pageTitle = 'Complete Payment';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Get payment ID
$paymentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($paymentId <= 0) {
    setFlashMessage('error', 'Invalid payment ID');
    redirect(APP_URL . '/pet_owner/bookings.php');
}

// Get payment details
$paymentQuery = "
    SELECT p.*, b.booking_id, b.booking_date, b.start_time, b.end_time, b.status AS booking_status,
           s.title AS service_title, s.price,
           sp.business_name, sp.provider_id,
           pet.name AS pet_name, pet.pet_id
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    JOIN pets pet ON b.pet_id = pet.pet_id
    WHERE p.payment_id = $paymentId AND pet.owner_id = $ownerId AND p.status = 'pending'
";

$paymentResult = $db->query($paymentQuery);

if ($paymentResult->num_rows === 0) {
    setFlashMessage('error', 'Payment not found, already completed, or you do not have permission to access this payment');
    redirect(APP_URL . '/pet_owner/bookings.php');
}

$payment = $paymentResult->fetch_assoc();

// Process payment
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $cardName = trim($_POST['card_name'] ?? '');
    $cardNumber = trim($_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvv = trim($_POST['card_cvv'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'credit_card');
    
    // Validate form data
    if (empty($cardName)) {
        $errors['card_name'] = 'Cardholder name is required';
    }
    
    if (empty($cardNumber)) {
        $errors['card_number'] = 'Card number is required';
    } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $cardNumber))) {
        $errors['card_number'] = 'Invalid card number format';
    }
    
    if (empty($cardExpiry)) {
        $errors['card_expiry'] = 'Expiry date is required';
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $cardExpiry)) {
        $errors['card_expiry'] = 'Invalid expiry date format (MM/YY)';
    } else {
        list($month, $year) = explode('/', $cardExpiry);
        $expiry = \DateTime::createFromFormat('my', $month . $year);
        $now = new \DateTime();
        
        if ($expiry < $now) {
            $errors['card_expiry'] = 'Card has expired';
        }
    }
    
    if (empty($cardCvv)) {
        $errors['card_cvv'] = 'CVV is required';
    } elseif (!preg_match('/^\d{3,4}$/', $cardCvv)) {
        $errors['card_cvv'] = 'Invalid CVV format';
    }
    
    // If no errors, process payment
    if (empty($errors)) {
        // In a real application, you would integrate with a payment gateway here
        // For this demo, we'll simulate a successful payment
        
        // Generate a random transaction ID
        $transactionId = 'TXN' . time() . rand(1000, 9999);
        
        // Update payment record
        $updatePaymentQuery = "
            UPDATE payments
            SET status = 'completed', 
                payment_method = '$paymentMethod', 
                transaction_id = '$transactionId', 
                payment_date = NOW()
            WHERE payment_id = $paymentId
        ";
        
        if ($db->query($updatePaymentQuery)) {
            // Update booking status if it's pending
            if ($payment['booking_status'] === 'pending') {
                $updateBookingQuery = "
                    UPDATE bookings
                    SET status = 'confirmed'
                    WHERE booking_id = {$payment['booking_id']}
                ";
                $db->query($updateBookingQuery);
            }
            
            $success = true;
            setFlashMessage('success', 'Payment completed successfully');
            
            // Redirect after a short delay
            header("Refresh: 3; URL=" . APP_URL . "/pet_owner/booking_details.php?id=" . $payment['booking_id']);
        } else {
            $errors['general'] = 'Failed to process payment. Please try again.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if ($success): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                <h2 class="mb-3">Payment Successful!</h2>
                <p class="mb-4">Your payment for <?php echo $payment['service_title']; ?> has been processed successfully.</p>
                <p class="mb-1"><strong>Transaction ID:</strong> <?php echo $transactionId; ?></p>
                <p class="mb-1"><strong>Amount:</strong> <?php echo formatCurrency($payment['amount']); ?></p>
                <p class="mb-4"><strong>Date:</strong> <?php echo date('F j, Y, g:i a'); ?></p>
                <a href="<?php echo APP_URL; ?>/pet_owner/booking_details.php?id=<?php echo $payment['booking_id']; ?>" class="btn btn-primary">
                    View Booking Details
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="card-title mb-0">Complete Payment</h4>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="mb-3">Booking Summary</h5>
                        <p class="mb-1"><strong>Service:</strong> <?php echo $payment['service_title']; ?></p>
                        <p class="mb-1"><strong>Provider:</strong> <?php echo $payment['business_name']; ?></p>
                        <p class="mb-1"><strong>Pet:</strong> <?php echo $payment['pet_name']; ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($payment['booking_date']); ?></p>
                        <p class="mb-1"><strong>Time:</strong> <?php echo date('g:i A', strtotime($payment['start_time'])); ?> - <?php echo date('g:i A', strtotime($payment['end_time'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded">
                            <h5 class="mb-3">Payment Details</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Service Fee:</span>
                                <span><?php echo formatCurrency($payment['amount']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span>$0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span><?php echo formatCurrency($payment['amount']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="">
                    <h5 class="mb-3">Payment Method</h5>
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                            <label class="form-check-label" for="credit_card">Credit Card</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_method" id="debit_card" value="debit_card">
                            <label class="form-check-label" for="debit_card">Debit Card</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="card_name" class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control <?php echo isset($errors['card_name']) ? 'is-invalid' : ''; ?>" id="card_name" name="card_name" placeholder="John Doe" required>
                        <?php if (isset($errors['card_name'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['card_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="card_number" class="form-label">Card Number</label>
                        <input type="text" class="form-control <?php echo isset($errors['card_number']) ? 'is-invalid' : ''; ?>" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                        <?php if (isset($errors['card_number'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['card_number']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="card_expiry" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control <?php echo isset($errors['card_expiry']) ? 'is-invalid' : ''; ?>" id="card_expiry" name="card_expiry" placeholder="MM/YY" required>
                            <?php if (isset($errors['card_expiry'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['card_expiry']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="card_cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control <?php echo isset($errors['card_cvv']) ? 'is-invalid' : ''; ?>" id="card_cvv" name="card_cvv" placeholder="123" required>
                            <?php if (isset($errors['card_cvv'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['card_cvv']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo APP_URL; ?>/pet_owner/booking_details.php?id=<?php echo $payment['booking_id']; ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock me-1"></i> Pay <?php echo formatCurrency($payment['amount']); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple formatting for card inputs
document.addEventListener('DOMContentLoaded', function() {
    // Format card number with spaces
    const cardNumber = document.getElementById('card_number');
    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            
            // Add spaces every 4 digits
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formattedValue += ' ';
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Format expiry date with slash
    const cardExpiry = document.getElementById('card_expiry');
    if (cardExpiry) {
        cardExpiry.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            
            e.target.value = value;
        });
    }
    
    // Limit CVV to 3-4 digits
    const cardCvv = document.getElementById('card_cvv');
    if (cardCvv) {
        cardCvv.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            e.target.value = value;
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>