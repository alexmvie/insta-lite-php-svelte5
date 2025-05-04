<script lang="ts">
	import { auth } from '$lib/stores/auth';
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { theme } from '$lib/stores/theme';
	import { get } from 'svelte/store';

	let isAuthenticated = false;
	let isAdmin = false;
	let username = '';

	// Theme toggle logic
	import type { Writable } from 'svelte/store';

let currentTheme: 'light' | 'dark' = 'light';
(theme as Writable<'light' | 'dark'>).subscribe((value) => {
	currentTheme = value === 'dark' ? 'dark' : 'light';
});
function toggleTheme() {
	theme.set(currentTheme === 'dark' ? 'light' : 'dark');
}

	// Subscribe to auth store
	onMount(() => {
		const unsubscribe = auth.subscribe((state) => {
			isAuthenticated = state.isAuthenticated;
			isAdmin = state.isAdmin;
			username = state.user?.username || '';
		});

		return unsubscribe;
	});

	let showDropdown = false;

	function handleLogout() {
		auth.logout();
		goto('/login');
	}

	function toggleDropdown() {
		showDropdown = !showDropdown;
	}

	function hideDropdown() {
		showDropdown = false;
	}
</script>

<nav class="bg-gray-900 text-white shadow-lg">
	<div class="container mx-auto px-4">
		<div class="flex items-center justify-between py-3">
			<!-- Logo/Brand -->
			<div class="flex items-center space-x-2">
				<a href="/" class="text-xl font-bold">Insta-Lite</a>
			</div>

			<!-- Navigation Links -->
			<div class="hidden space-x-4 md:flex">
				<a href="/" class="flex items-center transition-colors hover:text-blue-300">
					<svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m4-8v8m5-5h-2a2 2 0 00-2 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 00-2-2H5a2 2 0 01-2-2v-2a2 2 0 012-2z" />
					</svg>
					Home
				</a>
				{#if isAdmin}
					<a href="/admin" class="flex items-center transition-colors hover:text-blue-300">
						<svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.104.896-2 2-2s2 .896 2 2v1h-4v-1zm-2 3h8v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2zm2-9a2 2 0 012 2v1a2 2 0 01-2 2H8a2 2 0 01-2-2V7a2 2 0 012-2h4z" />
						</svg>
						Admin
					</a>
				{/if}
				<a href="/profile" class="flex items-center transition-colors hover:text-blue-300">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						class="mr-1 h-5 w-5"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
						/>
					</svg>
					Profile
				</a>
				<a href="/settings" class="flex items-center transition-colors hover:text-blue-300">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						class="mr-1 h-5 w-5"
						fill="none"
						viewBox="0 0 24 24"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
						/>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
						/>
					</svg>
					Settings
				</a>
			</div>
			<!-- User Controls -->
			<div class="flex items-center space-x-2">
				<!-- Theme Toggle Button -->
				<button
					class="w-9 h-9 flex items-center justify-center rounded transition-colors hover:bg-gray-700 focus:outline-none"
					onclick={toggleTheme}
					aria-label={currentTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
				>
					{#if currentTheme === 'dark'}
						<!-- Sun Icon -->
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M6.05 6.05L4.636 4.636m12.728 0l-1.414 1.414M6.05 17.95l-1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z" />
						</svg>
					{:else}
						<!-- Moon Icon -->
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
						</svg>
					{/if}
				</button>
				{#if isAuthenticated}
					<span class="transition-colors hover:text-blue-300">Hello; {username}</span>
					<button
            onclick={() => {
              auth.logout();
              window.location.href = '/login';
            }}
            class="w-9 h-9 flex items-center justify-center rounded bg-red-600 hover:bg-red-700 transition-colors"
            aria-label="Logout"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="white">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
            </svg>
          </button>
				{:else}
					<a href="/login" class="transition-colors hover:text-blue-300">Login</a>
					<a
						href="/register"
						class="rounded-md bg-blue-600 px-4 py-2 transition-colors hover:bg-blue-700">Sign Up</a
					>
				{/if}
			</div>
		</div>
	</div>
</nav>
