<?php
$pageTitle = 'My Notifications';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Mark notifications as read if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $markReadQuery = "
        UPDATE notifications
        SET is_read = 1
        WHERE owner_id = $ownerId AND is_read = 0
    ";
    $db->query($markReadQuery);
    
    setFlashMessage('success', 'All notifications marked as read');
    redirect(APP_URL . '/pet_owner/notifications.php');
}

// Delete notification if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notificationId = intval($_GET['delete']);
    
    $deleteQuery = "
        DELETE FROM notifications
        WHERE notification_id = $notificationId AND owner_id = $ownerId
    ";
    
    if ($db->query($deleteQuery)) {
        setFlashMessage('success', 'Notification deleted successfully');
    } else {
        setFlashMessage('error', 'Failed to delete notification');
    }
    
    redirect(APP_URL . '/pet_owner/notifications.php');
}

// Get notifications
$notificationsQuery = "
    SELECT *
    FROM notifications
    WHERE owner_id = $ownerId
    ORDER BY created_at DESC
";
$notifications = $db->query($notificationsQuery)->fetch_all(MYSQLI_ASSOC);

// Count unread notifications
$unreadCount = 0;
foreach ($notifications as $notification) {
    if ($notification['is_read'] == 0) {
        $unreadCount++;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Notifications</h2>
    <div>
        <?php if ($unreadCount > 0): ?>
        <a href="<?php echo APP_URL; ?>/pet_owner/notifications.php?mark_read=all" class="btn btn-outline-primary me-2">
            <i class="fas fa-check-double me-1"></i> Mark All as Read
        </a>
        <?php endif; ?>
        <a href="<?php echo APP_URL; ?>/pet_owner/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if (empty($notifications)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-bell-slash fa-4x text-gray-300 mb-3"></i>
        <h4>No notifications</h4>
        <p class="text-muted">You don't have any notifications yet.</p>
    </div>
</div>
<?php else: ?>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Notifications</h5>
        <span class="badge bg-primary rounded-pill"><?php echo count($notifications); ?> total</span>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($notifications as $notification): ?>
        <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
            <div class="d-flex w-100 justify-content-between align-items-center">
                <h6 class="mb-1">
                    <?php if (!$notification['is_read']): ?>
                    <span class="badge bg-primary me-2">New</span>
                    <?php endif; ?>
                    <?php echo $notification['title']; ?>
                </h6>
                <small class="text-muted"><?php echo timeAgo($notification['created_at']); ?></small>
            </div>
            <p class="mb-1"><?php echo $notification['message']; ?></p>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <?php if (!empty($notification['link'])): ?>
                <a href="<?php echo $notification['link']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>/pet_owner/notifications.php?delete=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?')">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

<?php
// Helper function to format time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>

<?php require_once '../includes/footer.php'; ?>