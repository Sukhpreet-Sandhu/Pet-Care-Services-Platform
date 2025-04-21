<?php
require_once '../includes/init.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get user ID and owner ID
$userId = $_SESSION['user_id'];
$ownerId = $_SESSION['owner_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request');
    redirect(APP_URL . '/pet_owner/profile.php');
}

// Get password
$password = trim($_POST['password'] ?? '');

// Validate password
if (empty($password)) {
    setFlashMessage('error', 'Password is required to delete your account');
    redirect(APP_URL . '/pet_owner/profile.php');
}

// Get user's current password
$passwordQuery = "SELECT password FROM users WHERE user_id = $userId";
$passwordResult = $db->query($passwordQuery);

if ($passwordResult->num_rows === 0) {
    setFlashMessage('error', 'User not found');
    redirect(APP_URL . '/pet_owner/profile.php');
}

$hashedPassword = $passwordResult->fetch_assoc()['password'];

// Verify password
if (!password_verify($password, $hashedPassword)) {
    setFlashMessage('error', 'Incorrect password');
    redirect(APP_URL . '/pet_owner/profile.php');
}

// Start transaction
$db->query('SET autocommit = 0');
$db->query('START TRANSACTION');

try {
    // Delete reviews
    $deleteReviewsQuery = "
        DELETE r FROM reviews r
        JOIN bookings b ON r.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        WHERE p.owner_id = $ownerId
    ";
    $db->query($deleteReviewsQuery);
    
    // Delete payments
    $deletePaymentsQuery = "
        DELETE pay FROM payments pay
        JOIN bookings b ON pay.booking_id = b.booking_id
        JOIN pets p ON b.pet_id = p.pet_id
        WHERE p.owner_id = $ownerId
    ";
    $db->query($deletePaymentsQuery);
    
    // Delete bookings
    $deleteBookingsQuery = "
        DELETE b FROM bookings b
        JOIN pets p ON b.pet_id = p.pet_id
        WHERE p.owner_id = $ownerId
    ";
    $db->query($deleteBookingsQuery);
    
    // Delete pets
    $deletePetsQuery = "DELETE FROM pets WHERE owner_id = $ownerId";
    $db->query($deletePetsQuery);
    
    // Delete pet owner
    $deleteOwnerQuery = "DELETE FROM pet_owners WHERE owner_id = $ownerId";
    $db->query($deleteOwnerQuery);
    
    // Delete user
    $deleteUserQuery = "DELETE FROM users WHERE user_id = $userId";
    $db->query($deleteUserQuery);
    
    // Commit transaction
    $db->query('COMMIT');
    
    // Destroy session
    session_destroy();
    
    // Set flash message for login page
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Your account has been successfully deleted.'
    ];
    
    // Redirect to login page
    redirect(APP_URL . '/login.php');
} catch (Exception $e) {
    // Rollback transaction on error
    $db->query('ROLLBACK');
    
    setFlashMessage('error', 'Failed to delete account: ' . $e->getMessage());
    redirect(APP_URL . '/pet_owner/profile.php');
} finally {
    // Restore autocommit mode
    $db->query('SET autocommit = 1');
}
?>