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

<div class="flex items-center justify-center min-h-screen bg-gray-50">
  <div class="p-8 bg-white rounded-lg shadow-md text-center w-full max-w-md">
    {#if status === 'loading'}
      <h1 class="text-2xl font-bold text-gray-800">Redeeming Invitation...</h1>
      <p class="text-gray-600 mt-2">Please wait while we grant you access to the dashboard.</p>
    {:else if status === 'success'}
      <h1 class="text-2xl font-bold text-green-600">Success!</h1>
      <p class="text-gray-600 mt-2">Access granted. You will be redirected to your dashboards shortly.</p>
    {:else if status === 'error'}
      <h1 class="text-2xl font-bold text-red-600">Redemption Failed</h1>
      <p class="text-gray-600 mt-2">{errorMessage}</p>
      <a href="/viewer/dashboards" class="mt-4 inline-block bg-blue-600 text-white font-semibold px-4 py-2 rounded hover:bg-blue-700 transition">Go to My Dashboards</a>
    {/if}
  </div>
</div>
