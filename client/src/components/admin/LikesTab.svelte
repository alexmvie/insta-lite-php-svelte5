<script lang="ts">
  // Define props using $props() for Svelte 5
  const props = $props<{
    likes: Array<{id: number, user_id: number, post_id: number, created_at: string}>,
    isLoading: boolean
  }>();
  
  // Create local state variables
  let likesData = $state(props.likes || []);
  let loading = $state(props.isLoading || false);
  
  // Update local state when props change
  $effect(() => {
    likesData = props.likes || [];
    loading = props.isLoading || false;
    
    
  });
  
  // Function to handle delete action
  function handleDeleteLike(id: number) {
    // Dispatch a custom event
    const event = new CustomEvent('deleteLike', { detail: id, bubbles: true });
    document.dispatchEvent(event);
  }
</script>

<div class="px-4 py-5 sm:px-6">
  <h3 class="text-lg leading-6 font-medium text-gray-900">Likes Management</h3>
</div>

{#if loading}
  <div class="flex justify-center items-center py-10">
    <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-blue-500"></div>
  </div>
{:else}
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post ID</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
          <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        {#each likesData as like}
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{like.id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{like.user_id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{like.post_id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{new Date(like.created_at).toLocaleDateString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button 
                class="text-red-600 hover:text-red-900"
                onclick={() => handleDeleteLike(like.id)}
              >
                Delete
              </button>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}
