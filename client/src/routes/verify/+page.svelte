<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { AUTH_ENDPOINTS } from '$lib/config/api';
  
  // State variables
  let isLoading = true;
  let verificationStatus = 'pending';
  let message = 'Verifying your account...';
  let userId = null;
  let token = null;
  
  onMount(async () => {
    try {
      
      
      // Get token and user_id from URL parameters
      const urlParams = $page.url.searchParams;
      userId = urlParams.get('user_id');
      token = urlParams.get('token');
      
      
      
      if (!userId || !token) {
        verificationStatus = 'error';
        message = 'Invalid verification link. Missing required parameters.';
        isLoading = false;
        return;
      }
      
      // Call the verification API
      // We need to use the full URL to avoid CORS issues
      const verifyApiUrl = 'http://localhost:8000/api/auth/verify';
      
      
      try {
        // Create the request payload
        const payload = { user_id: userId, token };
        console.log('Request payload:', payload);
        
        const response = await fetch(verifyApiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });
        
        console.log('API response status:', response.status);
        
        if (response.ok) {
          const responseData = await response.json();
          console.log('API response data:', responseData);
          verificationStatus = 'success';
          message = responseData.message || 'Your account has been successfully verified!';
        } else {
          let errorData = { message: '' };
          try {
            errorData = await response.json();
          } catch (e) {
            
          }
          
          
          verificationStatus = 'error';
          message = errorData.message || 'Failed to verify your account. Please try again or contact support.';
        }
      } catch (apiErr) {
        
        verificationStatus = 'error';
        message = 'Could not connect to the verification service. Please try again later.';
      }
    } catch (err) {
      
      verificationStatus = 'error';
      message = 'An unexpected error occurred. Please try again later.';
    } finally {
      isLoading = false;
    }
  });
</script>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
  <div class="sm:mx-auto sm:w-full sm:max-w-md">
    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
      Account Verification
    </h2>
  </div>

  <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
    <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
      {#if isLoading}
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
        <p class="mt-4 text-center text-gray-600">{message}</p>
      {:else if verificationStatus === 'success'}
        <div class="rounded-md bg-green-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">Verification Successful</h3>
              <div class="mt-2 text-sm text-green-700">
                <p>{message}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-6">
          <button 
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            on:click={() => goto('/login')}
          >
            Go to Login
          </button>
        </div>
      {:else}
        <div class="rounded-md bg-red-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Verification Failed</h3>
              <div class="mt-2 text-sm text-red-700">
                <p>{message}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-6">
          <button 
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            on:click={() => goto('/login')}
          >
            Go to Login
          </button>
        </div>
      {/if}
    </div>
  </div>
</div>
