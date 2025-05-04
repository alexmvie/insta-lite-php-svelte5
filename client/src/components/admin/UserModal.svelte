<script lang="ts">
  // Define props using $props() for Svelte 5
  const props = $props<{
    showModal: boolean,
    modalType: string,
    modalTitle: string,
    formData: {
      id: number | null,
      username: string,
      email: string,
      password: string,
      verified: number
    }
  }>();
  
  // Create local state variables
  let modalVisible = $state(props.showModal || false);
  let type = $state(props.modalType || '');
  let title = $state(props.modalTitle || '');
  let userData = $state(props.formData || {
    id: null,
    username: '',
    email: '',
    password: '',
    verified: 0
  });
  
  // Update local state when props change
  $effect(() => {
    modalVisible = props.showModal || false;
    type = props.modalType || '';
    title = props.modalTitle || '';
    userData = props.formData || {
      id: null,
      username: '',
      email: '',
      password: '',
      verified: 0
    };
    
  });
  
  // Functions to handle modal actions
  function closeModal() {
    const event = new CustomEvent('close', { bubbles: true });
    document.dispatchEvent(event);
  }
  
  function handleSubmit() {
    if (type === 'createUser') {
      const event = new CustomEvent('create', { bubbles: true });
      document.dispatchEvent(event);
    } else if (type === 'editUser') {
      const event = new CustomEvent('update', { bubbles: true });
      document.dispatchEvent(event);
    }
  }
</script>

{#if modalVisible}
  <div 
    class="fixed inset-0 bg-gray-800 bg-opacity-60 transition-opacity z-[9000]" 
    onclick={closeModal} 
    onkeydown={(e) => e.key === 'Escape' && closeModal()}
    role="dialog"
    aria-modal="true"
    tabindex="-1"
  ></div>
  
  <div class="fixed inset-0 z-[9010] overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
      <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <h3 class="text-lg font-medium leading-6 text-gray-900">{title}</h3>
          
          <div class="mt-4">
            <form onsubmit={(e) => { e.preventDefault(); handleSubmit(); }}>
              <input type="hidden" name="id" bind:value={userData.id} />
              
              <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input 
                  type="text" 
                  id="username" 
                  name="username" 
                  bind:value={userData.username} 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  required
                />
              </div>
              
              <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input 
                  type="email" 
                  id="email" 
                  name="email" 
                  bind:value={userData.email} 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  required
                />
              </div>
              
              <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Password {type === 'editUser' ? '(leave blank to keep current)' : ''}</label>
                <input 
                  type="password" 
                  id="password" 
                  name="password" 
                  bind:value={userData.password} 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  required={type === 'createUser'}
                />
              </div>
              
              <div class="mb-4">
                <span class="block text-sm font-medium text-gray-700">Verified</span>
                <div class="mt-1">
                  <label class="inline-flex items-center" for="verified-yes">
                    <input 
                      type="radio" 
                      id="verified-yes"
                      name="verified" 
                      bind:group={userData.verified} 
                      value={1} 
                      class="form-radio h-4 w-4 text-blue-600"
                    />
                    <span class="ml-2">Yes</span>
                  </label>
                  <label class="inline-flex items-center ml-6" for="verified-no">
                    <input 
                      type="radio" 
                      id="verified-no"
                      name="verified" 
                      bind:group={userData.verified} 
                      value={0} 
                      class="form-radio h-4 w-4 text-blue-600"
                    />
                    <span class="ml-2">No</span>
                  </label>
                </div>
                {#if type === 'createUser'}
                  <p class="mt-1 text-sm text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Setting a user as verified bypasses email verification
                  </p>
                {/if}
              </div>
            </form>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button 
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
            onclick={handleSubmit}
          >
            {type === 'createUser' ? 'Create' : 'Update'}
          </button>
          <button 
            type="button"
            class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mt-3 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            onclick={closeModal}
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}
