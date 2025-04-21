<?php
$pageTitle = 'My Pets';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a pet owner
checkAccess('pet_owner');

// Get owner ID
$ownerId = $_SESSION['owner_id'];

// Handle pet deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $petId = intval($_GET['delete']);
    
    // Check if pet belongs to the owner
    $checkQuery = "SELECT pet_id FROM pets WHERE pet_id = $petId AND owner_id = $ownerId";
    $checkResult = $db->query($checkQuery);
    
    if ($checkResult->num_rows > 0) {
        // Delete pet
        $deleteQuery = "DELETE FROM pets WHERE pet_id = $petId";
        if ($db->query($deleteQuery)) {
            setFlashMessage('success', 'Pet deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete pet');
        }
    } else {
        setFlashMessage('error', 'You do not have permission to delete this pet');
    }
    
    redirect(APP_URL . '/pet_owner/pets.php');
}

// Get owner's pets
$petsQuery = "
    SELECT * FROM pets
    WHERE owner_id = $ownerId
    ORDER BY name
";
$pets = $db->query($petsQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Pets</h2>
    <a href="<?php echo APP_URL; ?>/pet_owner/add_pet.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add New Pet
    </a>
</div>

<?php if (empty($pets)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-paw fa-4x text-gray-300 mb-3"></i>
        <h4>You haven't added any pets yet</h4>
        <p class="text-muted">Add your pets to book services for them</p>
        <a href="<?php echo APP_URL; ?>/pet_owner/add_pet.php" class="btn btn-primary mt-2">Add Your First Pet</a>
    </div>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($pets as $pet): ?>
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <?php if (!empty($pet['image'])): ?>
            <img src="<?php echo UPLOAD_URL . $pet['image']; ?>" class="card-img-top" alt="<?php echo $pet['name']; ?>" style="height: 200px; object-fit: cover;">
            <?php else: ?>
            <img src="<?php echo APP_URL; ?>/assets/images/pet-placeholder.jpg" class="card-img-top" alt="<?php echo $pet['name']; ?>" style="height: 200px; object-fit: cover;">
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?php echo $pet['name']; ?></h5>
                <div class="mb-3">
                    <span class="badge bg-primary"><?php echo $pet['type']; ?></span>
                    <?php if (!empty($pet['breed'])): ?>
                    <span class="badge bg-secondary"><?php echo $pet['breed']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <?php if (!empty($pet['age'])): ?>
                    <p class="card-text mb-1"><strong>Age:</strong> <?php echo $pet['age']; ?> years</p>
                    <?php endif; ?>
                    <?php if (!empty($pet['weight'])): ?>
                    <p class="card-text mb-1"><strong>Weight:</strong> <?php echo $pet['weight']; ?> kg</p>
                    <?php endif; ?>
                </div>
                <div class="d-flex">
                    <a href="<?php echo APP_URL; ?>/pet_owner/pet_details.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-outline-primary me-2">View Details</a>
                    <a href="<?php echo APP_URL; ?>/pet_owner/edit_pet.php?id=<?php echo $pet['pet_id']; ?>" class="btn btn-outline-secondary me-2">Edit</a>
                    <a href="<?php echo APP_URL; ?>/pet_owner/pets.php?delete=<?php echo $pet['pet_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this pet?')">Delete</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>