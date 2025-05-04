<script lang="ts">
	import { errorStore, type ErrorMessage } from '$lib/stores/error';
	import { fly } from 'svelte/transition';
	import { quintOut } from 'svelte/easing';

	// Subscribe to the error store
	const errors = errorStore;

	// Function to get the appropriate icon and color based on error type.
	function getErrorStyles(type: ErrorMessage['type']) {
		switch (type) {
			case 'error':
				return {
					icon: 'exclamation-circle',
					bgColor: '',
					borderColor: 'border-red-800',
					textColor: 'text-red-800',
					iconColor: 'text-red-700'
				};
			case 'warning':
				return {
					icon: 'exclamation-triangle',
					bgColor: '',
					borderColor: 'border-amber-700',
					textColor: 'text-amber-900',
					iconColor: 'text-amber-700'
				};
			case 'info':
				return {
					icon: 'information-circle',
					bgColor: '',
					borderColor: 'border-blue-800',
					textColor: 'text-blue-900',
					iconColor: 'text-blue-800'
				};
			case 'success':
				return {
					icon: 'check-circle',
					bgColor: '',
					borderColor: 'border-green-700',
					textColor: 'text-green-800',
					iconColor: 'text-green-700'
				};
			default:
				return {
					icon: 'exclamation-circle',
					bgColor: '',
					borderColor: 'border-gray-700',
					textColor: 'text-gray-800',
					iconColor: 'text-gray-700'
				};
		}
	}

	// Function to dismiss an error
	function dismissError(id: string) {
		errorStore.removeError(id);
	}

	// Format timestamp
	function formatTime(timestamp: number): string {
		const date = new Date(timestamp);
		return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
	}
</script>

<div class="fixed right-4 top-4 z-[9999] w-96 space-y-2">
	{#each $errors as error (error.id)}
		<div
			transition:fly={{ y: -30, duration: 300, easing: quintOut }}
			class="rounded-md border p-4 shadow-md bg-white/60 backdrop-blur-md {getErrorStyles(error.type).borderColor}"
			role="alert"
		>
			<div class="flex items-start">
				<div class="flex-shrink-0">
					{#if getErrorStyles(error.type).icon === 'exclamation-circle'}
						<svg
							class="h-5 w-5 {getErrorStyles(error.type).iconColor}"
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 20 20"
							fill="currentColor"
							aria-hidden="true"
						>
							<path
								fill-rule="evenodd"
								d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
								clip-rule="evenodd"
							/>
						</svg>
					{:else if getErrorStyles(error.type).icon === 'exclamation-triangle'}
						<svg
							class="h-5 w-5 {getErrorStyles(error.type).iconColor}"
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 20 20"
							fill="currentColor"
							aria-hidden="true"
						>
							<path
								fill-rule="evenodd"
								d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
								clip-rule="evenodd"
							/>
						</svg>
					{:else if getErrorStyles(error.type).icon === 'information-circle'}
						<svg
							class="h-5 w-5 {getErrorStyles(error.type).iconColor}"
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 20 20"
							fill="currentColor"
							aria-hidden="true"
						>
							<path
								fill-rule="evenodd"
								d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 7a1 1 0 002 0v-3a1 1 0 00-2 0v3z"
								clip-rule="evenodd"
							/>
						</svg>
					{:else if getErrorStyles(error.type).icon === 'check-circle'}
						<svg
							class="h-5 w-5 {getErrorStyles(error.type).iconColor}"
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 20 20"
							fill="currentColor"
							aria-hidden="true"
						>
							<path
								fill-rule="evenodd"
								d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
								clip-rule="evenodd"
							/>
						</svg>
					{/if}
				</div>
				<div class="ml-3 flex-1">
					<div class="flex justify-between">
						<h3 class="text-sm font-medium {getErrorStyles(error.type).textColor}">
							{error.message}
						</h3>
						<div class="ml-2">
							<button
								type="button"
								class="{getErrorStyles(error.type).textColor} hover:opacity-75 focus:outline-none"
								on:click={() => dismissError(error.id)}
								aria-label="Dismiss"
							>
								<span class="sr-only">Dismiss</span>
								<svg
									class="h-5 w-5"
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 20 20"
									fill="currentColor"
									aria-hidden="true"
								>
									<path
										fill-rule="evenodd"
										d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
										clip-rule="evenodd"
									/>
								</svg>
							</button>
						</div>
					</div>
					{#if error.details}
						<p class="mt-1 text-sm {getErrorStyles(error.type).textColor}">
							{error.details}
						</p>
					{/if}
					<p class="mt-1 text-xs opacity-75 {getErrorStyles(error.type).textColor}">
						{formatTime(error.timestamp)}
					</p>
				</div>
			</div>
		</div>
	{/each}
</div>
