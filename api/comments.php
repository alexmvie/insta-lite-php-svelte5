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

// Handle POST request to add a new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $comment_text = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $user_id = $_SESSION['user_id'];
    
    if ($post_id <= 0 || empty($comment_text)) {
        echo '<div class="py-2 text-center text-red-500 text-sm">Invalid comment data</div>';
        exit;
    }
    
    try {
        // Insert the new comment
        $stmt = $db->prepare("
            INSERT INTO comments (post_id, user_id, comment, created_at)
            VALUES (:post_id, :user_id, :comment, NOW())
        ");
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':comment', $comment_text, PDO::PARAM_STR);
        $stmt->execute();
        
        // Get all comments for the post to refresh the list
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    } catch (Exception $e) {
        echo '<div class="py-2 text-center text-red-500 text-sm">Error adding comment</div>';
        error_log("Comments API error: " . $e->getMessage());
        exit;
    }
}

// Get post_id from query parameters for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
}

if ($post_id <= 0) {
    echo '<div class="py-2 text-center text-red-500 text-sm">Invalid post ID</div>';
    exit;
}

try {
    // Get comments for the post
    $stmt = $db->prepare("
        SELECT 
            c.*,
            u.username,
            u.profile_picture
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = :post_id
        ORDER BY c.created_at ASC
    ");
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return HTML for the comments
    if (count($comments) > 0) {
        foreach ($comments as $comment) {
            ?>
            <div class="flex items-start py-2 border-b border-gray-100 last:border-b-0">
                <img src="<?php 
                    // Use fun avatar if profile picture is not set or is default
                    if (empty($comment['profile_picture']) || $comment['profile_picture'] == 'default-avatar.png') {
                        echo 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($comment['username']);
                    } else {
                        echo htmlspecialchars($comment['profile_picture']);
                    }
                ?>" 
                     alt="Profile Picture" 
                     class="w-6 h-6 rounded-full mr-2 mt-1">
                <div>
                    <div class="flex items-baseline">
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($comment['username']); ?></span>
                        <span class="text-xs text-gray-500 ml-2">
                            <?php echo date('M d, g:i a', strtotime($comment['created_at'])); ?>
                        </span>
                    </div>
                    <p class="text-sm mt-1"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="py-2 text-center text-gray-500 text-sm">
            No comments yet
        </div>
        <?php
    }
} catch (Exception $e) {
    ?>
    <div class="py-2 text-center text-red-500 text-sm">
        Error loading comments
    </div>
    <?php
    error_log("Comments API error: " . $e->getMessage());
}
?>
