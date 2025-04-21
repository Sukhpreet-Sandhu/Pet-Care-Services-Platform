<?php
$pageTitle = 'Services';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$categoryId = intval($_GET['category'] ?? 0);
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sort = $_GET['sort'] ?? 'rating';

// Build query
$query = "
    SELECT s.service_id, s.title, s.description, s.price, s.duration,
           sp.provider_id, sp.business_name, sp.avg_rating, sc.name AS category_name, sc.category_id
    FROM services s
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    WHERE s.is_available = 1 AND s.status = 'active'
";

// Add filters to query
if (!empty($search)) {
    $search = $db->escapeString($search);
    $query .= " AND (s.title LIKE '%$search%' OR s.description LIKE '%$search%' OR sp.business_name LIKE '%$search%')";
}

if ($categoryId > 0) {
    $query .= " AND s.category_id = $categoryId";
}

if ($minPrice !== null) {
    $query .= " AND s.price >= $minPrice";
}

if ($maxPrice !== null) {
    $query .= " AND s.price <= $maxPrice";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY s.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY s.price DESC";
        break;
    case 'duration':
        $query .= " ORDER BY s.duration ASC";
        break;
    case 'rating':
    default:
        $query .= " ORDER BY sp.avg_rating DESC";
        break;
}

// Execute query
$services = $db->query($query)->fetch_all(MYSQLI_ASSOC);

// Get all categories for filter
$categoriesQuery = "SELECT * FROM service_categories ORDER BY name";
$categories = $db->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);

// Get min and max prices for filter
$priceRangeQuery = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM services";
$priceRange = $db->query($priceRangeQuery)->fetch_assoc();
?>

<div class="row">
    <!-- Filters Sidebar -->
    <div class="col-lg-3 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form action="" method="get">
                    <!-- Search -->
                    <div class="mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <!-- Categories -->
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo $categoryId == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-3">
                        <label class="form-label">Price Range</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?php echo $minPrice ?? ''; ?>" min="0">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?php echo $maxPrice ?? ''; ?>" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sort By -->
                    <div class="mb-3">
                        <label class="form-label">Sort By</label>
                        <select class="form-select" name="sort">
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rating</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="duration" <?php echo $sort == 'duration' ? 'selected' : ''; ?>>Duration</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="<?php echo APP_URL; ?>/services" class="btn btn-outline-secondary mt-2">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Services Listing -->
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Available Services</h2>
            <span class="text-muted"><?php echo count($services); ?> services found</span>
        </div>
        
        <?php if (empty($services)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No services found matching your criteria. Try adjusting your filters.
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($services as $service): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card service-card h-100">
                    <div class="card-body">
                        <span class="badge bg-primary mb-2"><?php echo $service['category_name']; ?></span>
                        <h5 class="card-title"><?php echo $service['title']; ?></h5>
                        <p class="card-text small"><?php echo substr($service['description'], 0, 100); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="service-price"><?php echo formatCurrency($service['price']); ?></span>
                            <span class="service-duration"><i class="far fa-clock me-1"></i><?php echo $service['duration']; ?> min</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="service-provider">By <?php echo $service['business_name']; ?></span>
                            <span class="service-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= round($service['avg_rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="<?php echo APP_URL; ?>/services/details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-primary w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>