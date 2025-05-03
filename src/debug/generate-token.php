<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';

// If user is already logged in, redirect to home
if (Auth::isLoggedIn()) {
    header('Location: /');
    exit;
}

// Get user ID from URL
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if ($user_id) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Generate a new verification token
        $token = bin2hex(random_bytes(32));
        
        // Update user with new token
        $stmt = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
        $stmt->execute([$token, $user_id]);
        
        // Get updated user info
        $stmt = $conn->prepare("SELECT id, username, verification_token FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $verification_link = "http://localhost:8000/verify.php?token=" . $user['verification_token'];
            
            // Show success message and verification link
            $message = "Successfully generated new verification token for " . htmlspecialchars($user['username']) . "!";
            $verification_link = $verification_link;
        }
    } catch(PDOException $e) {
        error_log("Token Generation Error: " . $e->getMessage());
        $error = "An error occurred while generating verification token.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Verification Token - Insta-Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Generate Verification Token</h1>
            
            <?php if (isset($error)): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($message)): ?>
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                
                <div class="mb-4 p-4 bg-blue-100 text-blue-700 rounded-lg">
                    <p><strong>Verification Link:</strong> <a href="<?php echo htmlspecialchars($verification_link); ?>" class="text-blue-600 hover:text-blue-500">Click here to verify</a></p>
                </div>
            <?php endif; ?>

            <form method="GET" class="space-y-4">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">User ID</label>
                    <input type="text" name="user_id" id="user_id" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button type="submit" 
                        class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Generate Token
                </button>
            </form>
        </div>
    </div>
</body>
</html>
