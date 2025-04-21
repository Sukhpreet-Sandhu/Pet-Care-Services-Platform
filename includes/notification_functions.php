<?php
/**
 * Notification Functions
 * 
 * Helper functions for managing notifications
 */

/**
 * Create a new notification for a pet owner
 * 
 * @param int $ownerId The ID of the pet owner
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $link Optional link to related content
 * @param int $relatedId Optional ID of related entity (booking, pet, etc.)
 * @param string $type Optional notification type
 * @return bool True if notification was created successfully, false otherwise
 */
function createNotification($ownerId, $title, $message, $link = '', $relatedId = 0, $type = 'general') {
    global $db;
    
    $stmt = $db->prepare("
        INSERT INTO notifications (owner_id, title, message, link, related_id, type, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    $stmt->bind_param("isssss", $ownerId, $title, $message, $link, $relatedId, $type);
    
    return $stmt->execute();
}

/**
 * Get unread notification count for a pet owner
 * 
 * @param int $ownerId The ID of the pet owner
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($ownerId) {
    global $db;
    
    $query = "
        SELECT COUNT(*) as count
        FROM notifications
        WHERE owner_id = $ownerId AND is_read = 0
    ";
    
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['count'];
    }
    
    return 0;
}

/**
 * Mark a notification as read
 * 
 * @param int $notificationId The ID of the notification
 * @param int $ownerId The ID of the pet owner (for security)
 * @return bool True if notification was marked as read, false otherwise
 */
function markNotificationAsRead($notificationId, $ownerId) {
    global $db;
    
    $query = "
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = $notificationId AND owner_id = $ownerId
    ";
    
    return $db->query($query);
}

/**
 * Create a booking notification
 * 
 * @param int $ownerId The ID of the pet owner
 * @param int $bookingId The ID of the booking
 * @param string $status The booking status
 * @param string $serviceName The name of the service
 * @return bool True if notification was created successfully, false otherwise
 */
function createBookingNotification($ownerId, $bookingId, $status, $serviceName) {
    $title = '';
    $message = '';
    $link = APP_URL . '/pet_owner/booking_details.php?id=' . $bookingId;
    
    switch ($status) {
        case 'confirmed':
            $title = 'Booking Confirmed';
            $message = "Your booking for $serviceName has been confirmed by the service provider.";
            break;
        case 'completed':
            $title = 'Service Completed';
            $message = "Your booking for $serviceName has been marked as completed. Please leave a review!";
            break;
        case 'cancelled':
            $title = 'Booking Cancelled';
            $message = "Your booking for $serviceName has been cancelled.";
            break;
        default:
            $title = 'Booking Update';
            $message = "Your booking for $serviceName has been updated.";
    }
    
    return createNotification($ownerId, $title, $message, $link, $bookingId, 'booking');
}

/**
 * Create a payment notification
 * 
 * @param int $ownerId The ID of the pet owner
 * @param int $paymentId The ID of the payment
 * @param string $status The payment status
 * @param float $amount The payment amount
 * @param int $bookingId The ID of the related booking
 * @return bool True if notification was created successfully, false otherwise
 */
function createPaymentNotification($ownerId, $paymentId, $status, $amount, $bookingId) {
    $title = '';
    $message = '';
    $link = APP_URL . '/pet_owner/booking_details.php?id=' . $bookingId;
    
    switch ($status) {
        case 'completed':
            $title = 'Payment Successful';
            $message = "Your payment of " . formatCurrency($amount) . " has been processed successfully.";
            break;
        case 'pending':
            $title = 'Payment Required';
            $message = "A payment of " . formatCurrency($amount) . " is required for your booking.";
            $link = APP_URL . '/pet_owner/make_payment.php?id=' . $paymentId;
            break;
        case 'failed':
            $title = 'Payment Failed';
            $message = "Your payment of " . formatCurrency($amount) . " has failed. Please try again.";
            $link = APP_URL . '/pet_owner/make_payment.php?id=' . $paymentId;
            break;
        default:
            $title = 'Payment Update';
            $message = "Your payment of " . formatCurrency($amount) . " has been updated.";
    }
    
    return createNotification($ownerId, $title, $message, $link, $paymentId, 'payment');
}

/**
 * Create a reminder notification
 * 
 * @param int $ownerId The ID of the pet owner
 * @param int $bookingId The ID of the booking
 * @param string $serviceName The name of the service
 * @param string $date The booking date
 * @param string $time The booking time
 * @return bool True if notification was created successfully, false otherwise
 */
function createReminderNotification($ownerId, $bookingId, $serviceName, $date, $time) {
    $title = 'Upcoming Appointment Reminder';
    $message = "Reminder: You have an appointment for $serviceName on " . formatDate($date) . " at $time.";
    $link = APP_URL . '/pet_owner/booking_details.php?id=' . $bookingId;
    
    return createNotification($ownerId, $title, $message, $link, $bookingId, 'reminder');
}
?>