<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Get parameters
$limit = $_GET['limit'] ?? 20;
$offset = $_GET['offset'] ?? 0;

// Get the database connection
$db = (new Database())->getConnection();

try {
    // Get posts from all users, sorted by date
    $stmt = $db->prepare("
        SELECT 
            p.*, 
            u.username, 
            u.profile_picture 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $posts = $stmt->fetchAll();
    
    // Generate HTML for posts
    $html = '';
    foreach ($posts as $post) {
        $html .= "<div class='bg-white rounded-lg shadow-md overflow-hidden'>";
        $html .= "    <div class='p-4'>";
        $html .= "        <div class='flex items-center mb-4'>";
        $html .= "            <img src='" . htmlspecialchars($post['profile_picture'] ?? 'default-avatar.png') . "'";
        $html .= "                 alt='Profile Picture'";
        $html .= "                 class='w-10 h-10 rounded-full mr-3'>";
        $html .= "            <div>";
        $html .= "                <h3 class='font-semibold'>" . htmlspecialchars($post['username']) . "</h3>";
        $html .= "                <p class='text-sm text-gray-500'>" . date('M d, Y', strtotime($post['created_at'])) . "</p>";
        $html .= "            </div>";
        $html .= "        </div>";

        if ($post['image_url']) {
            $html .= "        <img src='" . htmlspecialchars($post['image_url']) . "'";
            $html .= "             alt='Post Image'";
            $html .= "             class='w-full h-64 object-cover'>";
        }

        $html .= "        <p class='mt-4'>" . nl2br(htmlspecialchars($post['caption'])) . "</p>";
        
        $html .= "        <div class='mt-4 flex justify-between items-center text-sm text-gray-500'>";
        $html .= "            <div class='flex items-center space-x-2'>";
        $html .= "                <button class='like-button'";
        $html .= "                        hx-post='/api/like'";
        $html .= "                        hx-vals='{\"id\": \"" . $post['id'] . "\"}'";  
        $html .= "                        hx-target='this'>";
        $html .= "                    <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>";
        $html .= "                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'/></svg>";
        $html .= "                    <span class='ml-1'>Like</span>";
        $html .= "                </button>";
        $html .= "                <span class='like-count'>0</span>";
        $html .= "            </div>";
        $html .= "            <div class='flex items-center space-x-2'>";
        $html .= "                <button class='comment-button'";
        $html .= "                        hx-post='/api/comment'";
        $html .= "                        hx-vals='{\"id\": \"" . $post['id'] . "\"}'";  
        $html .= "                        hx-target='this'>";
        $html .= "                    <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>";
        $html .= "                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'/></svg>";
        $html .= "                    <span class='ml-1'>Comment</span>";
        $html .= "                </button>";
        $html .= "            </div>";
        $html .= "        </div>";
        $html .= "    </div>";
        $html .= "</div>";
    }
    
    echo $html;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
