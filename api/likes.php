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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the database connection
$db = (new Database())->getConnection();

// Get post_id from query parameters
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if ($post_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid post ID']);
    exit;
}

try {
    // Get likes for the post
    $stmt = $db->prepare("
        SELECT 
            l.*,
            u.username,
            u.profile_picture
        FROM likes l
        JOIN users u ON l.user_id = u.id
        WHERE l.post_id = :post_id
        ORDER BY l.created_at DESC
    ");
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return HTML for the likes
    if (count($likes) > 0) {
        foreach ($likes as $like) {
            ?>
            <div class="flex items-center py-1 border-b border-gray-100 last:border-b-0">
                <img src="<?php 
                    // Use fun avatar if profile picture is not set or is default
                    if (empty($like['profile_picture']) || $like['profile_picture'] == 'default-avatar.png') {
                        echo 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($like['username']);
                    } else {
                        echo htmlspecialchars($like['profile_picture']);
                    }
                ?>" 
                     alt="Profile Picture" 
                     class="w-6 h-6 rounded-full mr-2">
                <span class="text-sm font-medium"><?php echo htmlspecialchars($like['username']); ?></span>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="py-2 text-center text-gray-500 text-sm">
            No likes yet
        </div>
        <?php
    }
} catch (Exception $e) {
    ?>
    <div class="py-2 text-center text-red-500 text-sm">
        Error loading likes
    </div>
    <?php
    error_log("Likes API error: " . $e->getMessage());
}
?>
