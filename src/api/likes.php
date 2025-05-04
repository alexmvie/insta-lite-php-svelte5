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

// Handle POST request to toggle like
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $user_id = $_SESSION['user_id'];
    
    if ($post_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid post ID']);
        exit;
    }
    
    try {
        // Check if user already liked the post
        $stmt = $db->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $existing_like = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_like) {
            // Unlike: Remove the like
            $stmt = $db->prepare("DELETE FROM likes WHERE id = :like_id");
            $stmt->bindParam(':like_id', $existing_like['id'], PDO::PARAM_INT);
            $stmt->execute();
            $action = 'unliked';
        } else {
            // Like: Add a new like
            $stmt = $db->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (:user_id, :post_id, NOW())");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->execute();
            $action = 'liked';
        }
        
        // Get updated like count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = :post_id");
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $like_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'action' => $action,
            'like_count' => $like_count,
            'like_text' => $like_count == 1 ? '1 like' : $like_count . ' likes'
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error processing like']);
        error_log("Likes API error: " . $e->getMessage());
        exit;
    }
}

// Get post_id from query parameters for GET requests
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
            <div class="flex items-center py-2 px-2 border-b border-gray-100 last:border-b-0 hover:bg-gray-50">
                <img src="<?php 
                    // Use fun avatar if profile picture is not set or is default
                    if (empty($like['profile_picture']) || $like['profile_picture'] == 'default-avatar.png') {
                        echo 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($like['username']);
                    } else {
                        echo htmlspecialchars($like['profile_picture']);
                    }
                ?>" 
                     alt="Profile Picture" 
                     class="w-8 h-8 rounded-full mr-3">
                <div>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($like['username']); ?></span>
                    <p class="text-xs text-gray-500"><?php echo date('M d', strtotime($like['created_at'] ?? 'now')); ?></p>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="py-4 text-center text-gray-500 text-sm">
            <svg class="w-6 h-6 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <p>No likes yet</p>
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
