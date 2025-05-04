<script lang="ts">
import { auth } from '$lib/stores/auth';
import { goto } from '$app/navigation';
import { onMount } from 'svelte';

let username = '';
let password = '';
let isSubmitting = false;
let errorMessage = '';
let redirectTo = '/';

// Check if already logged in
onMount(() => {
  // Subscribe to auth store to check authentication status
  const unsubscribe = auth.subscribe(state => {
    if (state.isAuthenticated) {
      goto(redirectTo);
    }
  });
  
  // Cleanup subscription
  return unsubscribe;
});

async function handleLogin() {
  if (!username || !password) {
    errorMessage = 'Please enter both username and password';
    return;
  }

  isSubmitting = true;
  errorMessage = '';

  try {
    // Use the auth store's login method
    const success = await auth.login(username, password);
    if (success) {
      // The auth store and onMount redirect will handle navigation
      // No need to manually call goto here
      return;
    } else {
      errorMessage = 'Login failed. Please check your credentials.';
    }
  } catch (error) {
    errorMessage = error instanceof Error ? error.message : 'An unexpected error occurred';
  } finally {
    isSubmitting = false;
  }
}
</script>

<div class="flex justify-center items-center min-h-screen bg-gray-100">
  <div class="w-full max-w-md p-8 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-center">Login to Insta-Lite</h1>
    
    {#if errorMessage}
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        {errorMessage}
      </div>
    {/if}
    
    <form on:submit|preventDefault={handleLogin} class="space-y-4">
      <div>
        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <input
          type="text"
          id="username"
          bind:value={username}
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter your username"
          disabled={isSubmitting}
        />
      </div>
      
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input
          type="password"
          id="password"
          bind:value={password}
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter your password"
          disabled={isSubmitting}
        />
      </div>
      
      <button
        type="submit"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
        disabled={isSubmitting}
      >
        {isSubmitting ? 'Logging in...' : 'Login'}
      </button>
    </form>
    
    <div class="mt-4 text-sm text-gray-600 text-center">
      <p>Don't have an account? <a href="/register" class="text-blue-600 hover:underline">Register</a></p>
    </div>
  </div>
</div>
