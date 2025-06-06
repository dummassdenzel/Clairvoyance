<script lang="ts">
  import { authStore } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';

  onMount(() => {
    const auth = $authStore; // Read store value once for efficiency and to avoid potential reactivity issues in onMount
    if (auth.isAuthenticated) {
      if (auth.user?.role === 'admin') {
        goto('/admin/dashboard', { replaceState: true });
      } else {
        // Default for any other authenticated user (editor, viewer, etc.)
        goto('/user/dashboard', { replaceState: true });
      }
    } else {
      // If not authenticated, redirect to the login page
      goto('/auth/login', { replaceState: true });
    }
  });
</script>

<!-- This content will briefly appear before redirection, or if JS is disabled. -->
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-4 text-center">
  <div>
    <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <h1 class="text-xl font-semibold text-gray-700">Loading Clairvoyance</h1>
    <p class="text-gray-500 mt-2">Please wait while we redirect you...</p>
  </div>
</div>