<?php
$pageTitle = 'Article';
require_once '../includes/header.php';

// Get article ID
$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($articleId <= 0) {
    setFlashMessage('error', 'Invalid article ID');
    redirect(APP_URL . '/educational');
}

// Get article details
$query = "SELECT * FROM educational_content WHERE content_id = ? AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $articleId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Article not found');
    redirect(APP_URL . '/educational');
}

$article = $result->fetch_assoc();

// Get related articles
$relatedQuery = "SELECT content_id, title, image, created_at 
                FROM educational_content 
                WHERE category = ? AND content_id != ? AND status = 'active' 
                ORDER BY created_at DESC 
                LIMIT 3";
$stmt = $db->prepare($relatedQuery);
$stmt->bind_param('si', $article['category'], $articleId);
$stmt->execute();
$relatedResult = $stmt->get_result();
$relatedArticles = $relatedResult->fetch_all(MYSQLI_ASSOC);

// Update page title
$pageTitle = $article['title'] . ' - Pet Care Tips';
?>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/educational">Pet Care Tips</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($article['title']); ?></li>
                </ol>
            </nav>
            
            <article>
                <h1 class="mb-3"><?php echo htmlspecialchars($article['title']); ?></h1>
                
                <div class="mb-4">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($article['category']); ?></span>
                    <span class="text-muted ms-2">Published on <?php echo date('F d, Y', strtotime($article['created_at'])); ?></span>
                </div>
                
                <?php if (!empty($article['image'])): ?>
                <div class="mb-4">
                    <img src="<?php echo APP_URL; ?>/uploads/educational/<?php echo $article['image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($article['title']); ?>">
                </div>
                <?php endif; ?>
                
                <div class="lead mb-4">
                    <?php echo htmlspecialchars($article['summary']); ?>
                </div>
                
                <div class="article-content">
                    <?php echo $article['content']; ?>
                </div>
            </article>
            
            <div class="mt-5">
                <div class="card">
                    <div class="card-body">
                        <h5>Share this article</h5>
                        <div class="social-share">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(APP_URL . '/educational/article.php?id=' . $articleId); ?>" class="btn btn-outline-primary me-2" target="_blank">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(APP_URL . '/educational/article.php?id=' . $articleId); ?>&text=<?php echo urlencode($article['title']); ?>" class="btn btn-outline-info me-2" target="_blank">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode($article['title']); ?>&body=<?php echo urlencode('Check out this article: ' . APP_URL . '/educational/article.php?id=' . $articleId); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Related Articles</h5>
                </div>
                <div class="card-body">
                    <?php if (count($relatedArticles) > 0): ?>
                        <?php foreach ($relatedArticles as $related): ?>
                        <div class="mb-3">
                            <div class="row g-0">
                                <?php if (!empty($related['image'])): ?>
                                <div class="col-4">
                                    <img src="<?php echo APP_URL; ?>/uploads/educational/<?php echo $related['image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($related['title']); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="<?php echo !empty($related['image']) ? 'col-8' : 'col-12'; ?>">
                                    <div class="card-body py-0">
                                        <h6 class="card-title">
                                            <a href="<?php echo APP_URL; ?>/educational/article.php?id=<?php echo $related['content_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($related['title']); ?>
                                            </a>
                                        </h6>
                                        <p class="card-text"><small class="text-muted"><?php echo date('M d, Y', strtotime($related['created_at'])); ?></small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="mb-0">No related articles found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Need Professional Help?</h5>
                </div>
                <div class="card-body">
                    <p>Our professional pet care providers are ready to help with all your pet needs.</p>
                    <a href="<?php echo APP_URL; ?>/services" class="btn btn-primary">Browse Services</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>