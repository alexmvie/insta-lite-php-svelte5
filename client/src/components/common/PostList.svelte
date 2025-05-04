<script lang="ts">
  import { onMount } from 'svelte';
  import { auth } from '$lib/stores/auth';
  import { API_BASE_URL, POST_ENDPOINTS } from '$lib/config/api';
  import Post from './Post.svelte';
  
  export let showCreatePost = true;
  export let userId: number | null = null; // Filter posts by user ID
  export let username: string | null = null; // Filter posts by username
  export let limit = 3; // Number of posts to display (changed to 3 as requested)
  export let initialOffset = 0; // Initial offset for pagination
  
  // Define Post type
  type Post = {
    id: number;
    user_id: number;
    caption: string;
    location?: string;
    created_at: string;
    username?: string;
    profile_picture?: string;
    likes_count?: number;
    comments_count?: number;
    liked_by_user?: boolean;
    image_path?: string;
  };

  // Config for deriving thumbnail path
  const FULLRES_DIR = 'uploads/posts/';
  const THUMB_DIR = 'uploads/posts/thumbs/';

  function getThumbnailPath(imagePath?: string) {
    if (!imagePath) return '';
    return imagePath.replace(FULLRES_DIR, THUMB_DIR);
  }
  
  let posts: Post[] = [];
  let isLoading = false;
  let loadingMore = false;
  let error = '';
  let hasMore = true;
  let offset = initialOffset;
  let caption = '';
  let location = '';
  let imageFile: File | null = null;
  let isSubmitting = false;
  let isAuthenticated = false;
  let currentUserId: number | null = null;
  
  // Generate a unique ID for this timeline instance
  const timelineId = `timeline-${Math.random().toString(36).substring(2, 9)}`;
  
  onMount(() => {
    // Subscribe to auth store
    const unsubscribe = auth.subscribe(state => {
      isAuthenticated = state.isAuthenticated;
      currentUserId = state.user?.id || null;
    });
    
    // Load posts immediately
    loadPosts();
    
    // Setup infinite scrolling
    const cleanupScroll = setupInfiniteScroll();
    
    // Return cleanup function
    return () => {
      unsubscribe();
      cleanupScroll();
    };
  });
  
  async function loadPosts(resetOffset = false) {
    try {
      if (resetOffset) {
        offset = initialOffset;
        posts = [];
      }
      
      isLoading = true;
      
      const params = new URLSearchParams();
      params.append('limit', limit.toString());
      params.append('offset', offset.toString());
      
      if (userId) {
        params.append('user_id', userId.toString());
      }
      
      if (username) {
        params.append('username', username);
      }
      
      console.log(`Loading posts with offset ${offset} and limit ${limit}`);
      
      const response = await fetch(`${POST_ENDPOINTS.LIST}?${params.toString()}`, {
        headers: {
          ...auth.getAuthHeader()
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to load posts');
      }
      
      // Get posts data
      const data = await response.json();
      console.log('API response:', data);
      
      // Handle both formats: array of posts or {posts, has_more} object
      const postsData = Array.isArray(data) ? data : (data.posts || []);
      hasMore = Array.isArray(data) ? postsData.length >= limit : (data.has_more || false);
      
      // Use the posts directly from the API response without transformation
      // The PHP API already returns the correct structure with all fields we need
      const processedPosts = postsData.map((post: any) => {
        // Ensure all required fields exist with defaults if missing
        return {
          ...post,
          // Provide defaults for any potentially missing fields
          id: post.id || 0,
          user_id: post.user_id || 0,
          caption: post.caption || '',
          image_path: post.image_path || '',
          created_at: post.created_at || new Date().toISOString(),
          username: post.username || `user_${post.user_id}`,
          profile_picture: post.profile_picture || '',
          like_count: post.like_count || 0,
          comment_count: post.comment_count || 0,
          is_liked_by_current_user: post.is_liked_by_current_user || false
        };
      });
      
      
      
      // Update posts - always append to existing posts unless resetOffset is true
      if (resetOffset) {
        posts = processedPosts;
      } else {
        posts = [...posts, ...processedPosts];
      }
      
      
    } catch (err) {
      error = err instanceof Error ? err.message : 'An error occurred while loading posts';
      
    } finally {
      isLoading = false;
      loadingMore = false;
    }
  }
  
  async function loadMorePosts() {
    if (loadingMore || !hasMore) return;
    
    loadingMore = true;
    offset += limit;
    await loadPosts(false);
  }
  
  // Function to handle infinite scrolling
  function setupInfiniteScroll() {
    const handleScroll = () => {
      if (loadingMore || !hasMore) return;
      
      const scrollPosition = window.scrollY;
      const windowHeight = window.innerHeight;
      const bodyHeight = document.body.offsetHeight;
      
      // Load more posts when user scrolls to 80% of the page
      if (scrollPosition + windowHeight > bodyHeight * 0.8) {
        loadMorePosts();
      }
    };
    
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }
  
  async function createPost() {
    if (!imageFile || !isAuthenticated || isSubmitting) {
      error = !imageFile ? 'You must select an image to create a post.' : '';
      return;
    }
    try {
      isSubmitting = true;
      error = '';
      const formData = new FormData();
      formData.append('caption', caption);
      formData.append('location', location);
      formData.append('image', imageFile);
      const response = await fetch(POST_ENDPOINTS.CREATE, {
        method: 'POST',
        headers: {
          ...auth.getAuthHeader()
        },
        body: formData
      });
      if (!response.ok) {
        const errData = await response.json().catch(() => ({}));
        throw new Error(errData.error || 'Failed to create post');
      }
      // Clear the form and reload posts
      caption = '';
      location = '';
      imageFile = null;
      imagePreviewUrl = '';
      await loadPosts(true);
    } catch (err) {
      error = err instanceof Error ? err.message : 'An error occurred while creating post';
      console.error('Error creating post:', err);
    } finally {
      isSubmitting = false;
    }
  }
  
  let imagePreviewUrl = '';
  function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
      imageFile = target.files[0];
      // Show preview
      if (imagePreviewUrl) URL.revokeObjectURL(imagePreviewUrl);
      imagePreviewUrl = URL.createObjectURL(imageFile);
    } else {
      imageFile = null;
      if (imagePreviewUrl) {
        URL.revokeObjectURL(imagePreviewUrl);
        imagePreviewUrl = '';
      }
    }
  }
