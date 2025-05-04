<?php
/**
 * Post Card Component for Insta-Lite
 * 
 * This component displays a single post card with consistent styling.
 * 
 * @param array $post The post data to display
 */

// Include helper functions
require_once __DIR__ . '/../helpers/post_helpers.php';

// Ensure $post is provided
if (!isset($post)) {
    return;
}
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 w-full mb-4" x-data="{
    showLikes: false,
    showComments: false,
    newComment: '',
    isLiked: <?php echo isset($post['is_liked_by_current_user']) && $post['is_liked_by_current_user'] ? 'true' : 'false'; ?>,
    likeCount: <?php echo $post['like_count']; ?>,
    likeText: '<?php echo $post['like_count'] == 1 ? '1 like' : $post['like_count'] . ' likes'; ?>',
    async loadLikes(postId) {
        const response = await fetch(`/api/likes.php?post_id=${postId}`);
        if (response.ok) {
            const html = await response.text();
            document.getElementById(`likes-container-${postId}`).innerHTML = html;
        }
    },
    async loadComments(postId) {
        const response = await fetch(`/api/comments.php?post_id=${postId}`);
        if (response.ok) {
            const html = await response.text();
            document.getElementById(`comments-container-${postId}`).innerHTML = html;
        }
    },
    async toggleLike(postId) {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('/api/likes.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                this.isLiked = data.action === 'liked';
                this.likeCount = data.like_count;
                this.likeText = data.like_text;
                
                // If likes panel is open, refresh it
                if (this.showLikes) {
                    this.loadLikes(postId);
                }
            }
        }
    },
    async submitComment(postId) {
        if (this.newComment.trim() === '') return;
        
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('comment', this.newComment);
        
        const response = await fetch('/api/comments.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            this.newComment = '';
            this.loadComments(postId);
        }
    }
}">
    <!-- Post Header -->
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

    <!-- Post Image -->
    <?php if (isset($post['image_path']) && $post['image_path']): ?>
        <div class="w-full h-96 flex items-center justify-center bg-gray-50">
            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                alt="Post Image" 
                class="max-h-full max-w-full object-contain">
        </div>
    <?php endif; ?>

    <!-- Post Caption -->
    <p class="px-3 py-2 text-sm">
        <?php echo nl2br(htmlspecialchars($post['caption'])); ?>
    </p>

    <!-- Likes and comments counts -->
    <div class="px-4 py-2 flex justify-between text-sm">
        <div>
            <button @click="showLikes = !showLikes; if(showLikes) loadLikes(<?php echo $post['id']; ?>)" class="text-gray-600 hover:text-gray-900 hover:underline font-medium">
                <span x-text="likeText"></span>
            </button>
        </div>
        <div>
            <button @click="showComments = !showComments; if(showComments) loadComments(<?php echo $post['id']; ?>)" class="text-gray-600 hover:text-gray-900 hover:underline font-medium">
                <?php echo $post['comment_count']; ?> <?php echo $post['comment_count'] == 1 ? 'comment' : 'comments'; ?>
            </button>
        </div>
    </div>

    <!-- Post Footer -->
    <div class="px-4 py-3 flex space-x-6 border-t">
        <button @click="toggleLike(<?php echo $post['id']; ?>)" class="flex items-center space-x-1.5 transition-colors" :class="isLiked ? 'text-red-500 hover:text-red-600' : 'text-gray-600 hover:text-red-500'">
            <svg class="w-5 h-5" :fill="isLiked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
            </svg>
            <span class="font-medium" x-text="isLiked ? 'Liked' : 'Like'"></span>
        </button>
        <button @click="showComments = !showComments; if(showComments) loadComments(<?php echo $post['id']; ?>)" class="flex items-center space-x-1.5 text-gray-600 hover:text-blue-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <span class="font-medium">Comment</span>
        </button>
    </div>

    <!-- Likes Panel -->
    <div x-show="showLikes" class="px-4 py-3 border-t bg-gray-50" style="display: none;">
        <h4 class="font-semibold text-sm mb-3 text-gray-700">Likes</h4>
        <div id="likes-container-<?php echo $post['id']; ?>" class="max-h-48 overflow-y-auto rounded-md">
            <div class="text-center py-4">
                <div class="animate-spin inline-block w-6 h-6 border-2 border-gray-300 border-t-blue-500 rounded-full"></div>
                <p class="text-sm text-gray-500 mt-2">Loading likes...</p>
            </div>
        </div>
    </div>

    <!-- Comments Panel -->
    <div x-show="showComments" class="px-4 py-3 border-t bg-gray-50" style="display: none;">
        <h4 class="font-semibold text-sm mb-3 text-gray-700">Comments</h4>
        <div id="comments-container-<?php echo $post['id']; ?>" class="max-h-64 overflow-y-auto rounded-md">
            <div class="text-center py-4">
                <div class="animate-spin inline-block w-6 h-6 border-2 border-gray-300 border-t-blue-500 rounded-full"></div>
                <p class="text-sm text-gray-500 mt-2">Loading comments...</p>
            </div>
        </div>

        <!-- Comment Form -->
        <form x-on:submit.prevent="submitComment(<?php echo $post['id']; ?>)" class="mt-3 flex">
            <input type="text" x-model="newComment" placeholder="Add a comment..." class="flex-1 border rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 transition-colors text-white rounded-r-lg px-4 py-2 text-sm font-medium">Post</button>
        </form>
    </div>
</div>
