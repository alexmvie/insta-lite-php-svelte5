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

            <div id="posts-container" x-data="{
                posts: [],
                offset: 0,
                limit: 3,
                loading: false,
                hasMore: <?php echo ($total_posts > 3) ? 'true' : 'false'; ?>,
                totalPosts: <?php echo $total_posts; ?>,
                init() {
                    // Initialize with the server-rendered posts
                    this.offset = <?php echo count($posts); ?>;
                    console.log('Initialized infinite scroll with offset:', this.offset);
                    
                    // Set up intersection observer for infinite scroll
                    this.$nextTick(() => {
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                console.log('Intersection observed:', entry.isIntersecting);
                                if (entry.isIntersecting && this.hasMore && !this.loading) {
                                    console.log('Loading more posts...');
                                    this.loadMorePosts();
                                }
                            });
                        }, { rootMargin: '200px', threshold: 0.1 });
                        
                        // Observe the loading indicator
                        if (this.$refs.loadingIndicator) {
                            observer.observe(this.$refs.loadingIndicator);
                            console.log('Observer attached to loading indicator');
                        } else {
                            console.error('Loading indicator reference not found');
                        }
                    });
                },
                async loadMorePosts() {
                    if (this.loading || !this.hasMore) return;
                    
                    this.loading = true;
                    console.log('Loading more posts from offset:', this.offset);
                    
                    try {
                        const response = await fetch(`/api/posts.php?offset=${this.offset}&limit=${this.limit}`);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        const data = await response.json();
                        console.log('Received data:', data);
                        
                        if (data.posts && data.posts.length > 0) {
                            // Append new posts to the container
                            const postsContainer = document.getElementById('posts-list');
                            data.posts.forEach(post => {
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = post.html;
                                const postElement = tempDiv.firstElementChild;
                                if (postElement) {
                                    postsContainer.appendChild(postElement);
                                    console.log('Appended post:', post.id);
                                } else {
                                    console.error('Failed to extract post element from HTML');
                                }
                            });
                            
                            // Update pagination info
                            this.offset += data.posts.length;
                            this.hasMore = data.pagination.has_more;
                            console.log('Updated offset to:', this.offset, 'hasMore:', this.hasMore);
                        } else {
                            this.hasMore = false;
                            console.log('No more posts available');
                        }
                    } catch (error) {
                        console.error('Error loading more posts:', error);
                    } finally {
                        this.loading = false;
                    }
                }
            }">
                <div id="posts-list" class="grid grid-cols-1 gap-4 w-full">
                    <?php foreach ($posts as $post): ?>
                        <?php include __DIR__ . '/../components/post-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Loading indicator and load more trigger -->
                <div x-ref="loadingIndicator" class="text-center py-8">
                    <template x-if="loading">
                        <div class="flex justify-center items-center space-x-2">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                            <span class="text-gray-500">Loading more posts...</span>
                        </div>
                    </template>
                    <template x-if="!loading && !hasMore && offset > 0">
                        <div class="text-gray-500 py-4">No more posts to load</div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
