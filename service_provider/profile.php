<?php
$pageTitle = 'My Profile';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];
$userId = $_SESSION['user_id'];

// Get provider details
$providerQuery = "
    SELECT sp.*, u.email, u.first_name, u.last_name, u.phone, u.address
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    WHERE sp.provider_id = $providerId
";
$provider = $db->query($providerQuery)->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data and update profile
    // Add your code here
    
    setFlashMessage('success', 'Profile updated successfully');
    redirect(APP_URL . '/service_provider/profile.php');
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2>My Profile</h2>
        <p class="text-muted">Manage your personal and business information</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                value="<?php echo htmlspecialchars($provider['first_name']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                value="<?php echo htmlspecialchars($provider['last_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                            value="<?php echo htmlspecialchars($provider['email']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                            value="<?php echo htmlspecialchars($provider['phone']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($provider['address']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="business_name" class="form-label">Business Name</label>
                        <input type="text" class="form-control" id="business_name" name="business_name" 
                            value="<?php echo htmlspecialchars($provider['business_name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Business Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($provider['description']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/service_provider/change_password.php" class="btn btn-outline-primary">
                        <i class="fas fa-key me-2"></i> Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>