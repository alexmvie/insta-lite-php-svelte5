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

// Get parameters from query
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 3; // Default to 3 posts per batch

// Validate parameters
if ($offset < 0) $offset = 0;
if ($limit <= 0 || $limit > 10) $limit = 3; // Limit to maximum 10 posts per request

// Get current user ID
$current_user_id = $_SESSION['user_id'];

try {
    // Get posts with pagination
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
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total posts for pagination info
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM posts");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Prepare response
    $response = [
        'posts' => [],
        'pagination' => [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ]
    ];
    
    // Process posts
    foreach ($posts as $post) {
        // Render each post using the post-card component
        ob_start();
        // We need to include the post-card component in a way that it has access to the $post variable
        extract($post); // Extract the $post array into variables
        include __DIR__ . '/../components/post-card.php';
        $post_html = ob_get_clean();
        
        $response['posts'][] = [
            'id' => $post['id'],
            'html' => $post_html
        ];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error fetching posts']);
    error_log("Posts API error: " . $e->getMessage());
}
