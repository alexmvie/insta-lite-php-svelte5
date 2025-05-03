<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';

// If user is already logged in, redirect to home
if (Auth::isLoggedIn()) {
    header('Location: /');
    exit;
}

// Get user ID from URL if provided
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

// Get all users for the list
$users = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, username, verified, verification_token FROM users ORDER BY username");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "An error occurred while fetching users.";
}

// If specific user ID is provided, show their details
if ($user_id) {
    try {
        $stmt = $conn->prepare("SELECT id, username, verified, verification_token FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $verification_link = $user['verification_token'] ? 
                "http://localhost:8000/verify.php?token=" . $user['verification_token'] : 
                null;
        } else {
            // If no user found, set error message
            $error = "User not found with ID: " . htmlspecialchars($user_id);
        }
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $error = "An error occurred while checking user status.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Verification - Insta-Lite</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Test Verification</h1>
            
            <?php if (isset($error)): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($user)): ?>
                <div class="space-y-4">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Verification Status:</strong> <?php echo $user['verified'] ? 'Verified' : 'Not Verified'; ?></p>
                    <p><strong>Verification Token:</strong> <?php echo $user['verification_token'] ? htmlspecialchars($user['verification_token']) : 'None'; ?></p>
                    
                    <?php if (!$user['verified'] && $verification_link): ?>
                        <p><strong>Verification Link:</strong> <a href="<?php echo htmlspecialchars($verification_link); ?>" class="text-blue-600 hover:text-blue-500">Click here to verify</a></p>
                    <?php elseif ($user['verified']): ?>
                        <p class="text-green-600">User is already verified</p>
                        <form method="GET" action="/generate-token.php" class="mt-4">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                            <button type="submit" 
                                    class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Generate New Verification Token
                            </button>
                        </form>
                    <?php else: ?>
                        <p>No verification link available</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mt-6">
                <h2 class="text-xl font-semibold mb-4">Users List</h2>
                <div class="space-y-2">
                    <?php foreach ($users as $user): ?>
                        <a href="?user_id=<?php echo htmlspecialchars($user['id']); ?>" 
                           class="block p-2 rounded-md hover:bg-gray-100 <?php echo isset($_GET['user_id']) && $_GET['user_id'] == $user['id'] ? 'bg-blue-100' : ''; ?>">
                            <div class="flex justify-between items-center">
                                <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                <span class="text-sm text-gray-600"><?php echo $user['verified'] ? 'Verified' : 'Not Verified'; ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
