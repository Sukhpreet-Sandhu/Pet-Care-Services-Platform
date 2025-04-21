<?php
$pageTitle = 'Pet Care Educational Resources';
require_once '../includes/header.php';
require_once '../includes/db.php';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT * FROM educational_content WHERE status = 'active'";

if (!empty($category)) {
    $query .= " AND category = '" . $db->real_escape_string($category) . "'";
}

if (!empty($search)) {
    $query .= " AND (title LIKE '%" . $db->real_escape_string($search) . "%' OR 
                     summary LIKE '%" . $db->real_escape_string($search) . "%' OR
                     content LIKE '%" . $db->real_escape_string($search) . "%')";
}

$query .= " ORDER BY created_at DESC";

$result = $db->query($query);
$articles = $result->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$categoryQuery = "SELECT DISTINCT category FROM educational_content WHERE status = 'active' ORDER BY category";
$categories = $db->query($categoryQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-5 fw-bold">Pet Care Educational Resources</h1>
            <p class="lead">Learn valuable information to keep your pets happy and healthy.</p>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="get" action="" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
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

    <!-- Articles Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (count($articles) > 0): ?>
            <?php foreach ($articles as $article): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($article['image'])): ?>
                    <img src="<?php echo UPLOAD_URL . $article['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                        <i class="fas fa-paw fa-3x text-secondary"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($article['category']); ?></span>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($article['created_at'])); ?></small>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($article['summary']); ?></p>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="<?php echo APP_URL; ?>/educational/article.php?id=<?php echo $article['content_id']; ?>" class="btn btn-outline-primary w-100">Read More</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No articles found matching your criteria. Please try a different search or category.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>