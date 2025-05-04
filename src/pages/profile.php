<?php
// Check if APP_ROOT is defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__DIR__)));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Get the database connection
$db = (new Database())->getConnection();

// Initialize variables
$user = [];
$posts = [];

try {
    // Get basic user data first
    $stmt = $db->prepare("SELECT id, username, email, profile_picture, bio, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found, redirect to login
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    // Set default counts
    $user['post_count'] = 0;
    $user['follower_count'] = 0;
    $user['following_count'] = 0;
    
    // Check if posts table exists
    $postsTableExists = false;
    try {
        $check = $db->query("SHOW TABLES LIKE 'posts'");
        $postsTableExists = ($check->rowCount() > 0);
    } catch (Exception $e) {
        // Table doesn't exist
    }
    
    // Check if follows table exists
    $followsTableExists = false;
    try {
        $check = $db->query("SHOW TABLES LIKE 'follows'");
        $followsTableExists = ($check->rowCount() > 0);
    } catch (Exception $e) {
        // Table doesn't exist
    }
    
    // Get post count if table exists
    if ($postsTableExists) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $user['post_count'] = $result['count'] ?? 0;
        
        // Get user posts
        $stmt = $db->prepare("
            SELECT * FROM posts 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $posts = $stmt->fetchAll();
    }
    
    // Get follower and following counts if table exists
    if ($followsTableExists) {
        // Get follower count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $user['follower_count'] = $result['count'] ?? 0;
        
        // Get following count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        $user['following_count'] = $result['count'] ?? 0;
    }
} catch (Exception $e) {
    // Handle error silently
    error_log("Profile error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insta-Lite - Profile</title>
    <script src="https://unpkg.com/htmx.org@1.9.16"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="min-h-screen pt-16 pb-16 md:pb-0">
        <!-- Mobile-friendly container with max width similar to a phone -->
        <div class="max-w-md mx-auto px-4 py-4">
            <!-- Profile Header -->
            <div class="bg-white shadow rounded-lg p-4 mb-4 border border-gray-100">
                <div class="flex flex-col items-center">
                    <div class="mb-3">
                        <img src="<?php 
                            // Use fun avatar if profile picture is not set or is default
                            if (empty($user['profile_picture']) || $user['profile_picture'] == 'default-avatar.png') {
                                echo 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($user['username']);
                            } else {
                                echo htmlspecialchars($user['profile_picture']);
                            }
                        ?>" 
                             alt="Profile Picture" 
                             class="w-24 h-24 rounded-full object-cover border-2 border-gray-200">
                    </div>
                    <div class="text-center w-full">
                        <h1 class="text-xl font-bold"><?php echo htmlspecialchars($user['username']); ?></h1>
                        <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                        
                        <?php if (!empty($user['bio'])): ?>
                            <p class="text-gray-700 text-sm mb-3"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="flex justify-center space-x-6 text-sm">
                            <div class="text-center">
                                <div class="font-bold"><?php echo $user['post_count']; ?></div>
                                <div class="text-gray-500 text-xs">posts</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold"><?php echo $user['follower_count']; ?></div>
                                <div class="text-gray-500 text-xs">followers</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold"><?php echo $user['following_count']; ?></div>
                                <div class="text-gray-500 text-xs">following</div>
                            </div>
                        </div>
                        <!-- Logout Button -->
                        <div class="mt-4">
                            <a href="/logout" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Posts -->
            <h2 class="text-lg font-bold mb-3 mt-4">Posts</h2>
            
            <?php if (count($posts) > 0): ?>
                <div class="grid grid-cols-3 gap-1">
                    <?php foreach ($posts as $post): ?>
                        <a href="/post/<?php echo $post['id']; ?>" class="aspect-square overflow-hidden bg-gray-100 rounded">
                            <?php if (isset($post['image_url']) && $post['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                     alt="Post Image" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center p-2 text-xs text-center text-gray-500">
                                    <?php echo nl2br(htmlspecialchars(substr($post['caption'], 0, 50) . (strlen($post['caption']) > 50 ? '...' : ''))); ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white shadow rounded-lg p-4 text-center border border-gray-100">
                    <p class="text-gray-600 text-sm">No posts yet.</p>
                    <a href="/create-post" class="mt-2 inline-block bg-blue-500 text-white px-4 py-2 rounded-full text-sm">Create your first post</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
