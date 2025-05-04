<script lang="ts">
  import { onMount } from 'svelte';
  import { auth } from '$lib/stores/auth';
  import { API_BASE_URL, COMMENT_ENDPOINTS, LIKE_ENDPOINTS } from '$lib/config/api';
  
  export let post: {
    id: number;
    user_id: number;
    caption?: string;
    content?: string;
    created_at: string;
    username?: string;
    profile_picture?: string;
    like_count?: number;
    comment_count?: number;
    is_liked_by_current_user?: boolean;
    image_path?: string;
  };
  
  let isLiked = post.is_liked_by_current_user || false;
  let likeCount = post.like_count || 0;
  let commentCount = post.comment_count || 0;
  let isAuthenticated = false;
  let userId: number | null = null;
  let showLikes = false;
  let showComments = false;
  let newComment = '';
  let comments: any[] = [];
  let likes: any[] = [];
  let loadingComments = false;
  let loadingLikes = false;
  
  onMount(() => {
    // Check initial auth state first
    const initialState = auth.get();
    if (initialState) {
      isAuthenticated = initialState.isAuthenticated;
      userId = initialState.user?.id || null;
      console.log('Initial auth state:', { isAuthenticated, userId });
    }

    // Wait a bit to ensure auth state is fully initialized
    setTimeout(() => {
      const state = auth.get();
      if (state) {
        isAuthenticated = state.isAuthenticated;
        userId = state.user?.id || null;
        console.log('Auth state after timeout:', { isAuthenticated, userId });
      }
    }, 100);

    const unsubscribe = auth.subscribe((state) => {
      isAuthenticated = state.isAuthenticated;
      userId = state.user?.id || null;
      console.log('Auth state updated:', { isAuthenticated, userId });
    });
    
    return unsubscribe;
  });
  
  async function toggleLike() {
    if (!isAuthenticated) return;
    
    try {
      const response = await fetch(isLiked ? LIKE_ENDPOINTS.DELETE(post.id) : LIKE_ENDPOINTS.CREATE, {
        method: isLiked ? 'DELETE' : 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...auth.getAuthHeader()
        },
        body: JSON.stringify({
          post_id: post.id,
          user_id: userId
        })
      });
      
      if (response.ok) {
        isLiked = !isLiked;
        likeCount = isLiked ? likeCount + 1 : likeCount - 1;
        
        // Update the post object to reflect changes
        post.is_liked_by_current_user = isLiked;
        post.like_count = likeCount;
        
        // If likes panel is open, refresh it
        if (showLikes) {
          loadLikes();
        }
      }
    } catch (error) {
      console.error('Error toggling like:', error);
    }
  }
  
  async function loadLikes() {
    if (!isAuthenticated) return;
    loadingLikes = true;
    
    try {
      const response = await fetch(`${LIKE_ENDPOINTS.LIST}?post_id=${post.id}`, {
        headers: auth.getAuthHeader()
      });
      
      if (response.ok) {
        const data = await response.json();
        likes = data;
      }
    } catch (error) {
      console.error('Error loading likes:', error);
    } finally {
      loadingLikes = false;
    }
  }
  
  async function loadComments() {
    if (!isAuthenticated) return;
    loadingComments = true;
    
    try {
      const response = await fetch(`${COMMENT_ENDPOINTS.LIST}?post_id=${post.id}`, {
        headers: auth.getAuthHeader()
      });
      
      if (response.ok) {
        const data = await response.json();
        comments = data;
      }
    } catch (error) {
      console.error('Error loading comments:', error);
    } finally {
      loadingComments = false;
    }
  }
  
  async function submitComment() {
    if (!isAuthenticated || !newComment.trim()) {
      console.error('Cannot submit comment - not authenticated or empty content');
      return;
    }

    // Wait a bit to ensure auth state is fully initialized
    await new Promise(resolve => setTimeout(resolve, 100));

    // Get fresh auth state to ensure we have the latest user ID
    const state = auth.get();
    if (!state) {
      console.error('No auth state available');
      return;
    }
    
    if (!state.isAuthenticated) {
      console.error('User is not authenticated');
      return;
    }

    const currentUserId = state.user?.id;
    if (!currentUserId) {
      console.error('No user ID available in auth state');
      return;
    }

    // Ensure we have all required data
    if (!post?.id || !userId) {
      console.error('Missing required data:', { post_id: post?.id, user_id: userId });
      return;
    }

    console.log('Submitting comment with data:', {
      post_id: post.id,
      user_id: currentUserId,
      content: newComment
    });
    
    try {
      const response = await fetch(`${API_BASE_URL}/comments/create`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...auth.getAuthHeader()
        },
        body: JSON.stringify({
          post_id: post.id,
          user_id: userId,
          content: newComment
        })
      });
      
      if (response.ok) {
        newComment = '';
        commentCount += 1;
        post.comment_count = commentCount;
        await loadComments();
      } else {
        const errorData = await response.json().catch(() => {});
        console.error('Comment submission failed:', errorData || response.statusText);
      }
    } catch (error) {
      console.error('Error submitting comment:', error);
    }
  }
  
  // Format date to a readable format
  function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(date);
  }
  
  function toggleLikesPanel() {
    showLikes = !showLikes;
    if (showLikes) {
      loadLikes();
    }
  }
  
  function toggleCommentsPanel() {
    showComments = !showComments;
    if (showComments) {
      loadComments();
    }
  }
  

</script>