</script>

<div class="timeline-container">
  {#if error}
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
      {error}
    </div>
  {/if}
  
  {#if showCreatePost && isAuthenticated}
    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
      <h2 class="text-lg font-semibold mb-2">Create a Post</h2>
      <form on:submit|preventDefault={createPost} class="space-y-3">
        <textarea
          bind:value={caption}
          placeholder="Write a caption..."
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
          rows="3"
          disabled={isSubmitting}
        ></textarea>
        <input
          type="text"
          bind:value={location}
          placeholder="Add a location (optional)"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          disabled={isSubmitting}
        />
        
        <div class="flex items-center">
          <label class="flex items-center space-x-2 cursor-pointer">
            <input 
              type="file" 
              accept="image/*" 
              class="hidden" 
              on:change={handleFileChange} 
              disabled={isSubmitting}
            />
            <span class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded-md text-sm flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {imageFile ? 'Change Image' : 'Add Image'}
            </span>
          </label>
          
          {#if imageFile}
            <span class="ml-2 text-sm text-gray-600">{imageFile.name}</span>
            <button 
              type="button" 
              class="ml-2 text-red-500 hover:text-red-700"
              on:click={() => imageFile = null}
              aria-label="Remove image"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          {/if}
                    <div class="ml-4">
            {#if imagePreviewUrl}
              <img src={imagePreviewUrl} alt="Image preview" class="w-16 h-16 object-cover rounded border mr-2" style="display:inline-block;vertical-align:middle;" />
            {/if}
          </div>
          <div class="ml-auto">
            <button
              type="submit"
              class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
              disabled={!imageFile || isSubmitting}
            >
              {isSubmitting ? 'Posting...' : 'Post'}
            </button>
          </div>
        </div>
      </form>
    </div>
  {/if}
  
  <!-- Posts List -->
  <div id="{timelineId}-posts" class="grid grid-cols-1 gap-4 w-full">
    {#if isLoading && offset === 0}
      <div class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-300 border-t-blue-500"></div>
      </div>
    {:else if posts.length === 0}
      <div class="bg-white shadow rounded-lg p-6 text-center">
        <p class="text-gray-500">No posts to display.</p>
        {#if !userId && !username && isAuthenticated}
          <button 
            on:click={createPost}
            class="mt-3 inline-block bg-blue-500 text-white px-4 py-2 rounded-full text-sm"
          >
            Create your first post
          </button>
        {/if}
      </div>
    {:else}
      {#each posts as post (post.id)}
        <Post {post} />
      {/each}
    {/if}
  </div>
  
  {#if loadingMore}
    <div class="flex justify-center mt-4">
      <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-300 border-t-blue-500"></div>
      <span class="ml-2">Loading more posts...</span>
    </div>
  {/if}
</div>
