<?php
$pageTitle = 'Notifications';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get user ID (not provider ID, we need user ID for notifications)
$userId = $_SESSION['user_id'];

// Handle mark as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $notificationId = intval($_GET['id']);
    $markReadQuery = "UPDATE notifications SET is_read = 1 WHERE notification_id = $notificationId AND user_id = $userId";
    $db->query($markReadQuery);
    redirect(APP_URL . '/service_provider/notifications.php');
}

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $markAllReadQuery = "UPDATE notifications SET is_read = 1 WHERE user_id = $userId AND is_read = 0";
    $db->query($markAllReadQuery);
    setFlashMessage('success', 'All notifications marked as read');
    redirect(APP_URL . '/service_provider/notifications.php');
}

// Get filter parameters
$isRead = isset($_GET['is_read']) ? $_GET['is_read'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Build query
$query = "SELECT * FROM notifications WHERE user_id = $userId";

if ($isRead !== '') {
    $query .= " AND is_read = " . ($isRead === 'read' ? '1' : '0');
}

if (!empty($type)) {
    $query .= " AND type = '$type'";
}

$query .= " ORDER BY created_at DESC";

$result = $db->query($query);
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Get unread count
$unreadQuery = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $userId AND is_read = 0";
$unreadCount = $db->query($unreadQuery)->fetch_assoc()['count'];

// Get notification types for filter
$typesQuery = "SELECT DISTINCT type FROM notifications WHERE user_id = $userId ORDER BY type";
$types = $db->query($typesQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Notifications</h2>
            <?php if ($unreadCount > 0): ?>
            <a href="<?php echo APP_URL; ?>/service_provider/notifications.php?action=mark_all_read" class="btn btn-outline-primary">
                <i class="fas fa-check-double me-1"></i> Mark All as Read
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filter Notifications</h6>
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="is_read" class="form-label">Status</label>
                        <select class="form-select" id="is_read" name="is_read">
                            <option value="">All Notifications</option>
                            <option value="unread" <?php echo $isRead === 'unread' ? 'selected' : ''; ?>>Unread Only</option>
                            <option value="read" <?php echo $isRead === 'read' ? 'selected' : ''; ?>>Read Only</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">All Types</option>
                            <?php foreach ($types as $notificationType): ?>
                            <option value="<?php echo $notificationType['type']; ?>" <?php echo $type === $notificationType['type'] ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $notificationType['type'])); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?php echo APP_URL; ?>/service_provider/notifications.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Notifications
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?> unread</span>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-bell-slash fa-3x text-gray-300 mb-3"></i>
                    <p class="mb-0">No notifications found</p>
                </div>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-light'; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <?php if (!$notification['is_read']): ?>
                                <span class="badge bg-primary me-2">New</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($notification['title']); ?>
                            </h5>
                            <small class="text-muted"><?php echo formatTimeAgo($notification['created_at']); ?></small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">
                                Type: <?php echo ucwords(str_replace('_', ' ', $notification['type'])); ?>
                            </small>
                            <?php if (!$notification['is_read']): ?>
                            <a href="<?php echo APP_URL; ?>/service_provider/notifications.php?action=mark_read&id=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-check me-1"></i> Mark as Read
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($notification['related_id'])): ?>
                            <?php 
                                $link = '#';
                                switch ($notification['type']) {
                                    case 'new_booking':
                                    case 'booking_cancelled':
                                    case 'booking_confirmed':
                                    case 'booking_completed':
                                        $link = APP_URL . '/service_provider/view_booking.php?id=' . $notification['related_id'];
                                        break;
                                    // Add other types as needed
                                }
                            ?>
                            <a href="<?php echo $link; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>