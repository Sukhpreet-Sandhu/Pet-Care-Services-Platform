<?php
$pageTitle = 'Login';
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    // Debug information for logged-in users
    echo "<h2>Session Debug Information</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Redirect based on user type
    if ($_SESSION['user_type'] === 'admin' || $_SESSION['role'] === 'admin') {
        redirect(APP_URL . '/admin/dashboard.php');
    } elseif ($_SESSION['user_type'] === 'pet_owner') {
        redirect(APP_URL . '/pet_owner/dashboard.php');
    } elseif ($_SESSION['user_type'] === 'service_provider') {
        redirect(APP_URL . '/service_provider/dashboard.php');
    } else {
        redirect(APP_URL);
    }
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate form data
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        $result = loginUser($email, $password);
        
        // In the loginUser function result handling
        if ($result['success']) {
            // Make sure we're setting both user_type and role for compatibility
            $_SESSION['user_type'] = $result['user_type'];
            $_SESSION['role'] = $result['user_type']; // Set role to be the same as user_type
            
            // Redirect based on user type
            if ($result['user_type'] === 'admin') {
                redirect(APP_URL . '/admin/dashboard.php');
            } elseif ($result['user_type'] === 'pet_owner') {
                redirect(APP_URL . '/pet_owner/dashboard.php');
            } elseif ($result['user_type'] === 'service_provider') {
                redirect(APP_URL . '/service_provider/dashboard.php');
            } else {
                redirect(APP_URL);
            }
        } else {
            $errors['general'] = $result['message'];
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h4 class="card-title mb-0 text-center">Log In to Your Account</h4>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password">
                        <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Log In</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white py-3 text-center">
                <p class="mb-0">Don't have an account? <a href="<?php echo APP_URL; ?>/register.php">Create Account</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>