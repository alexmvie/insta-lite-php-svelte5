<script lang="ts">
	import { onMount } from 'svelte';
	import { auth } from '$lib/stores/auth';
	import { goto } from '$app/navigation';
	import { apiRequest } from '$lib/utils/apiUtils';
	import { ADMIN_ENDPOINTS } from '$lib/config/api';
	import { AUTH_ENDPOINTS } from '$lib/config/api';
	import { errorStore, showError, showSuccess, showInfo } from '$lib/stores/error';
	import ErrorToast from '../../components/common/ErrorToast.svelte';
	import Navbar from '../../components/common/Navbar.svelte';
	import UsersTab from '../../components/admin/UsersTab.svelte';
	import PostsTab from '../../components/admin/PostsTab.svelte';
	import CommentsTab from '../../components/admin/CommentsTab.svelte';
	import LikesTab from '../../components/admin/LikesTab.svelte';
	import UserModal from '../../components/admin/UserModal.svelte';

	// Data for different sections using $state rune
	let users = $state<
		Array<{ id: number; username: string; email: string; verified: boolean; created_at: string }>
	>([]);
	let posts = $state<
		Array<{ id: number; user_id: number; caption: string; image_path: string; created_at: string }>
	>([]);
	let comments = $state<
		Array<{ id: number; user_id: number; post_id: number; content: string; created_at: string }>
	>([]);
	let likes = $state<Array<{ id: number; user_id: number; post_id: number; created_at: string }>>(
		[]
	);

	// UI state
	let activeTab = $state('users');
	let isLoading = $state(false);
	let showModal = $state(false);
	let modalType = $state('');
	let modalTitle = $state('');

	// Form data
	let formData = $state({
		id: null as number | null,
		username: '',
		email: '',
		password: '',
		verified: false
	});

	// Event handlers for custom events from components
	const handleCreateUser = (event: Event) => {
		const customEvent = event as CustomEvent;
		openModal('create', 'Create New User');
	};

	const handleEditUser = (event: Event) => {
		const customEvent = event as CustomEvent;
		const user = customEvent.detail;
		formData = {
			id: user.id,
			username: user.username,
			email: user.email,
			password: '',
			verified: user.verified
		};
		openModal('edit', 'Edit User');
	};

	const handleVerifyUser = (event: Event) => {
		const customEvent = event as CustomEvent;
		verifyUser(customEvent.detail);
	};

	const handleResetVerification = (event: Event) => {
		const customEvent = event as CustomEvent;
		resetVerification(customEvent.detail);
	};

	const handleSimulateVerification = (event: Event) => {
		const customEvent = event as CustomEvent;
		const { id, username } = customEvent.detail;
		simulateVerification(id, username);
	};

	const handleResetPassword = (event: Event) => {
		const customEvent = event as CustomEvent;
		resetPassword(customEvent.detail);
	};

	const handleDeleteUser = (event: Event) => {
		const customEvent = event as CustomEvent;
		deleteUser(customEvent.detail);
	};

	const handleDeletePost = (event: Event) => {
		const customEvent = event as CustomEvent;
		deletePost(customEvent.detail);
	};

	const handleDeleteComment = (event: Event) => {
		const customEvent = event as CustomEvent;
		deleteComment(customEvent.detail);
	};

	const handleDeleteLike = (event: Event) => {
		const customEvent = event as CustomEvent;
		deleteLike(customEvent.detail);
	};

	onMount(() => {
		// Check if user is logged in and has admin privileges
		checkAdminAccess();

		// Set up event listeners for custom events from components
		setupEventListeners();

		return () => {
			// Clean up event listeners on component destruction
			removeEventListeners();
		};
	});

	// Check if the user has admin access
	async function checkAdminAccess() {
		// Check if user is logged in
		if (!$auth.token) {
			showError('You must be logged in to access the admin area');
			goto('/login');
			return;
		}

		try {
			// Check if user has admin privileges
			const response = await fetch(AUTH_ENDPOINTS.ME, {
				headers: {
					Authorization: `Bearer ${$auth.token}`
				}
			});

			if (!response.ok) {
				throw new Error('Failed to fetch user data');
			}

			const userData = await response.json();

			if (!userData.user?.is_admin) {
				showError('You do not have permission to access the admin area');
				goto('/');
				return;
			}

			// User is admin, load data
			loadTabData('users');
		} catch (err: any) {
			showError(`Authentication error: ${err.message || 'Unknown error'}`);
			goto('/');
		}
	}

	// Set up event listeners
	function setupEventListeners() {
		document.addEventListener('createUser', handleCreateUser as EventListener);
		document.addEventListener('editUser', handleEditUser as EventListener);
		document.addEventListener('verifyUser', handleVerifyUser as EventListener);
		document.addEventListener('resetVerification', handleResetVerification as EventListener);
		document.addEventListener('simulateVerification', handleSimulateVerification as EventListener);
		document.addEventListener('resetPassword', handleResetPassword as EventListener);
		document.addEventListener('deleteUser', handleDeleteUser as EventListener);
		document.addEventListener('deletePost', handleDeletePost as EventListener);
		document.addEventListener('deleteComment', handleDeleteComment as EventListener);
		document.addEventListener('deleteLike', handleDeleteLike as EventListener);
		document.addEventListener('close', closeModal as EventListener);
		document.addEventListener('create', createUser as EventListener);
		document.addEventListener('update', updateUser as EventListener);
	}

	// Remove event listeners
	function removeEventListeners() {
		document.removeEventListener('createUser', handleCreateUser as EventListener);
		document.removeEventListener('editUser', handleEditUser as EventListener);
		document.removeEventListener('verifyUser', handleVerifyUser as EventListener);
		document.removeEventListener('resetVerification', handleResetVerification as EventListener);
		document.removeEventListener(
			'simulateVerification',
			handleSimulateVerification as EventListener
		);
		document.removeEventListener('resetPassword', handleResetPassword as EventListener);
		document.removeEventListener('deleteUser', handleDeleteUser as EventListener);
		document.removeEventListener('deletePost', handleDeletePost as EventListener);
		document.removeEventListener('deleteComment', handleDeleteComment as EventListener);
		document.removeEventListener('deleteLike', handleDeleteLike as EventListener);
		document.removeEventListener('close', closeModal as EventListener);
		document.removeEventListener('create', createUser as EventListener);
		document.removeEventListener('update', updateUser as EventListener);
	}

	// Load data based on active tab
	async function loadTabData(tab: string) {
		activeTab = tab;
		isLoading = true;

		try {
			if (tab === 'users') {
				await fetchUsers();
			} else if (tab === 'posts') {
				await fetchPosts();
			} else if (tab === 'comments') {
				await fetchComments();
			} else if (tab === 'likes') {
				await fetchLikes();
			}
		} catch (err: any) {
			showError(`Failed to load ${tab}: ${err.message || 'Unknown error'}`);
		} finally {
			isLoading = false;
		}
	}

	async function fetchUsers() {
		try {
			isLoading = true;
			const data = await apiRequest(ADMIN_ENDPOINTS.USERS.LIST, {
				errorPrefix: 'Users',
				successMessage: 'Users loaded successfully'
			});

			// Update the state variable with the new data
			users = [...data];
		} catch (err: any) {
			// Error is already handled by apiRequest
			console.error('Error in fetchUsers:', err);
		} finally {
			isLoading = false;
		}
	}

	async function createUser() {
		if (!formData.username || !formData.email || !formData.password) {
			showError('Please fill all required fields');
			return;
		}

		try {
			isLoading = true;
			await apiRequest(ADMIN_ENDPOINTS.USERS.LIST, {
				method: 'POST',
				body: formData,
				errorPrefix: 'Create User',
				successMessage: 'User created successfully'
			});

			await fetchUsers();
			closeModal();
		} catch (err: any) {
			showError(`Failed to create user: ${err.message || 'Unknown error'}`);
		} finally {
			isLoading = false;
		}
	}

	async function updateUser() {
		if (!formData.username || !formData.email) {
			showError('Please fill all required fields');
			return;
		}
		if (formData.id === null) {
			showError('No user selected for update.');
			return;
		}
		try {
			isLoading = true;
			await apiRequest(ADMIN_ENDPOINTS.USERS.UPDATE(formData.id), {
				method: 'PUT',
				body: formData,
				errorPrefix: 'Update User',
				successMessage: 'User updated successfully'
			});

			await fetchUsers();
			closeModal();
		} catch (err: any) {
			showError(`Failed to update user: ${err.message || 'Unknown error'}`);
		} finally {
			isLoading = false;
		}
	}

	async function deleteUser(id: number) {
		try {
			isLoading = true;
			const response = await fetch(ADMIN_ENDPOINTS.USERS.DELETE(id), {
				method: 'DELETE',
				headers: {
					Authorization: `Bearer ${$auth.token}`
				}
			});

			if (!response.ok) {
				showError(`Failed to delete user: ${response.statusText}`);
			}

			await fetchUsers();
		} catch (err: any) {
			showError(err.message || 'An error occurred');
		} finally {
			isLoading = false;
		}
	}

	async function verifyUser(id: number) {
		try {
			isLoading = true;
			const response = await apiRequest(ADMIN_ENDPOINTS.USERS.VERIFY(id), {
				method: 'POST',
				headers: {
					Authorization: `Bearer ${$auth.token}`
				}
			});

			if (!response.ok) {
				showError('Failed to verify user');
			}

			await fetchUsers();
		} catch (err: any) {
			showError(err.message || 'An error occurred');
		} finally {
			isLoading = false;
		}
	}

	async function resetVerification(id: number) {
		try {
			isLoading = true;
			await apiRequest(ADMIN_ENDPOINTS.USERS.RESET_VERIFICATION(id), {
				method: 'POST',
				errorPrefix: 'Reset Verification',
				successMessage: 'Verification reset successfully'
			});

			await fetchUsers();
		} catch (err: any) {
			showError(err.message || 'An error occurred');
		} finally {
			isLoading = false;
		}
	}

	async function simulateVerification(id: number, username: string) {
		try {
			isLoading = true;
			// Generate a verification token (this would normally be done on the backend)
			const token = btoa(`verify-${username}-${Date.now()}`);

			// Open the verification URL in a new tab, pointing to the CLIENT route (not the server API)
			// We use window.location.origin to ensure we're using the client URL (localhost:5173)
			window.open(`${window.location.origin}/verify?token=${token}&user_id=${id}`, '_blank');

			// Show success message
			showInfo(
				`Verification simulation started for user ${username}. A new tab should open with the verification page.`
			);
		} catch (err: any) {
			showError(`Verification simulation failed: ${err.message || 'Unknown error'}`);
		} finally {
			isLoading = false;
		}
	}

	async function resetPassword(id: number) {
		try {
			isLoading = true;
			const response = await fetch(ADMIN_ENDPOINTS.USERS.RESET_PASSWORD(id), {
				method: 'POST',
				headers: {
					Authorization: `Bearer ${$auth.token}`,
					'Content-Type': 'application/json'
				}
			});

			if (!response.ok) {
				showError('Failed to reset password');
			}

			const data = await response.json();
			showInfo(`Password has been reset to: ${data.newPassword}`);
		} catch (err: any) {
			showError(err.message || 'An error occurred');
		} finally {
			isLoading = false;
		}
	}

	async function fetchPosts() {
		try {
			isLoading = true;
			const data = await apiRequest(ADMIN_ENDPOINTS.POSTS.LIST, {
				errorPrefix: 'Posts',
				successMessage: 'Posts loaded successfully'
			});

			if (!Array.isArray(data)) {
				throw new Error('Invalid response format');
			}

			posts = [...data];
		} catch (err: any) {
			// Error is already handled by apiRequest
			console.error('Error in fetchPosts:', err);
		} finally {
			isLoading = false;
		}
	}

	async function deletePost(id: number) {
		try {
			isLoading = true;
			await apiRequest(ADMIN_ENDPOINTS.POSTS.DELETE(id), {
				method: 'DELETE',
				errorPrefix: 'Delete Post',
				successMessage: 'Post deleted successfully'
			});

			await fetchPosts();
		} catch (err: any) {
			// Error is already handled by apiRequest
			console.error('Error in deletePost:', err);
		} finally {
			isLoading = false;
		}
	}

	async function fetchComments() {
		try {
			isLoading = true;
			const data = await apiRequest(ADMIN_ENDPOINTS.COMMENTS.LIST, {
				errorPrefix: 'Comments',
				successMessage: 'Comments loaded successfully'
			});

			if (!Array.isArray(data)) {
				throw new Error('Invalid response format');
			}

			comments = [...data];
		} catch (err: any) {
			// Error is already handled by apiRequest
			console.error('Error in fetchComments:', err);
		} finally {
			isLoading = false;
		}
	}

	async function deleteComment(id: number) {
		try {
			isLoading = true;
			await apiRequest(ADMIN_ENDPOINTS.COMMENTS.DELETE(id), {
				method: 'DELETE',
				errorPrefix: 'Delete Comment',
				successMessage: 'Comment deleted successfully'
			});

			await fetchComments();
		} catch (err: any) {
			// Error is already handled by apiRequest
			console.error('Error in deleteComment:', err);
		} finally {
			isLoading = false;
		}
	}

	async function fetchLikes() {
		try {
			isLoading = true;
			const data = await apiRequest(`${ADMIN_ENDPOINTS.USERS.LIST.replace('/users', '/likes')}`, {
				errorPrefix: 'Likes',
				successMessage: 'Likes loaded successfully'
			});

			if (!Array.isArray(data)) {
				throw new Error('Invalid response format');
			}

			likes = [...data];
		} catch (err: any) {
			// Error is already handled by apiRequest
			console.error('Error in fetchLikes:', err);
		} finally {
			isLoading = false;
		}
	}

	async function deleteLike(id: number) {
		try {
			isLoading = true;
			await apiRequest(`${ADMIN_ENDPOINTS.USERS.LIST.replace('/users', '/likes')}/${id}`, {
				method: 'DELETE',
				errorPrefix: 'Delete Like',
				successMessage: 'Like deleted successfully'
			});

			await fetchLikes();
		} catch (err: any) {
			// Error is already handled by apiRequest
			console.error('Error in deleteLike:', err);
		} finally {
			isLoading = false;
		}
	}

	function openModal(type: string, title: string) {
		modalType = type;
		modalTitle = title;
		showModal = true;
	}

	function closeModal() {
		showModal = false;
		// Reset form data
		formData = {
			id: null,
			username: '',
			email: '',
			password: '',
			verified: false
		};
	}
