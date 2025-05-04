<script lang="ts">


  // Define props using $props() for Svelte 5
  const props = $props<{
    posts: Array<{id: number, user_id: number, caption: string, image_path: string, created_at: string}>,
    isLoading: boolean
  }>();
  
  // Create local state variables
  let postsData = $state(props.posts || []);
  let loading = $state(props.isLoading || false);
  let viewMode = $state<'list' | 'grid'>('list'); // Track current view mode
  

  
  // Update local state when props change
  $effect(() => {
    postsData = props.posts || [];
    loading = props.isLoading || false;

  });
  
  // Function to handle delete action
  function handleDeletePost(id: number) {
    // Dispatch a custom event
    const event = new CustomEvent('deletePost', { detail: id, bubbles: true });
    document.dispatchEvent(event);
  }



  // Toggle between list and grid views
  function toggleViewMode() {
    viewMode = viewMode === 'list' ? 'grid' : 'list';
  }
</script>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
  <h3 class="text-lg leading-6 font-medium text-gray-900">Posts Management</h3>
  <div class="flex space-x-2">
    <button 
      class={`px-3 py-1 rounded-md text-sm font-medium ${viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
      onclick={() => viewMode = 'list'}
      aria-label="List view"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
      </svg>
    </button>
    <button 
      class={`px-3 py-1 rounded-md text-sm font-medium ${viewMode === 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
      onclick={() => viewMode = 'grid'}
      aria-label="Grid view"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
      </svg>
    </button>
  </div>
</div>

{#if loading}
  <div class="flex justify-center items-center py-10">
    <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-blue-500"></div>
  </div>
{:else}
  {#if viewMode === 'list'}
    <!-- List View -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caption</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          {#each postsData as post}
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{post.id}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{post.user_id}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="h-10 w-10 rounded-full overflow-hidden bg-gray-100">
                  <img 
                    src={post.image_path} 
                    alt="Post thumbnail" 
                    class="h-full w-full object-cover"
                  />
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{post.caption}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{new Date(post.created_at).toLocaleDateString()}</td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button 
                  class="text-red-600 hover:text-red-900"
                  onclick={() => handleDeletePost(post.id)}
                >
                  Delete
                </button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {:else}
    <!-- Grid View -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 p-4">
      {#each postsData as post}
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="relative pb-[100%] w-full"> <!-- Square aspect ratio -->
            <img 
              src={post.image_path} 
              alt="" 
              class="absolute inset-0 w-full h-full object-cover"
            />
          </div>
          <div class="p-4">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-900">ID: {post.id}</span>
              <span class="text-sm text-gray-500">User: {post.user_id}</span>
            </div>
            <p class="text-sm text-gray-500 mb-3 line-clamp-2">{post.caption}</p>
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-400">{new Date(post.created_at).toLocaleDateString()}</span>
              <button 
                class="text-red-600 hover:text-red-900 text-sm font-medium"
                onclick={() => handleDeletePost(post.id)}
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
{/if}
