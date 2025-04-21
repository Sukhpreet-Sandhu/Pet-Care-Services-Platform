// This is likely where your registerUser function is defined
// Let's add or modify the function to ensure accounts are active by default

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
            'message' => 'Email already exists'
        ];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Start transaction
    $db->query('SET autocommit = 0');
    $db->query('START TRANSACTION');
    
    try {
        // Insert user with active status
        $insertUserQuery = "INSERT INTO users (email, password, first_name, last_name, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($insertUserQuery);
        $stmt->bind_param("ssssss", $email, $hashedPassword, $firstName, $lastName, $phone, $userType);
        $stmt->execute();
        
        $userId = $db->insert_id;
        
        // Create specific user type record
        if ($userType === 'pet_owner') {
            $insertOwnerQuery = "INSERT INTO pet_owners (user_id) VALUES (?)";
            $stmt = $db->prepare($insertOwnerQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        } elseif ($userType === 'service_provider') {
            $insertProviderQuery = "INSERT INTO service_providers (user_id) VALUES (?)";
            $stmt = $db->prepare($insertProviderQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }
        
        // Commit transaction
        $db->query('COMMIT');
        
        return [
            'success' => true,
            'user_id' => $userId
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->query('ROLLBACK');
        
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage()
        ];
    } finally {
        // Restore autocommit mode
        $db->query('SET autocommit = 1');
    }
}


function loginUser($email, $password) {
    global $db;
    
    // Get user by email
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
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Check if account is active
    if (!$user['is_active']) {
        return [
            'success' => false,
            'message' => 'Your account is not active. Please contact support.'
        ];
    }
    
    // Get user-specific ID based on role
    $roleId = null;
    if ($user['role'] === 'pet_owner') {
        $ownerQuery = "SELECT owner_id FROM pet_owners WHERE user_id = ?";
        $stmt = $db->prepare($ownerQuery);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $ownerResult = $stmt->get_result();
        
        if ($ownerResult->num_rows > 0) {
            $roleId = $ownerResult->fetch_assoc()['owner_id'];
        }
    } elseif ($user['role'] === 'service_provider') {
        $providerQuery = "SELECT provider_id FROM service_providers WHERE user_id = ?";
        $stmt = $db->prepare($providerQuery);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $providerResult = $stmt->get_result();
        
        if ($providerResult->num_rows > 0) {
            $roleId = $providerResult->fetch_assoc()['provider_id'];
        }
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['role'] = $user['role'];
    
    if ($roleId) {
        if ($user['role'] === 'pet_owner') {
            $_SESSION['owner_id'] = $roleId;
        } elseif ($user['role'] === 'service_provider') {
            $_SESSION['provider_id'] = $roleId;
        }
    }
    
    return [
        'success' => true,
        'user_type' => $user['role']
    ];
}