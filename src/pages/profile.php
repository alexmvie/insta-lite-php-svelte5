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

// Get the username from URL
$username = isset($_GET['username']) ? $_GET['username'] : '';

if (empty($username)) {
    header('Location: /');
    exit;
}

// Get the database connection
$db = (new Database())->getConnection();

// Get user info
try {
    $stmt = $db->prepare("SELECT id, username, profile_picture, bio, created_at FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: /404');
        exit;
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    header('Location: /404');
    exit;
}

// Initialize posts array
$posts = [];

// Get posts for this user
try {
    // Check if the posts table exists
    $check = $db->query("SHOW TABLES LIKE 'posts'");
    if ($check && $check->rowCount() > 0) {
        $stmt = $db->prepare("
            SELECT 
                p.*, 
                u.username, 
                u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE u.username = ?
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$username]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Profile posts error: " . $e->getMessage());
    // Keep posts as empty array
}

// Get follower/following counts
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM follows WHERE followed_user_id = ?");
    $stmt->execute([$user['id']]);
    $followers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM follows WHERE following_user_id = ?");
    $stmt->execute([$user['id']]);
    $following = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    error_log("Profile counts error: " . $e->getMessage());
    $followers = 0;
    $following = 0;
}

// Check if current user follows this user (if logged in)
$isFollowing = false;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM follows 
            WHERE following_user_id = ? AND followed_user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $user['id']]);
        $isFollowing = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    } catch (Exception $e) {
        error_log("Follow check error: " . $e->getMessage());
    }
}

// Initialize variables
$user['post_count'] = count($posts);
$user['follower_count'] = $followers;
$user['following_count'] = $following;

// Get current user ID if logged in
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// We already have the posts from earlier, no need to fetch them again
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
                        <p class="text-gray-600 text-sm mb-2">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                        
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
                        <!-- Action Buttons -->
                        <div class="mt-4">
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user['id']): ?>
                                <!-- Follow/Unfollow button for other users -->
                                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1 rounded-full text-sm">
                                    <?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?>
                                </button>
                            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']): ?>
                                <!-- Edit Profile button for own profile -->
                                <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-1 rounded-full text-sm mr-2">
                                    Edit Profile
                                </button>
                                <a href="/logout" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Logout
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Posts -->
            <h2 class="text-lg font-bold mb-3 mt-4">Posts</h2>
            <!-- User Posts -->
            <div class="posts-grid mt-4">
                <?php 
                // Use the timeline component with the username filter
                $username = $user['username']; // Pass the username to the timeline component
                $limit = 12; // Show more posts on profile
                $show_load_more = true;
                include __DIR__ . '/../components/timeline.php'; 
                ?>
            </div>
        </div>
    </div>
</body>
</html>
