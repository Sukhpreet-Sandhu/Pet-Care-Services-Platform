<?php require_once __DIR__ . '/config.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                    <i class="fas fa-paw me-2"></i><?php echo APP_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/services">Services</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/educational">Pet Care Tips</a>
                        </li>
                        <?php if (isLoggedIn() && getUserType() == 'pet_owner'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/pet_owner/bookings.php">My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/pet_owner/pets.php">My Pets</a>
                        </li>
                        <?php elseif (isLoggedIn() && getUserType() == 'service_provider'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/service_provider/services.php">My Services</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/service_provider/bookings.php">Bookings</a>
                        </li>
                        <?php elseif (isLoggedIn() && getUserType() == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/admin/dashboard.php">Admin Dashboard</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <?php if (getUserType() == 'pet_owner'): ?>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/pet_owner/profile.php">My Profile</a></li>
                                <?php elseif (getUserType() == 'service_provider'): ?>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/service_provider/dashboard.php">My Dashboard</a></li>
                                <?php elseif (getUserType() == 'admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/dashboard.php">My Profile</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/register.php">Register</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <?php
        $flashMessage = getFlashMessage();
        if ($flashMessage): 
        ?>
        <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flashMessage['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Navigation for pet owner - removed as requested -->