</script>

<div class="min-h-screen bg-gray-50">
	<Navbar />
	<!-- Header -->
	<header class="bg-white shadow-sm">
		<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
			<div class="flex items-center justify-between py-4">
				<h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
			</div>
		</div>
	</header>

	<!-- Tabs -->
	<div class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
		<div class="border-b border-gray-200">
			<nav class="-mb-px flex space-x-8" aria-label="Tabs">
				<button
					class={`${activeTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'} whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium`}
					on:click={() => loadTabData('users')}
				>
					Users
				</button>
				<button
					class={`${activeTab === 'posts' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'} whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium`}
					on:click={() => loadTabData('posts')}
				>
					Posts
				</button>
				<button
					class={`${activeTab === 'comments' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'} whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium`}
					on:click={() => loadTabData('comments')}
				>
					Comments
				</button>
				<button
					class={`${activeTab === 'likes' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'} whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium`}
					on:click={() => loadTabData('likes')}
				>
					Likes
				</button>
			</nav>
		</div>
	</div>

	<!-- Content -->
	<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
		{#if isLoading}
			<div class="flex justify-center py-12">
				<div class="h-12 w-12 animate-spin rounded-full border-b-2 border-indigo-500"></div>
			</div>
		{:else if activeTab === 'users'}
			<UsersTab {users} />
		{:else if activeTab === 'posts'}
			<PostsTab {posts} {isLoading} />
		{:else if activeTab === 'comments'}
			<CommentsTab {comments} {isLoading} />
		{:else if activeTab === 'likes'}
			<LikesTab {likes} {isLoading} />
		{/if}
	</div>

	<!-- Modal -->
	{#if showModal}
		<UserModal {modalType} {modalTitle} bind:formData />
	{/if}

	<!-- Error Toast -->
	<ErrorToast />
</div>
