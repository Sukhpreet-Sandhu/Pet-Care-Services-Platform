// This is a new file we need to create or modify if it exists

<?php
$pageTitle = 'Approve Service Provider';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is admin
checkAccess('admin');

if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    
    // Update user status to active
    $userQuery = "UPDATE users SET status = 'active' WHERE user_id = ? AND user_type = 'service_provider'";
    $stmt = $db->prepare($userQuery);
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        // Also mark the provider as verified automatically
        $providerQuery = "UPDATE service_providers SET is_verified = TRUE WHERE user_id = ?";
        $stmt = $db->prepare($providerQuery);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        setFlashMessage('success', 'Service provider approved and verified successfully');
    } else {
        setFlashMessage('error', 'Failed to approve service provider');
    }
    
    redirect(APP_URL . '/admin/providers.php');
}
?>