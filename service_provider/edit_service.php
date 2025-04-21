<?php
$pageTitle = 'Edit Service';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Check if user is logged in and is a service provider
checkAccess('service_provider');

// Get provider ID
$providerId = $_SESSION['provider_id'];

// Check if service ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Invalid service ID');
    redirect(APP_URL . '/service_provider/services.php');
}

$serviceId = intval($_GET['id']);

// Get service details
$serviceQuery = "
    SELECT * FROM services 
    WHERE service_id = $serviceId AND provider_id = $providerId
";
$serviceResult = $db->query($serviceQuery);

// Check if service exists and belongs to the provider
if ($serviceResult->num_rows === 0) {
    setFlashMessage('error', 'Service not found or access denied');
    redirect(APP_URL . '/service_provider/services.php');
}

$service = $serviceResult->fetch_assoc();

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
    $status = isset($_POST['status']) && in_array($_POST['status'], ['active', 'inactive']) 
        ? $_POST['status'] 
        : 'active';
    
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
    
    // If no errors, update the service
    if (empty($errors)) {
        // Process image upload if present
        $image = $service['image']; // Keep existing image by default
        
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
                    // Delete old image if exists
                    if (!empty($service['image']) && file_exists(UPLOAD_PATH . $service['image'])) {
                        unlink(UPLOAD_PATH . $service['image']);
                    }
                    
                    $image = 'services/' . $fileName;
                } else {
                    $errors[] = 'Failed to upload image';
                }
            } else {
                $errors[] = 'Invalid image type. Allowed types: JPG, PNG, GIF';
            }
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
            // Delete image if remove checkbox is checked
            if (!empty($service['image']) && file_exists(UPLOAD_PATH . $service['image'])) {
                unlink(UPLOAD_PATH . $service['image']);
            }
            $image = null;
        }
        
        if (empty($errors)) {
            // Debugging block
            echo "<div style='background: #f8f9fa; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>";
            echo "<strong>Update Debug:</strong><br>";
            echo "Service ID: $serviceId<br>";
            echo "Status: $status<br>";
            echo "Click button below to continue with update:<br>";
            echo "<form method='post'>";
            foreach ($_POST as $key => $value) {
                echo "<input type='hidden' name='$key' value='$value'>";
            }
            echo "<input type='hidden' name='confirmed' value='1'>";
            echo "<button type='submit' class='btn btn-primary'>Continue Update</button>";
            echo "</form>";
            echo "</div>";

            // Only proceed with the update if confirmed
            if (!isset($_POST['confirmed'])) {
                exit;
            }

            // Update service
            echo "<pre>Debug: Updating service with status: $status</pre>";
            $updateQuery = "
                UPDATE services 
                SET category_id = ?, title = ?, description = ?, price = ?, 
                    duration = ?, status = ?, image = ?, updated_at = NOW()
                WHERE service_id = ? AND provider_id = ?
            ";
            
            $stmt = $db->prepare($updateQuery);
            $stmt->bind_param('issdiisii', $categoryId, $title, $description, $price, $duration, $status, $image, $serviceId, $providerId);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Service updated successfully');
                redirect(APP_URL . '/service_provider/services.php');
            } else {
                $errors[] = 'Failed to update service: ' . $db->error;
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
        <h2>Edit Service</h2>
        <p class="text-muted">Update your service information</p>
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
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($service['title']); ?>">
                        <div class="form-text">Enter a descriptive title for your service</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" 
                                    <?php echo $service['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                        <div class="form-text">Provide detailed information about what this service includes</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" required 
                                   value="<?php echo htmlspecialchars($service['price']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="5" step="5" required 
                                   value="<?php echo htmlspecialchars($service['duration']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Service Image</label>
                        <?php if (!empty($service['image'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo UPLOAD_URL . $service['image']; ?>" alt="Service Image" class="img-thumbnail" style="max-height: 150px;">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                <label class="form-check-label" for="remove_image">Remove current image</label>
                            </div>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image">
                        <div class="form-text">Upload a new image to replace the current one (optional)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo $service['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $service['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <div class="form-text">Set to inactive if you don't want to offer this service</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo APP_URL; ?>/service_provider/services.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>