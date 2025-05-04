<?php
// Check if APP_ROOT is defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__DIR__)));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Get the database connection
$db = (new Database())->getConnection();

// Get posts from all users, sorted by date
$posts = [];
try {
    // Check if posts table exists
    $tableExists = false;
    try {
        $check = $db->query("SHOW TABLES LIKE 'posts'");
        $tableExists = ($check->rowCount() > 0);
    } catch (Exception $e) {
        // Table doesn't exist
    }
    
    if ($tableExists) {
        $stmt = $db->prepare("
            SELECT 
                p.*, 
                u.username, 
                u.profile_picture 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT 20
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Handle error silently
    error_log("Timeline error: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insta-Lite Timeline</title>
    <script src="https://unpkg.com/htmx.org@1.9.16"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="min-h-screen pt-16 pb-16 md:pb-0">
        <!-- Mobile-friendly container with max width similar to a phone -->
        <div class="max-w-md mx-auto px-4 py-4">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Timeline</h1>
                <a href="/create-post" class="bg-blue-500 text-white px-4 py-2 rounded-full hover:bg-blue-600 text-sm">+ New Post</a>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <?php foreach ($posts as $post): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100">
                        <div class="p-3">
                            <div class="flex items-center mb-3">
                                <img src="<?php 
                                    // Use fun avatar if profile picture is not set or is default
                                    if (empty($post['profile_picture']) || $post['profile_picture'] == 'default-avatar.png') {
                                        echo 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($post['username']);
                                    } else {
                                        echo htmlspecialchars($post['profile_picture']);
                                    }
                                ?>" 
                                     alt="Profile Picture" 
                                     class="w-8 h-8 rounded-full mr-2">
                                <div>
                                    <h3 class="font-semibold text-sm">
                                        <?php echo htmlspecialchars($post['username']); ?>
                                    </h3>
                                    <p class="text-xs text-gray-500">
                                        <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($post['image_url']) && $post['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                 alt="Post Image" 
                                 class="w-full h-auto max-h-96 object-cover">
                        <?php endif; ?>

                        <p class="px-3 py-2 text-sm">
                            <?php echo nl2br(htmlspecialchars($post['caption'])); ?>
                        </p>

                        <div class="px-3 py-2 flex justify-between items-center text-xs text-gray-500 border-t border-gray-100">
                            <div class="flex items-center space-x-4">
                                <button class="like-button flex items-center" 
                                        hx-post="/api/like" 
                                        hx-vals='{"id": "<?php echo $post['id']; ?>"}' 
                                        hx-target="this">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span class="ml-1">Like</span>
                                </button>
                                <span class="like-count text-xs">0</span>
                            </div>
                            <div class="flex items-center">
                                <button class="comment-button flex items-center" 
                                        hx-post="/api/comment" 
                                        hx-vals='{"id": "<?php echo $post['id']; ?>"}' 
                                        hx-target="this">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span class="ml-1">Comment</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4 mb-8">
            <button class="bg-gray-200 px-4 py-2 rounded-full text-sm" 
                    hx-get="/api/posts?limit=10&offset=10" 
                    hx-target=".grid"
                    hx-swap="afterend">
                Load More
            </button>
        </div>
    </div>
</body>
</html>
