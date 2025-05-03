<?php
try {
    $conn = new PDO("mysql:host=localhost;dbname=insta_lite", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query
    $stmt = $conn->prepare("SELECT id, username, verification_token, verified FROM users WHERE username = ?");
    $stmt->execute(['alexm_vie']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Database connection successful!\n";
        echo "User found: " . $user['username'] . "\n";
        echo "Verification Token: " . ($user['verification_token'] ?? 'None') . "\n";
        echo "Verified: " . ($user['verified'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "No user found with that username\n";
    }
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
