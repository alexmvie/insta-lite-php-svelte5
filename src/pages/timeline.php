<?php
// Check if APP_ROOT is defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__DIR__)));
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Get the database connection
$db = (new Database())->getConnection();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get initial batch of posts (first 3)
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
        // Get current user ID if logged in
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        
        // Get only first 3 posts initially
        $stmt = $db->prepare("
            SELECT 
                p.*, 
                u.username, 
                u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :current_user_id) AS is_liked_by_current_user
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT 3
        ");
        $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();
        
        // Count total posts for pagination info
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM posts");
        $stmt->execute();
        $total_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
} catch (Exception $e) {
    // Handle error silently
    error_log("Timeline error: " . $e->getMessage());
}

// Session already started above

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

            <?php 
            // Use the timeline component with no filters to show all posts
            $limit = 10; // Show 10 posts initially
            $show_load_more = true;
            include __DIR__ . '/../components/timeline.php'; 
            ?>
        </div>
    </div>
</body>
</html>
