<?php
/**
 * Send Reminders Script
 * 
 * This script sends reminder notifications for upcoming appointments.
 * It should be run daily via a cron job or scheduled task.
 */

// Set up environment
define('APP_PATH', dirname(__DIR__));
require_once APP_PATH . '/includes/init.php';
require_once APP_PATH . '/includes/db.php';
require_once APP_PATH . '/includes/notification_functions.php';

// Get bookings for tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$bookingsQuery = "
    SELECT b.*, s.title AS service_title, p.owner_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN pets p ON b.pet_id = p.pet_id
    WHERE b.booking_date = '$tomorrow'
    AND b.status = 'confirmed'
";

$bookings = $db->query($bookingsQuery)->fetch_all(MYSQLI_ASSOC);

// Send reminders
$reminderCount = 0;
foreach ($bookings as $booking) {
    $startTime = date('g:i A', strtotime($booking['start_time']));
    
    $success = createReminderNotification(
        $booking['owner_id'],
        $booking['booking_id'],
        $booking['service_title'],
        $booking['booking_date'],
        $startTime
    );
    
    if ($success) {
        $reminderCount++;
    }
}

echo "Sent $reminderCount reminder notifications for appointments on $tomorrow\n";
?>