<div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 w-full mb-4">
  <!-- Post Header -->
  <div class="p-3">
    <a href="/profile?username={post.username}" class="flex items-center mb-3 hover:opacity-80 transition-opacity">
      <img
        src={post.profile_picture}
        alt=""
        class="w-8 h-8 rounded-full mr-2"
      />
      <div>
        <h3 class="font-semibold text-sm">{post.username || 'Unknown User'}</h3>
        <p class="text-xs text-gray-500">{formatDate(post.created_at)}</p>
      </div>
    </a>
  </div>
  
  <!-- Post Image (if available) -->
  {#if post.image_path}
    <div class="w-full h-96 flex items-center justify-center bg-gray-50">
      <img 
        src={post.image_path} 
        alt="Post by {post.username}" 
        class="max-h-full max-w-full object-contain" 
        on:error={(e) => { console.error('Image failed to load:', post.image_path); }}
      />
    </div>
  {/if}
  
  <!-- Post Content -->
  <p class="px-3 py-2 text-sm whitespace-pre-line">{post.caption || ''}</p>
  
  <!-- Likes and comments counts -->
  <div class="px-4 py-2 flex justify-between text-sm">
    <div>
      <button 
        on:click={toggleLikesPanel}
        class="text-gray-600 hover:text-gray-900 hover:underline font-medium"
        aria-label="Show likes"
      >
        {likeCount} {likeCount === 1 ? 'like' : 'likes'}
      </button>
    </div>
    <div>
      <button 
        on:click={toggleCommentsPanel}
        class="text-gray-600 hover:text-gray-900 hover:underline font-medium"
        aria-label="Show comments"
      >
        {commentCount} {commentCount === 1 ? 'comment' : 'comments'}
      </button>
    </div>
  </div>
  
  <!-- Post Actions -->
  <div class="px-4 py-3 flex space-x-6 border-t">
    <!-- Like Button -->
    <button 
      on:click={toggleLike} 
      class="flex items-center space-x-1.5 transition-colors {isLiked ? 'text-red-500 hover:text-red-600' : 'text-gray-600 hover:text-red-500'}"
      disabled={!isAuthenticated}
      aria-label={isLiked ? 'Unlike post' : 'Like post'}
    >
      <svg class="w-5 h-5" fill={isLiked ? 'currentColor' : 'none'} stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
      </svg>
      <span class="font-medium">{isLiked ? 'Liked' : 'Like'}</span>
    </button>
    
    <!-- Comment Button -->
    <button 
      on:click={toggleCommentsPanel}
      class="flex items-center space-x-1.5 text-gray-600 hover:text-blue-500 transition-colors"
      disabled={!isAuthenticated}
      aria-label="Show comments"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
      </svg>
      <span class="font-medium">Comment</span>
    </button>
  </div>
  
  <!-- Likes Panel -->
  {#if showLikes}
    <div class="px-4 py-3 border-t bg-gray-50">
      <h4 class="font-semibold text-sm mb-3 text-gray-700">Likes</h4>
      <div class="max-h-48 overflow-y-auto rounded-md">
        {#if loadingLikes}
          <div class="text-center py-4">
            <div class="animate-spin inline-block w-6 h-6 border-2 border-gray-300 border-t-blue-500 rounded-full"></div>
            <p class="text-sm text-gray-500 mt-2">Loading likes...</p>
          </div>
        {:else if likes.length === 0}
          <p class="text-sm text-gray-500 py-2">No likes yet.</p>
        {:else}
          <ul class="divide-y divide-gray-200">
            {#each likes as like}
              <li class="py-2">
                <a href="/profile?username={like.username}" class="flex items-center hover:bg-gray-100 p-2 rounded">
                  <img 
                    src={like.profile_picture}
                    alt="{like.username}'s avatar"
                    class="w-8 h-8 rounded-full mr-2"
                  />
                  <span class="font-medium text-sm">{like.username}</span>
                </a>
              </li>
            {/each}
          </ul>
        {/if}
      </div>
    </div>
  {/if}
  
  <!-- Comments Panel -->
  {#if showComments}
    <div class="px-4 py-3 border-t bg-gray-50">
      <h4 class="font-semibold text-sm mb-3 text-gray-700">Comments</h4>
      <div class="max-h-64 overflow-y-auto rounded-md">
        {#if loadingComments}
          <div class="text-center py-4">
            <div class="animate-spin inline-block w-6 h-6 border-2 border-gray-300 border-t-blue-500 rounded-full"></div>
            <p class="text-sm text-gray-500 mt-2">Loading comments...</p>
          </div>
        {:else if comments.length === 0}
          <p class="text-sm text-gray-500 py-2">No comments yet. Be the first to comment!</p>
        {:else}
          <ul class="divide-y divide-gray-200">
            {#each comments as comment}
              <li class="py-2">
                <div class="flex items-start">
                  <img 
                    src={comment.profile_picture}
                    alt="{comment.username}'s avatar"
                    class="w-8 h-8 rounded-full mr-2 mt-1"
                  />
                  <div>
                    <div class="flex items-center">
                      <a href="/profile?username={comment.username}" class="font-medium text-sm hover:underline">{comment.username}</a>
                      <span class="text-xs text-gray-500 ml-2">{formatDate(comment.created_at)}</span>
                    </div>
                    <p class="text-sm mt-1">{comment.content}</p>
                  </div>
                </div>
              </li>
            {/each}
          </ul>
        {/if}
      </div>
      
      <!-- Comment Form -->
      {#if isAuthenticated}
        <form on:submit|preventDefault={submitComment} class="mt-3 flex">
          <input 
            type="text" 
            bind:value={newComment} 
            placeholder="Add a comment..." 
            class="flex-1 border rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
          />
          <button 
            type="submit" 
            class="bg-blue-500 hover:bg-blue-600 transition-colors text-white rounded-r-lg px-4 py-2 text-sm font-medium"
            disabled={!newComment.trim()}
          >
            Post
          </button>
        </form>
      {/if}
    </div>
  {/if}
</div>
