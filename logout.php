<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log out the user
logout();

// Redirect to home page
setFlashMessage('success', 'You have been successfully logged out.');
redirect(APP_URL);
?>