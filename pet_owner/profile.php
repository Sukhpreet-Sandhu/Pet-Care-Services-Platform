<?php
$pageTitle = 'My Profile';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get user ID
$userId = $_SESSION['user_id'];

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Get user details
$userQuery = "
    SELECT u.*, po.address, po.city, po.state, po.zip_code
    FROM users u
    JOIN pet_owners po ON u.user_id = po.user_id
    WHERE u.user_id = $userId
";
$userResult = $db->query($userQuery);

if ($userResult->num_rows === 0) {
    setFlashMessage('error', 'User not found');
    redirect(APP_URL . '/pet_owner/dashboard.php');
}

$user = $userResult->fetch_assoc();

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    // Validate form data
    if (empty($firstName)) {
        $errors['first_name'] = 'First name is required';
    }
    
    if (empty($lastName)) {
        $errors['last_name'] = 'Last name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } elseif ($email !== $user['email']) {
        // Check if email is already taken
        $checkEmailQuery = "
            SELECT user_id FROM users
            WHERE email = '$email' AND user_id != $userId
        ";
        $checkEmailResult = $db->query($checkEmailQuery);
        
        if ($checkEmailResult->num_rows > 0) {
            $errors['email'] = 'Email is already taken';
        }
    }
    
    if (!empty($phone) && !preg_match('/^\d{10}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $errors['phone'] = 'Invalid phone number format';
    }
    
    // Password validation
    if (!empty($newPassword)) {
        // Verify current password
        if (empty($currentPassword)) {
            $errors['current_password'] = 'Current password is required to set a new password';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }
        
        if (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters long';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // If no errors, update user
    if (empty($errors)) {
        // Start transaction
        $db->begin_transaction();
        
        try {
            // Update user table
            $updateUserQuery = "
                UPDATE users
                SET first_name = ?, last_name = ?, email = ?, phone = ?
                WHERE user_id = ?
            ";
            
            $stmt = $db->prepare($updateUserQuery);
            $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);
            $stmt->execute();
            
            // Update pet_owners table
            $updateOwnerQuery = "
                UPDATE pet_owners
                SET address = ?, city = ?, state = ?, zip_code = ?
                WHERE owner_id = ?
            ";
            
            $stmt = $db->prepare($updateOwnerQuery);
            $stmt->bind_param("ssssi", $address, $city, $state, $zipCode, $ownerId);
            $stmt->execute();
            
            // Update password if provided
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $updatePasswordQuery = "
                    UPDATE users
                    SET password = ?
                    WHERE user_id = ?
                ";
                
                $stmt = $db->prepare($updatePasswordQuery);
                $stmt->bind_param("si", $hashedPassword, $userId);
                $stmt->execute();
            }
            
            // Commit transaction
            $db->commit();
            
            $success = true;
            setFlashMessage('success', 'Profile updated successfully');
            
            // Update session data
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;
            
            // Redirect to refresh the page
            redirect(APP_URL . '/pet_owner/profile.php');
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            $errors['general'] = 'Failed to update profile: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Profile</h2>
    <a href="<?php echo APP_URL; ?>/pet_owner/dashboard.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success">Profile updated successfully!</div>
                <?php endif; ?>
                
                <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Address Information</h5>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="zip_code" class="form-label">ZIP Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code']); ?>">
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Change Password</h5>
                    <p class="text-muted small mb-3">Leave these fields blank if you don't want to change your password</p>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password">
                        <?php if (isset($errors['current_password'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['current_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password">
                            <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['new_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                            <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Account Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-circle me-3">
                        <span class="initials"><?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?></span>
                    </div>
                    <div>
                        <h5 class="mb-0"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h5>
                        <p class="text-muted mb-0">Pet Owner</p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <p class="mb-1"><strong>Email:</strong> <?php echo $user['email']; ?></p>
                    <p class="mb-1"><strong>Phone:</strong> <?php echo !empty($user['phone']) ? $user['phone'] : 'Not provided'; ?></p>
                    <p class="mb-0"><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></p>
                </div>
                
                <hr>
                
                <?php
                // Get pet count
                $petCountQuery = "SELECT COUNT(*) as pet_count FROM pets WHERE owner_id = $ownerId";
                $petCount = $db->query($petCountQuery)->fetch_assoc()['pet_count'];
                
                // Get booking count
                $bookingCountQuery = "
                    SELECT COUNT(*) as booking_count 
                    FROM bookings b
                    JOIN pets p ON b.pet_id = p.pet_id
                    WHERE p.owner_id = $ownerId
                ";
                $bookingCount = $db->query($bookingCountQuery)->fetch_assoc()['booking_count'];
                ?>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h4><?php echo $petCount; ?></h4>
                        <p class="text-muted">Pets</p>
                    </div>
                    <div class="col-6">
                        <h4><?php echo $bookingCount; ?></h4>
                        <p class="text-muted">Bookings</p>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="<?php echo APP_URL; ?>/pet_owner/pets.php" class="btn btn-outline-primary">
                        <i class="fas fa-paw me-1"></i> Manage Pets
                    </a>
                    <a href="<?php echo APP_URL; ?>/pet_owner/bookings.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-alt me-1"></i> View Bookings
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Account Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-user-times me-1"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger">Warning: This action cannot be undone.</p>
                <p>Deleting your account will permanently remove all your data, including:</p>
                <ul>
                    <li>Your profile information</li>
                    <li>All your pets' information</li>
                    <li>All booking history</li>
                    <li>All reviews you've submitted</li>
                </ul>
                <p>Are you sure you want to proceed?</p>
                <form id="deleteAccountForm" action="<?php echo APP_URL; ?>/pet_owner/delete_account.php" method="post">
                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Enter your password to confirm</label>
                        <input type="password" class="form-control" id="delete_password" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger">Delete Account</button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 60px;
    height: 60px;
    background-color: #007bff;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.initials {
    font-size: 24px;
    color: white;
    font-weight: bold;
}
</style>

<?php require_once '../includes/footer.php'; ?>