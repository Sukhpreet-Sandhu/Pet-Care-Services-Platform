<?php
$pageTitle = 'Register';
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(APP_URL);
}

$errors = [];
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'user_type' => 'pet_owner'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'user_type' => $_POST['user_type'] ?? 'pet_owner',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Validate form data
    if (empty($formData['first_name'])) {
        $errors['first_name'] = 'First name is required';
    }
    
    if (empty($formData['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($formData['password']) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no errors, register user
    if (empty($errors)) {
        $result = registerUser(
            $formData['email'],
            $formData['password'],
            $formData['user_type'],
            $formData['first_name'],
            $formData['last_name'],
            $formData['phone']
        );
        
        if ($result['success']) {
            setFlashMessage('success', 'Registration successful! You can now log in.');
            redirect(APP_URL . '/login.php');
        } else {
            $errors['general'] = $result['message'];
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h4 class="card-title mb-0 text-center">Create an Account</h4>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">I am a:</label>
                        <div class="d-flex">
                            <div class="form-check me-4">
                                <input class="form-check-input" type="radio" name="user_type" id="petOwner" value="pet_owner" <?php echo $formData['user_type'] === 'pet_owner' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="petOwner">Pet Owner</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_type" id="serviceProvider" value="service_provider" <?php echo $formData['user_type'] === 'service_provider' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="serviceProvider">Service Provider</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="firstName" name="first_name" value="<?php echo htmlspecialchars($formData['first_name']); ?>">
                            <?php if (isset($errors['first_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="lastName" name="last_name" value="<?php echo htmlspecialchars($formData['last_name']); ?>">
                            <?php if (isset($errors['last_name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>">
                        <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number (optional)</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password">
                        <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirmPassword" name="confirm_password">
                        <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white py-3 text-center">
                <p class="mb-0">Already have an account? <a href="<?php echo APP_URL; ?>/login.php">Log In</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>