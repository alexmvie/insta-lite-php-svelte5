<script lang="ts">
  import { onMount } from 'svelte';
  import { auth } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import Navbar from '../components/common/Navbar.svelte';
  import PostList from '../components/common/PostList.svelte';
  
  let isAuthenticated = false;
  let isLoading = true;
  
  onMount(() => {
    // Check if user is authenticated
    const unsubscribe = auth.subscribe(state => {
      isAuthenticated = state.isAuthenticated;
      isLoading = state.isLoading;
      
      // If not authenticated, redirect to login
      if (!state.isAuthenticated && !state.isLoading) {
        goto('/login');
      }
    });
    
    // Return cleanup function
    return () => unsubscribe();
  });
</script>

<svelte:head>
  <title>Insta-Lite | Home</title>
</svelte:head>

<div class="min-h-screen bg-gray-100">
  <Navbar />
  
  <main class="container mx-auto px-4 py-6 max-w-3xl">
    {#if isLoading}
      <div class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    {:else if isAuthenticated}
      <PostList showCreatePost={true} />
    {/if}
  </main>
</div>
