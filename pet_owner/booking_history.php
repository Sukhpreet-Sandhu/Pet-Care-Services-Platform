<?php
$pageTitle = 'Booking History';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Get pet ID
$petId = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;

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

// Get booking history
$bookingsQuery = "
    SELECT b.*, s.title AS service_title, s.price, sp.business_name, sp.provider_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    WHERE b.pet_id = $petId
    ORDER BY b.booking_date DESC, b.start_time DESC
";
$bookings = $db->query($bookingsQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $pet['name']; ?>'s Booking History</h2>
    <div>
        <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Pet Profile
        </a>
        <a href="<?php echo APP_URL; ?>/services" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Book New Service
        </a>
    </div>
</div>

<?php if (empty($bookings)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-calendar-alt fa-4x text-gray-300 mb-3"></i>
        <h4>No bookings found</h4>
        <p class="text-muted"><?php echo $pet['name']; ?> doesn't have any service history yet.</p>
        <a href="<?php echo APP_URL; ?>/services" class="btn btn-primary mt-2">Browse Services</a>
    </div>
</div>
<?php else: ?>

<!-- Booking History -->
<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">All Bookings</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Provider</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
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
    </div>
</div>

<!-- Booking Statistics -->
<?php
// Calculate statistics
$totalBookings = count($bookings);
$completedBookings = 0;
$cancelledBookings = 0;
$totalSpent = 0;

foreach ($bookings as $booking) {
    if ($booking['status'] === 'completed') {
        $completedBookings++;
        $totalSpent += $booking['price'];
    } elseif ($booking['status'] === 'cancelled') {
        $cancelledBookings++;
    }
}

// Get most visited provider
$providerCounts = [];
foreach ($bookings as $booking) {
    $providerId = $booking['provider_id'];
    $providerName = $booking['business_name'];
    
    if (!isset($providerCounts[$providerId])) {
        $providerCounts[$providerId] = [
            'name' => $providerName,
            'count' => 0
        ];
    }
    
    $providerCounts[$providerId]['count']++;
}

// Sort by count
usort($providerCounts, function($a, $b) {
    return $b['count'] - $a['count'];
});

$favoriteProvider = !empty($providerCounts) ? $providerCounts[0] : null;
?>

<div class="row mt-4">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 mb-0"><?php echo $totalBookings; ?></h1>
                <p class="text-muted">Total Bookings</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 mb-0"><?php echo $completedBookings; ?></h1>
                <p class="text-muted">Completed Services</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <h1 class="display-4 mb-0"><?php echo formatCurrency($totalSpent); ?></h1>
                <p class="text-muted">Total Spent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <?php if ($favoriteProvider): ?>
                <h5 class="mb-2"><?php echo $favoriteProvider['name']; ?></h5>
                <p class="text-muted mb-0">Most Visited Provider</p>
                <p class="text-muted">(<?php echo $favoriteProvider['count']; ?> visits)</p>
                <?php else: ?>
                <p class="text-muted">No favorite provider yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Service Type Distribution -->
<?php
// Calculate service type distribution
$serviceTypes = [];
foreach ($bookings as $booking) {
    $serviceTitle = $booking['service_title'];
    
    if (!isset($serviceTypes[$serviceTitle])) {
        $serviceTypes[$serviceTitle] = 0;
    }
    
    $serviceTypes[$serviceTitle]++;
}

// Sort by count
arsort($serviceTypes);

// Get top 5 services
$topServices = array_slice($serviceTypes, 0, 5, true);
?>

<?php if (!empty($topServices)): ?>
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Most Used Services</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($topServices as $service => $count): ?>
            <div class="col-md-4 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span><?php echo $service; ?></span>
                    <span class="badge bg-primary rounded-pill"><?php echo $count; ?> times</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Monthly Booking Trend -->
<?php
// Calculate monthly booking trend for the last 6 months
$monthlyTrend = [];
$sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

foreach ($bookings as $booking) {
    if ($booking['booking_date'] >= $sixMonthsAgo) {
        $month = date('M Y', strtotime($booking['booking_date']));
        
        if (!isset($monthlyTrend[$month])) {
            $monthlyTrend[$month] = 0;
        }
        
        $monthlyTrend[$month]++;
    }
}

// Sort by month
ksort($monthlyTrend);
?>

<?php if (!empty($monthlyTrend)): ?>
<div class="card shadow-sm mt-4 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Booking Trend (Last 6 Months)</h5>
    </div>
    <div class="card-body">
        <div class="chart-container" style="height: 300px;">
            <canvas id="bookingTrendChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('bookingTrendChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php echo "'" . implode("', '", array_keys($monthlyTrend)) . "'"; ?>],
            datasets: [{
                label: 'Number of Bookings',
                data: [<?php echo implode(', ', array_values($monthlyTrend)); ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>