<?php
$pageTitle = 'Edit Pet';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Get pet ID
$petId = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
    setFlashMessage('error', 'Pet not found or you do not have permission to edit this pet');
    redirect(APP_URL . '/pet_owner/pets.php');
}

$pet = $petResult->fetch_assoc();

$errors = [];
$formData = [
    'name' => $pet['name'],
    'type' => $pet['type'],
    'breed' => $pet['breed'],
    'age' => $pet['age'],
    'weight' => $pet['weight'],
    'special_needs' => $pet['special_needs'],
    'medical_conditions' => $pet['medical_conditions']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'type' => trim($_POST['type'] ?? ''),
        'breed' => trim($_POST['breed'] ?? ''),
        'age' => trim($_POST['age'] ?? ''),
        'weight' => trim($_POST['weight'] ?? ''),
        'special_needs' => trim($_POST['special_needs'] ?? ''),
        'medical_conditions' => trim($_POST['medical_conditions'] ?? '')
    ];
    
    // Validate form data
    if (empty($formData['name'])) {
        $errors['name'] = 'Pet name is required';
    }
    
    if (empty($formData['type'])) {
        $errors['type'] = 'Pet type is required';
    }
    
    if (!empty($formData['age']) && !is_numeric($formData['age'])) {
        $errors['age'] = 'Age must be a number';
    }
    
    if (!empty($formData['weight']) && !is_numeric($formData['weight'])) {
        $errors['weight'] = 'Weight must be a number';
    }
    
    // Handle image upload
    $imagePath = $pet['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['image'], 'pets');
        
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['file_path'];
        } else {
            $errors['image'] = $uploadResult['message'];
        }
    }
    
    // If no errors, update pet
    if (empty($errors)) {
        $stmt = $db->prepare("
            UPDATE pets
            SET name = ?, type = ?, breed = ?, age = ?, weight = ?, special_needs = ?, medical_conditions = ?, image = ?
            WHERE pet_id = ? AND owner_id = ?
        ");
        
        $stmt->bind_param(
            "ssssssssii",
            $formData['name'],
            $formData['type'],
            $formData['breed'],
            $formData['age'],
            $formData['weight'],
            $formData['special_needs'],
            $formData['medical_conditions'],
            $imagePath,
            $petId,
            $ownerId
        );
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Pet updated successfully');
            redirect(APP_URL . '/pet_owner/pet_details.php?id=' . $petId);
        } else {
            $errors['general'] = 'Failed to update pet. Please try again.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Edit Pet: <?php echo $pet['name']; ?></h4>
                <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $petId; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Pet Details
                </a>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Pet Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Pet Type *</label>
                            <select class="form-select <?php echo isset($errors['type']) ? 'is-invalid' : ''; ?>" id="type" name="type" required>
                                <option value="">-- Select Type --</option>
                                <option value="Dog" <?php echo $formData['type'] === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                                <option value="Cat" <?php echo $formData['type'] === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                                <option value="Bird" <?php echo $formData['type'] === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                                <option value="Fish" <?php echo $formData['type'] === 'Fish' ? 'selected' : ''; ?>>Fish</option>
                                <option value="Small Animal" <?php echo $formData['type'] === 'Small Animal' ? 'selected' : ''; ?>>Small Animal</option>
                                <option value="Reptile" <?php echo $formData['type'] === 'Reptile' ? 'selected' : ''; ?>>Reptile</option>
                                <option value="Other" <?php echo $formData['type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <?php if (isset($errors['type'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['type']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="breed" class="form-label">Breed</label>
                            <input type="text" class="form-control" id="breed" name="breed" value="<?php echo htmlspecialchars($formData['breed']); ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="age" class="form-label">Age (years)</label>
                            <input type="number" class="form-control <?php echo isset($errors['age']) ? 'is-invalid' : ''; ?>" id="age" name="age" value="<?php echo htmlspecialchars($formData['age']); ?>" min="0" step="0.1">
                            <?php if (isset($errors['age'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['age']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control <?php echo isset($errors['weight']) ? 'is-invalid' : ''; ?>" id="weight" name="weight" value="<?php echo htmlspecialchars($formData['weight']); ?>" min="0" step="0.1">
                            <?php if (isset($errors['weight'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['weight']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="special_needs" class="form-label">Special Needs</label>
                        <textarea class="form-control" id="special_needs" name="special_needs" rows="2"><?php echo htmlspecialchars($formData['special_needs']); ?></textarea>
                        <div class="form-text">Any special requirements or preferences for your pet</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="medical_conditions" class="form-label">Medical Conditions</label>
                        <textarea class="form-control" id="medical_conditions" name="medical_conditions" rows="2"><?php echo htmlspecialchars($formData['medical_conditions']); ?></textarea>
                        <div class="form-text">Any medical conditions, allergies, or medications</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="image" class="form-label">Pet Image</label>
                        <?php if (!empty($pet['image'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo UPLOAD_URL . $pet['image']; ?>" alt="<?php echo $pet['name']; ?>" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" id="image" name="image" accept="image/*">
                        <?php if (isset($errors['image'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['image']; ?></div>
                        <?php else: ?>
                        <div class="form-text">Upload a new photo of your pet (optional)</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $petId; ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Pet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>