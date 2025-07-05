<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';
  import * as api from '$lib/services/api';
  import { user, authLoaded } from '$lib/stores/auth';
  import { get } from 'svelte/store';

  let status: 'loading' | 'success' | 'error' = 'loading';
  let errorMessage = '';

  onMount(async () => {
    // This function waits until the auth store has confirmed the user's status.
    const checkAuth = async () => {
      return new Promise(resolve => {
        if (get(authLoaded)) return resolve(null);
        const unsubscribe = authLoaded.subscribe(loaded => {
          if (loaded) {
            unsubscribe();
            resolve(null);
          }
        });
      });
    };

    await checkAuth();

    // If user is not logged in after check, redirect them to login.
    // Pass the current path as a redirect destination.
    if (!get(user)) {
      const destination = $page.url.pathname;
      goto(`/auth/login?redirect=${encodeURIComponent(destination)}`);
      return;
    }

    const token = $page.params.token;
    if (!token) {
      status = 'error';
      errorMessage = 'No redemption token found in the URL.';
      return;
    }

    try {
      const response = await api.redeemShareLink(token);
      if (response.status === 'success') {
        status = 'success';
        // Redirect to the viewer dashboard page after a short delay.
        setTimeout(() => {
          goto('/viewer/dashboards');
        }, 2000);
      } else {
        status = 'error';
        errorMessage = response.message || 'Failed to redeem the invitation. The link may be invalid or expired.';
      }
    } catch (e) {
      status = 'error';
      errorMessage = 'An unexpected error occurred while redeeming the invitation.';
    }
  });
</script>

<svelte:head>
  <title>Redeem Invitation</title>
</svelte:head>

<div class="flex items-center justify-center min-h-screen bg-slate-100 dark:bg-slate-900">
  <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-2xl shadow-lg dark:bg-slate-800">
    {#if status === 'loading'}
      <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-slate-700">
          <svg class="animate-spin h-8 w-8 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
        <h1 class="mt-5 text-2xl font-bold text-slate-900 dark:text-white">Redeeming Invitation</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Please wait while we grant you access to the dashboard.</p>
      </div>
    {:else if status === 'success'}
      <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-slate-700">
          <svg class="h-8 w-8 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
        <h1 class="mt-5 text-2xl font-bold text-slate-900 dark:text-white">Success!</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Access granted. Redirecting you to your dashboards...</p>
      </div>
    {:else if status === 'error'}
      <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-slate-700">
          <svg class="h-8 w-8 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="mt-5 text-2xl font-bold text-slate-900 dark:text-white">Redemption Failed</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{errorMessage}</p>
        <a href="/viewer/dashboards" class="mt-6 inline-flex items-center justify-center w-full px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-slate-900 transition-colors">
          Go to My Dashboards
        </a>
      </div>
    {/if}
  </div>
</div>
