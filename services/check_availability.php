<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get parameters
$providerId = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 0;
$date = $_GET['date'] ?? '';

// Validate parameters
if ($providerId <= 0 || empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Get day of week
$dayOfWeek = date('l', strtotime($date));

// Get provider availability for the day
$availabilityQuery = "
    SELECT * FROM provider_availability
    WHERE provider_id = $providerId AND day_of_week = '$dayOfWeek'
";
$availabilityResult = $db->query($availabilityQuery);

if ($availabilityResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No availability for this day', 'available_slots' => []]);
    exit;
}

$availability = $availabilityResult->fetch_assoc();
$startTime = strtotime($availability['start_time']);
$endTime = strtotime($availability['end_time']);

// Get booked slots for the day
$bookedSlotsQuery = "
    SELECT start_time, end_time
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE s.provider_id = $providerId
    AND b.booking_date = '$date'
    AND b.status IN ('pending', 'confirmed')
";
$bookedSlotsResult = $db->query($bookedSlotsQuery);
$bookedSlots = [];

while ($slot = $bookedSlotsResult->fetch_assoc()) {
    $bookedSlots[] = [
        'start' => strtotime($slot['start_time']),
        'end' => strtotime($slot['end_time'])
    ];
}

// Generate available time slots (30-minute intervals)
$interval = 30 * 60; // 30 minutes in seconds
$availableSlots = [];

for ($time = $startTime; $time < $endTime; $time += $interval) {
    $slotStart = $time;
    $slotEnd = $time + $interval;
    
    // Check if slot is available
    $isAvailable = true;
    
    foreach ($bookedSlots as $bookedSlot) {
        // Check if there's an overlap
        if (
            ($slotStart >= $bookedSlot['start'] && $slotStart < $bookedSlot['end']) ||
            ($slotEnd > $bookedSlot['start'] && $slotEnd <= $bookedSlot['end']) ||
            ($slotStart <= $bookedSlot['start'] && $slotEnd >= $bookedSlot['end'])
        ) {
            $isAvailable = false;
            break;
        }
    }
    
    if ($isAvailable) {
        $availableSlots[] = [
            'start_time' => date('H:i:s', $slotStart),
            'end_time' => date('H:i:s', $slotEnd),
            'display_time' => date('g:i A', $slotStart)
        ];
    }
}

echo json_encode([
    'success' => true,
    'available_slots' => $availableSlots
]);
?>

<p>
    <?php 
    echo strlen($service['provider_description'] ?? '') > 0 
         ? substr($service['provider_description'], 0, 200) . '...' 
         : 'No description available.'; 
    ?>
</p>