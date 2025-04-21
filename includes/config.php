<?php
// Application configuration
define('APP_NAME', 'Pet Care Platform');
define('APP_URL', 'http://localhost/pet_care_platform');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/pet_care_platform/uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pet_care_platform');

// Session configuration
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>