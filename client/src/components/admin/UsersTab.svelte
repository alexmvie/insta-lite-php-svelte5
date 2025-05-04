<script lang="ts">
  // Define props using $props() for Svelte 5
  const props = $props<{
    users: Array<{id: number, username: string, email: string, verified: boolean, created_at: string, profile_picture?: string}>,
    isLoading: boolean
  }>();
  
  // Create local state variables
  let usersData = $state(props.users || []);
  let loading = $state(props.isLoading || false);
  
  // Update local state when props change
  $effect(() => {
    // Make sure we're getting an array
    if (props.users && Array.isArray(props.users)) {
      usersData = [...props.users];
    } else {
      usersData = [];
    }
    
    loading = props.isLoading || false;
  });
  
  // Functions to handle user actions
  function openEditModal(user: {id: number, username: string, email: string, verified: boolean, created_at: string}) {
    // Dispatch custom events
    const event = new CustomEvent('editUser', { detail: user, bubbles: true });
    document.dispatchEvent(event);
  }
  
  function openCreateModal() {
    const event = new CustomEvent('createUser', { bubbles: true });
    document.dispatchEvent(event);
  }
  
  function handleVerifyUser(id: number) {
    const event = new CustomEvent('verifyUser', { detail: id, bubbles: true });
    document.dispatchEvent(event);
  }
  
  function handleResetVerification(id: number) {
    const event = new CustomEvent('resetVerification', { detail: id, bubbles: true });
    document.dispatchEvent(event);
  }
  
  function handleSimulateVerification(id: number, username: string) {
    const event = new CustomEvent('simulateVerification', { 
      detail: { id, username }, 
      bubbles: true 
    });
    document.dispatchEvent(event);
  }
  
  function handleResetPassword(id: number) {
    const event = new CustomEvent('resetPassword', { detail: id, bubbles: true });
    document.dispatchEvent(event);
  }
  
  function handleDeleteUser(id: number) {
    const event = new CustomEvent('deleteUser', { detail: id, bubbles: true });
    document.dispatchEvent(event);
  }
</script>

<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
  <h3 class="text-lg leading-6 font-medium text-gray-900">Users Management</h3>
  <button 
    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out"
    onclick={openCreateModal}
  >
    Add New User
  </button>
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
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avatar</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
          <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        {#each usersData as user}
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{user.id}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              {#if user.profile_picture}
                <img src={user.profile_picture} alt="Avatar" class="h-10 w-10 rounded-full object-cover border border-gray-300" />
              {:else}
                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
              {/if}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{user.username}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{user.email}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              {#if user.verified}
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Verified</span>
              {:else}
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
              {/if}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{new Date(user.created_at).toLocaleDateString()}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <div class="flex gap-1 justify-end">
                <!-- Edit Button -->
                <button 
                  class="p-2 rounded-md bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors relative group"
                  onclick={() => openEditModal(user)}
                  aria-label="Edit User"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 0L11.828 15.1l-2.12.636.636-2.12 9.192-9.192z" />
                  </svg>
                  <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Edit User</span>
                </button>
                
                {#if !user.verified}
                  <!-- Verify Button -->
                  <button 
                    class="p-2 rounded-md bg-green-100 text-green-600 hover:bg-green-200 transition-colors relative group"
                    onclick={() => handleVerifyUser(user.id)}
                    aria-label="Verify User"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Verify User</span>
                  </button>
                  
                  <!-- Simulate Verification Button -->
                  <button 
                    class="p-2 rounded-md bg-purple-100 text-purple-600 hover:bg-purple-200 transition-colors relative group"
                    onclick={() => handleSimulateVerification(user.id, user.username)}
                    aria-label="Simulate Verification"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Simulate Verification</span>
                  </button>
                {:else}
                  <!-- Reset Verification Button -->
                  <button 
                    class="p-2 rounded-md bg-amber-100 text-amber-600 hover:bg-amber-200 transition-colors relative group"
                    onclick={() => handleResetVerification(user.id)}
                    aria-label="Reset Verification"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Reset Verification</span>
                  </button>
                {/if}
                
                <!-- Reset Password Button -->
                <button 
                  class="p-2 rounded-md bg-indigo-100 text-indigo-600 hover:bg-indigo-200 transition-colors relative group"
                  onclick={() => handleResetPassword(user.id)}
                  aria-label="Reset Password"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                  </svg>
                  <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Reset Password</span>
                </button>
                
                <!-- Delete Button -->
                <button 
                  class="p-2 rounded-md bg-red-100 text-red-600 hover:bg-red-200 transition-colors relative group"
                  onclick={() => handleDeleteUser(user.id)}
                  aria-label="Delete User"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  <span class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Delete User</span>
                </button>
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}
