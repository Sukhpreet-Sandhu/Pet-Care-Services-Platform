<?php
require_once 'config.php';
require_once 'db.php';

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user type
 * @return string User type (pet_owner, service_provider, admin)
 */
function getUserType() {
    return $_SESSION['user_type'] ?? ($_SESSION['role'] ?? '');
}

/**
 * Check if user has access to a specific role page
 * @param string $requiredRole The role required to access the page
 * @return void Redirects with error if access is denied
 */
function checkAccess($requiredRole) {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please log in to access this page');
        redirect(APP_URL . '/login.php');
    }
    
    $userType = getUserType();
    
    if ($userType !== $requiredRole) {
        setFlashMessage('error', 'You do not have permission to access this page');
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Login user with email and password
 * @param string $email User email
 * @param string $password User password
 * @return array Result with success flag and message
 */
function loginUser($email, $password) {
    global $db;
    
    // Use prepared statement instead of real_escape_string
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    $user = $result->fetch_assoc();
    
    // Rest of the function remains the same
    if ($user['status'] !== 'active') {
        return [
            'success' => false,
            'message' => 'Your account is not active. Please contact support.'
        ];
    }
    
    if (!password_verify($password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Set session data
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['role'] = $user['user_type']; // For compatibility
    
    // Get additional data based on user type
    if ($user['user_type'] === 'pet_owner') {
        $ownerQuery = "SELECT owner_id FROM pet_owners WHERE user_id = {$user['user_id']}";
        $ownerResult = $db->query($ownerQuery);
        
        if ($ownerResult->num_rows > 0) {
            $owner = $ownerResult->fetch_assoc();
            $_SESSION['owner_id'] = $owner['owner_id'];
        }
    } elseif ($user['user_type'] === 'service_provider') {
        $providerQuery = "SELECT provider_id FROM service_providers WHERE user_id = {$user['user_id']}";
        $providerResult = $db->query($providerQuery);
        
        if ($providerResult->num_rows > 0) {
            $provider = $providerResult->fetch_assoc();
            $_SESSION['provider_id'] = $provider['provider_id'];
        }
    }
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user_type' => $user['user_type']
    ];
}

/**
 * Register a new user
 * 
 * @param string $email User's email address
 * @param string $password User's password (will be hashed)
 * @param string $userType User type (pet_owner or service_provider)
 * @param string $firstName User's first name
 * @param string $lastName User's last name
 * @param string $phone User's phone number (optional)
 * @return array Result with success flag and message
 */
function registerUser($email, $password, $userType, $firstName, $lastName, $phone = '') {
    global $db;
    
    // Check if email already exists
    $checkQuery = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $db->prepare($checkQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'Email address is already registered'
        ];
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    try {
        // Insert into users table
        $query = "INSERT INTO users (email, password, user_type, first_name, last_name, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssssss", $email, $hashedPassword, $userType, $firstName, $lastName, $phone);
        $stmt->execute();
        
        $userId = $db->getLastInsertId();
        
        // Insert into specific user type table
        if ($userType === 'pet_owner') {
            $query = "INSERT INTO pet_owners (user_id) VALUES (?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        } elseif ($userType === 'service_provider') {
            $query = "INSERT INTO service_providers (user_id) VALUES (?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }
        
        // Commit the transaction
        $db->getConnection()->commit();
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $userId
        ];
    } catch (Exception $e) {
        // Rollback on error
        $db->getConnection()->rollback();
        
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Log out the current user by destroying the session
 */
function logout() {
    // Start the session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // If a session cookie is used, clear it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Set a flash message to be displayed on the next page
 * @param string $type Message type (success, error, info, warning)
 * @param string $message The message to display
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear the current flash message
 * @return array|null The flash message or null if none set
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Redirect to a specific URL
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Format a number as currency
 * @param float $amount The amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return '$' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Format a date in a user-friendly format
 * @param string $date The date to format (YYYY-MM-DD)
 * @return string Formatted date (e.g., "Apr 9, 2025")
 */
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Format a datetime in a user-friendly format
 * @param string $datetime The datetime to format
 * @return string Formatted datetime (e.g., "Apr 9, 2025, 3:45 PM")
 */
function formatDateTime($datetime) {
    return date('M j, Y, g:i A', strtotime($datetime));
}

/**
 * Format a datetime as a relative time ago string
 * @param string $datetime The datetime to format
 * @return string Formatted time ago (e.g., "2 hours ago")
 */
function formatTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' ' . ($minutes == 1 ? 'minute' : 'minutes') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . ($hours == 1 ? 'hour' : 'hours') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ' . ($days == 1 ? 'day' : 'days') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' ' . ($weeks == 1 ? 'week' : 'weeks') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Get count of unread notifications for a user
 * @param int $userId User ID
 * @return int Count of unread notifications
 */
function getUnreadNotificationCount($userId) {
    global $db;
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $userId AND is_read = 0";
    $result = $db->query($query);
    
    return $result->fetch_assoc()['count'];
}

/**
 * Create a notification for new booking
 * @param int $userId User ID to notify
 * @param int $bookingId Booking ID
 * @param string $serviceTitle Service title
 * @param string $bookingDate Booking date
 * @return bool Success flag
 */
function createBookingNotification($userId, $bookingId, $serviceTitle, $bookingDate) {
    global $db;
    
    $title = 'New Booking';
    $message = "A new booking for '$serviceTitle' has been made for $bookingDate.";
    $type = 'new_booking';
    
    return createNotification($userId, $title, $message, $type, $bookingId);
}

/**
 * Create a notification for a booking cancellation
 * @param int $userId User ID to notify
 * @param int $bookingId Booking ID
 * @param string $serviceTitle Service title
 * @param string $bookingDate Booking date
 * @return bool Success flag
 */
function createCancellationNotification($userId, $bookingId, $serviceTitle, $bookingDate) {
    global $db;
    
    $title = 'Booking Cancelled';
    $message = "The booking for '$serviceTitle' on $bookingDate has been cancelled.";
    $type = 'booking_cancelled';
    
    return createNotification($userId, $title, $message, $type, $bookingId);
}

/**
 * Create a reminder notification for upcoming booking
 * @param int $userId User ID to notify
 * @param int $bookingId Booking ID
 * @param string $serviceTitle Service title
 * @param string $bookingDate Booking date
 * @param string $startTime Start time
 * @return bool Success flag
 */
function createReminderNotification($userId, $bookingId, $serviceTitle, $bookingDate, $startTime) {
    global $db;
    
    $title = 'Appointment Reminder';
    $message = "Reminder: You have a booking for '$serviceTitle' tomorrow at $startTime.";
    $type = 'appointment_reminder';
    
    return createNotification($userId, $title, $message, $type, $bookingId);
}

/**
 * Create a generic notification
 * @param int $userId User ID to notify
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type
 * @param int|null $relatedId Related entity ID (optional)
 * @return bool Success flag
 */
function createNotification($userId, $title, $message, $type, $relatedId = null) {
    global $db;
    
    $title = $db->real_escape_string($title);
    $message = $db->real_escape_string($message);
    $type = $db->real_escape_string($type);
    
    $query = "
        INSERT INTO notifications (user_id, title, message, type, related_id, created_at)
        VALUES ($userId, '$title', '$message', '$type', " . ($relatedId ? $relatedId : "NULL") . ", NOW())
    ";
    
    return $db->query($query);
}

/**
 * Generate a random string
 * @param int $length Length of the string to generate
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Sanitize input data
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Upload a file to the server
 * 
 * @param array $file The $_FILES array element
 * @param string $folder The destination subfolder inside the uploads directory
 * @param array $allowedTypes Allowed mime types (default: images only)
 * @param int $maxSize Maximum file size in bytes (default: 5MB)
 * @return array Result with success flag, message, and file path
 */
function uploadFile($file, $folder, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $maxSize = 5242880) {
    // Check if uploads directory exists, create if not
    $uploadsDir = dirname(__DIR__) . '/uploads';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Create subfolder if it doesn't exist
    $targetDir = $uploadsDir . '/' . $folder;
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Validate file
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'message' => 'File is too large. Maximum size is ' . formatFileSize($maxSize)
        ];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Allowed types: ' . implode(', ', array_map('formatMimeType', $allowedTypes))
        ];
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetPath = $targetDir . '/' . $newFilename;
    $relativePath = $folder . '/' . $newFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_path' => $relativePath
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload file. Please try again.'
        ];
    }
}

/**
 * Format file size for human readability
 * 
 * @param int $bytes Size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Format mime type for display
 * 
 * @param string $mimeType The MIME type
 * @return string User-friendly file type
 */
function formatMimeType($mimeType) {
    $types = [
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG',
        'image/gif' => 'GIF',
        'image/webp' => 'WebP',
        'application/pdf' => 'PDF',
        'text/plain' => 'Text',
        'application/msword' => 'Word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word'
    ];
    
    return $types[$mimeType] ?? $mimeType;
}

/**
 * Ensure admin directory exists
 * @return void
 */
function ensureAdminDirectoryExists() {
    $adminDir = __DIR__ . '/../admin';
    if (!is_dir($adminDir)) {
        mkdir($adminDir, 0755, true);
    }
    
    // Make sure index.php exists in admin directory
    $indexFile = $adminDir . '/index.php';
    if (!file_exists($indexFile)) {
        $content = '<?php
require_once "../includes/init.php";
header("Location: " . APP_URL . "/admin/dashboard.php");
exit;
?>';
        file_put_contents($indexFile, $content);
    }
}

// Call this function during initialization
ensureAdminDirectoryExists();
?>