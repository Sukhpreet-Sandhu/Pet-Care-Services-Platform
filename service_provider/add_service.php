<?php
$pageTitle = 'Add New Service';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];

// Get service categories
$categoriesQuery = "SELECT * FROM service_categories ORDER BY name";
$categories = $db->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $categoryId = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $status = $_POST['status'];
    
    // Validate data
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Service title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if ($categoryId <= 0) {
        $errors[] = 'Please select a valid category';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero';
    }
    
    if ($duration <= 0) {
        $errors[] = 'Duration must be greater than zero';
    }
    
    // If no errors, insert the service
    if (empty($errors)) {
        // Process image upload if present
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['image']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $fileName = time() . '_' . $_FILES['image']['name'];
                $uploadPath = UPLOAD_PATH . 'services/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $filePath = $uploadPath . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                    $image = 'services/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload image';
                }
            } else {
                $errors[] = 'Invalid image type. Allowed types: JPG, PNG, GIF';
            }
        }
        
        if (empty($errors)) {
            // Insert service
            $insertQuery = "
                INSERT INTO services (provider_id, category_id, title, description, price, duration, status, image, is_available)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ";
            
            $stmt = $db->prepare($insertQuery);
            $stmt->bind_param('iissdisd', $providerId, $categoryId, $title, $description, $price, $duration, $status, $image);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Service added successfully');
                redirect(APP_URL . '/service_provider/services.php');
            } else {
                $errors[] = 'Failed to add service: ' . $db->error;
            }
        }
    }
    
    // If there are errors, show them
    if (!empty($errors)) {
        $errorMessage = '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
        setFlashMessage('error', $errorMessage);
    }
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2>Add New Service</h2>
        <p class="text-muted">Create a new service offering for pet owners</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Service Information</h6>
            </div>
            <div class="card-body">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Service Title</label>
                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        <div class="form-text">Enter a descriptive title for your service</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="form-text">Provide detailed information about what this service includes</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="5" step="5" required value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '30'; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Service Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                        <div class="form-text">Upload an image representing your service (optional)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <div class="form-text">Set to inactive if you don't want to offer this service yet</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo APP_URL; ?>/service_provider/services.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>