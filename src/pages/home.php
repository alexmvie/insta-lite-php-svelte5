<?php
// Start a new session or resume the existing one
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Get current user data
require_once APP_ROOT . '/src/config/database.php';
$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("SELECT id, username, profile_picture, bio FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found in database, clear session and redirect to login
        session_destroy();
        header('Location: /login');
        exit;
    }
} catch(PDOException $e) {
    error_log("Index Error: " . $e->getMessage());
    $error = "An error occurred. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insta-Lite - Home</title>
    <script src="https://unpkg.com/htmx.org@1.9.16"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once APP_ROOT . '/src/components/navbar.php'; ?>
    
    <div class="min-h-screen pt-16">
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h1 class="text-2xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                    <p class="mb-6">You are now logged in to Insta-Lite.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold mb-2">Your Account</h2>
                            <ul class="space-y-2">
                                <li><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></li>
                                <li><strong>Status:</strong> <span class="text-green-600">Verified</span></li>
                            </ul>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold mb-2">Quick Links</h2>
                            <ul class="space-y-2">
                                <li><a href="/debug/mail" class="text-blue-600 hover:text-blue-800">Debug Mail</a></li>
                                <li><a href="/debug/session" class="text-blue-600 hover:text-blue-800">Session Debug</a></li>
                                <li><a href="/logout" class="text-blue-600 hover:text-blue-800">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('app', () => ({
                posts: [],
                error: '',

                init() {
                    // Initialize dashboard
                    console.log('Dashboard initialized');
                }
            }));
        });
    </script>
</body>
</html>
