<?php
/**
 * Reusable Timeline Component
 * 
 * This component displays a timeline of posts with optional filtering.
 * 
 * Parameters:
 * - $user_id (optional): Filter posts by a specific user ID
 * - $limit (optional): Number of posts to display (default: 10)
 * - $offset (optional): Offset for pagination (default: 0)
 * - $show_load_more (optional): Whether to show the load more button (default: true)
 */

// Ensure we have a database connection
if (!isset($db) || !$db) {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
}

// Default parameters
$user_id = isset($user_id) ? $user_id : null;
$username = isset($username) ? $username : null;
$limit = isset($limit) ? $limit : 10;
$offset = isset($offset) ? $offset : 0;
$show_load_more = isset($show_load_more) ? $show_load_more : true;

// Get current user ID if logged in
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Initialize posts array
$posts = [];
$has_more = false;

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
        // Build the query based on filters
        $query = "
            SELECT 
                p.*, 
                u.username, 
                u.profile_picture,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count,
                (SELECT COUNT(*) > 0 FROM likes WHERE post_id = p.id AND user_id = :current_user_id) AS is_liked_by_current_user
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
        ";
        
        // Add WHERE clause if filtering by user
        if ($user_id) {
            $query .= " WHERE p.user_id = :user_id ";
        } elseif ($username) {
            $query .= " WHERE u.username = :username ";
        }
        
        // Add ORDER BY and LIMIT
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        // Prepare and execute the query
        $stmt = $db->prepare($query);
        $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
        
        if ($user_id) {
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        } elseif ($username) {
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if there are more posts
        if (count($posts) == $limit) {
            // Get one more post to check if there are more
            $check_query = $query;
            $check_query = str_replace("LIMIT :limit", "LIMIT 1", $check_query);
            $check_query = str_replace("OFFSET :offset", "OFFSET " . ($offset + $limit), $check_query);
            
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
            
            if ($user_id) {
                $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            } elseif ($username) {
                $check_stmt->bindParam(':username', $username, PDO::PARAM_STR);
            }
            
            $check_stmt->execute();
            $has_more = $check_stmt->rowCount() > 0;
        }
    }
} catch (Exception $e) {
    error_log("Timeline component error: " . $e->getMessage());
}

// Generate a unique ID for this timeline instance
$timeline_id = 'timeline-' . uniqid();
?>

<!-- Timeline Component -->
<div class="timeline-container">
    <!-- Posts List -->
    <div id="<?php echo $timeline_id; ?>-posts" class="grid grid-cols-1 gap-4 w-full">
        <?php if (empty($posts)): ?>
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">No posts to display.</p>
                <?php if (!$user_id && !$username && isset($_SESSION['user_id'])): ?>
                    <a href="/create-post" class="mt-3 inline-block bg-blue-500 text-white px-4 py-2 rounded-full text-sm">Create your first post</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/post-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($show_load_more && $has_more): ?>
        <!-- Load More Button -->
        <div class="flex justify-center mt-4">
            <button 
                id="load-more-<?php echo $timeline_id; ?>"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-full inline-flex items-center"
            >
                <span id="load-more-text-<?php echo $timeline_id; ?>">Load More</span>
                <span id="loading-indicator-<?php echo $timeline_id; ?>" class="hidden inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                </span>
            </button>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loadMoreBtn = document.getElementById('load-more-<?php echo $timeline_id; ?>');
                const loadingIndicator = document.getElementById('loading-indicator-<?php echo $timeline_id; ?>');
                const loadMoreText = document.getElementById('load-more-text-<?php echo $timeline_id; ?>');
                const postsContainer = document.getElementById('<?php echo $timeline_id; ?>-posts');
                
                let offset = <?php echo $offset + count($posts); ?>;
                let loading = false;
                let hasMore = <?php echo $has_more ? 'true' : 'false'; ?>;
                
                loadMoreBtn.addEventListener('click', async function() {
                    if (loading || !hasMore) return;
                    
                    loading = true;
                    loadMoreText.classList.add('hidden');
                    loadingIndicator.classList.remove('hidden');
                    
                    // Build the URL with parameters
                    let url = '/api/posts.php?limit=<?php echo $limit; ?>&offset=' + offset;
                    <?php if ($user_id): ?>
                    url += '&user_id=<?php echo $user_id; ?>';
                    <?php endif; ?>
                    <?php if ($username): ?>
                    url += '&username=<?php echo urlencode($username); ?>';
                    <?php endif; ?>
                    
                    try {
                        const response = await fetch(url);
                        const data = await response.json();
                        
                        if (data.success && data.posts && data.posts.length > 0) {
                            // Process and append new posts
                            data.posts.forEach(post => {
                                // Create a temporary container
                                const tempDiv = document.createElement('div');
                                
                                // Set the post data as a global variable temporarily
                                window.currentPost = post;
                                
                                // Fetch the post card HTML
                                fetch('/api/render-post-card.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify(post)
                                })
                                .then(response => response.text())
                                .then(html => {
                                    tempDiv.innerHTML = html;
                                    postsContainer.appendChild(tempDiv.firstElementChild);
                                });
                            });
                            
                            offset += data.posts.length;
                            hasMore = data.has_more;
                        } else {
                            hasMore = false;
                            loadMoreBtn.disabled = true;
                            loadMoreBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    } catch (error) {
                        console.error('Error loading more posts:', error);
                    } finally {
                        loading = false;
                        loadMoreText.classList.remove('hidden');
                        loadingIndicator.classList.add('hidden');
                        
                        if (!hasMore) {
                            loadMoreBtn.disabled = true;
                            loadMoreBtn.classList.add('opacity-50', 'cursor-not-allowed');
                            loadMoreText.textContent = 'No more posts';
                        }
                    }
                });
            });
        </script>
    <?php endif; ?>
    <div id="<?php echo $timeline_id; ?>-posts" class="grid grid-cols-1 gap-4 w-full">
        <?php if (empty($posts)): ?>
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">No posts to display.</p>
                <?php if (!$user_id && !$username && isset($_SESSION['user_id'])): ?>
                    <a href="/create-post" class="mt-3 inline-block bg-blue-500 text-white px-4 py-2 rounded-full text-sm">Create your first post</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/post-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($show_load_more && $has_more): ?>
        <!-- Load More Button -->
        <div class="flex justify-center mt-4">
            <button 
                @click="loadMorePosts()" 
                x-show="hasMore"
                x-bind:disabled="loading"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-full inline-flex items-center"
            >
                <span x-show="!loading">Load More</span>
                <span x-show="loading" class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                </span>
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Intersection Observer for Infinite Scroll -->
    <div 
        x-data="{}"
        x-init="
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        $el.dispatchEvent(new CustomEvent('loadMore'));
                    }
                });
            }, { rootMargin: '100px' });
            
            observer.observe($el);
            
            $el.addEventListener('loadMore', () => {
                if (!$parent.loading && $parent.hasMore) {
                    $parent.loadMorePosts();
                }
            });
        "
        class="h-10 w-full"
    ></div>
</div>
