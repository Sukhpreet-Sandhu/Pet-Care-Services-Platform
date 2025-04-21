<?php
$pageTitle = 'Services';
require_once 'includes/header.php';
require_once 'includes/db.php';

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get service categories for filter
$categoriesQuery = "SELECT * FROM service_categories ORDER BY name";
$categories = $db->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);

// Build base query with required filters
$baseQuery = "
    SELECT s.*, c.name as category_name, sp.business_name, u.first_name, u.last_name
    FROM services s
    JOIN service_categories c ON s.category_id = c.category_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    JOIN users u ON sp.user_id = u.user_id
    WHERE sp.is_verified = 1
    AND s.is_available = 1 
    AND s.status = 'active'";

// Add search filter if provided
if (!empty($search)) {
    $searchTerm = "%" . $db->real_escape_string($search) . "%";
    $baseQuery .= " AND (s.title LIKE '$searchTerm' OR s.description LIKE '$searchTerm')";
}

// Add category filter if provided
if (!empty($category)) {
    $baseQuery .= " AND c.category_id = " . intval($category);
}

// Add order by
$baseQuery .= " ORDER BY s.created_at DESC";

// Debug - uncomment to see the query
// echo "<pre>DEBUG QUERY: " . $baseQuery . "</pre>";

// Execute the query directly
$result = $db->query($baseQuery);
$services = $result->fetch_all(MYSQLI_ASSOC);

// Debug - uncomment to see returned services with their status
/* 
echo "<div style='background:#f8f9fa;padding:10px;margin-bottom:20px'>";
echo "<h5>DEBUG: Found " . count($services) . " services</h5>";
if (count($services) > 0) {
    echo "<table class='table table-sm'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Available</th></tr>";
    foreach ($services as $service) {
        echo "<tr>";
        echo "<td>" . $service['service_id'] . "</td>";
        echo "<td>" . $service['title'] . "</td>";
        echo "<td>" . $service['status'] . "</td>";
        echo "<td>" . ($service['is_available'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";
*/
?>

<!-- Services Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="h2">Available Services</h1>
        <p class="text-muted">Browse services offered by our verified pet care professionals</p>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search services..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Services List -->
<div class="row">
    <?php if (count($services) > 0): ?>
        <?php foreach ($services as $service): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <?php if (!empty($service['image'])): ?>
                <img src="<?php echo UPLOAD_URL . $service['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($service['title']); ?>" style="height: 200px; object-fit: cover;">
                <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-paw fa-3x text-secondary"></i>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                    <p class="card-text small"><?php echo substr(htmlspecialchars($service['description']), 0, 100); ?>...</p>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($service['category_name']); ?></span>
                        <span class="fw-bold"><?php echo formatCurrency($service['price']); ?></span>
                    </div>
                    <p class="card-text small text-muted">
                        <i class="far fa-clock me-1"></i><?php echo $service['duration']; ?> min
                        <br>
                        <i class="far fa-user me-1"></i>By <?php echo htmlspecialchars($service['business_name'] ?: $service['first_name'] . ' ' . $service['last_name']); ?>
                    </p>
                </div>
                <div class="card-footer bg-white border-top-0 d-grid">
                    <a href="<?php echo APP_URL; ?>/service_details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-outline-primary">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                <p class="mb-0"><i class="fas fa-info-circle me-2"></i> No services found matching your criteria. Try adjusting your filters.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>