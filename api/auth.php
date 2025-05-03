<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Login endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['PATH_INFO'] === '/login') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        exit;
    }

    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($data['password'], $user['password_hash'])) {
            Auth::login($user['id']);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid username or password']);
        }
    } catch(PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

// Logout endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['PATH_INFO'] === '/logout') {
    Auth::logout();
    echo json_encode(['success' => true]);
    exit;
}

// Register endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['PATH_INFO'] === '/register') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, password, and email are required']);
        exit;
    }

    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
            exit;
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already exists']);
            exit;
        }

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $password_hash = hashPassword($data['password']);
        $stmt->execute([$data['username'], $data['email'], $password_hash]);

        // Login the user after registration
        $user_id = $conn->lastInsertId();
        Auth::login($user_id);
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

// Protected API routes (require authentication)
if (isset($_SERVER['PATH_INFO'])) {
    Auth::check(); // This will redirect to login if not authenticated
}

// Handle other API routes here
