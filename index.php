<?php
$pageTitle = 'Home';
require_once 'includes/header.php';
require_once 'includes/db.php';

// Get featured service providers
$featuredProvidersQuery = "
    SELECT sp.provider_id, sp.business_name, u.first_name, u.last_name, sp.description, sp.avg_rating, 
           COUNT(DISTINCT s.service_id) AS service_count
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    LEFT JOIN services s ON sp.provider_id = s.provider_id
    WHERE sp.is_verified = 1
    GROUP BY sp.provider_id
    ORDER BY sp.avg_rating DESC
    LIMIT 3
";
$featuredProviders = $db->query($featuredProvidersQuery)->fetch_all(MYSQLI_ASSOC);

// Get service categories
$categoriesQuery = "SELECT * FROM service_categories ORDER BY name";
$categories = $db->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);

// Get recent educational content
$educationalContentQuery = "
    SELECT ec.content_id, ec.title, ec.category, ec.image, ec.created_at,
           u.first_name, u.last_name
    FROM educational_content ec
    JOIN users u ON ec.author_id = u.user_id
    ORDER BY ec.created_at DESC
    LIMIT 3
";
$educationalContent = $db->query($educationalContentQuery)->fetch_all(MYSQLI_ASSOC);
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <h1>Find the Perfect Care for Your Pet</h1>
        <p class="lead mb-4">Connect with trusted pet care professionals in your area</p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="<?php echo APP_URL; ?>/services" method="get" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search for services...">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Service Categories -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Our Services</h2>
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <a href="<?php echo APP_URL; ?>/services?category=<?php echo $category['category_id']; ?>" class="text-decoration-none">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas <?php echo $category['icon']; ?> fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title"><?php echo $category['name']; ?></h5>
                            <p class="card-text small"><?php echo $category['description']; ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

<!-- Featured Service Providers -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Featured Service Providers</h2>
        <div class="row">
            <?php foreach ($featuredProviders as $provider): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <img src="<?php echo APP_URL; ?>/assets/images/provider-placeholder.jpg" alt="<?php echo $provider['business_name']; ?>" class="rounded-circle mb-3" width="100">
                        <h5 class="card-title"><?php echo $provider['business_name']; ?></h5>
                        <p class="card-text small"><?php echo substr($provider['description'], 0, 100); ?>...</p>
                        <div class="service-rating mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= round($provider['avg_rating'])): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <p class="small text-muted"><?php echo $provider['service_count']; ?> services available</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pet Care Tips -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Pet Care Tips</h2>
        <div class="row">
            <?php foreach ($educationalContent as $content): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($content['image']): ?>
                    <img src="<?php echo UPLOAD_URL . 'educational/' . $content['image']; ?>" class="card-img-top" alt="<?php echo $content['title']; ?>">
                    <?php else: ?>
                    <img src="<?php echo APP_URL; ?>/assets/images/pet-care-tips.jpg" class="card-img-top" alt="Pet Care Tips">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge bg-primary mb-2"><?php echo $content['category']; ?></span>
                        <h5 class="card-title"><?php echo $content['title']; ?></h5>
                        <p class="card-text small text-muted">By <?php echo $content['first_name'] . ' ' . $content['last_name']; ?> | <?php echo formatDate($content['created_at']); ?></p>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="<?php echo APP_URL; ?>/educational/article.php?id=<?php echo $content['content_id']; ?>" class="btn btn-outline-primary w-100">Read More</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo APP_URL; ?>/educational" class="btn btn-outline-primary">View All Articles</a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-search fa-2x"></i>
                </div>
                <h4>Find Services</h4>
                <p>Browse through our wide range of pet care services and find the perfect match for your pet's needs.</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-calendar-alt fa-2x"></i>
                </div>
                <h4>Book Appointment</h4>
                <p>Select your preferred date and time, and book your appointment with just a few clicks.</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-paw fa-2x"></i>
                </div>
                <h4>Enjoy Quality Care</h4>
                <p>Relax knowing your pet is receiving the best care from our verified and trusted service providers.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2 class="mb-4">Ready to Find the Perfect Care for Your Pet?</h2>
        <p class="lead mb-4">Join thousands of pet owners who trust our platform for their pet care needs.</p>
        <div class="d-flex justify-content-center">
            <?php if (!isLoggedIn()): ?>
            <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-light btn-lg me-3">Sign Up Now</a>
            <a href="<?php echo APP_URL; ?>/services" class="btn btn-outline-light btn-lg">Browse Services</a>
            <?php else: ?>
            <a href="<?php echo APP_URL; ?>/services" class="btn btn-light btn-lg">Browse Services</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>