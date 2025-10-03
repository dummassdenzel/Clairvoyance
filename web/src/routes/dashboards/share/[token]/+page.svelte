<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { user, authLoaded } from '$lib/stores/auth';
  import * as api from '$lib/services/api';
  import type { ApiResponse, Dashboard } from '$lib/types';


  let loading = true;
  let error = '';
  let success = false;
  let dashboard: Dashboard | null = null;
  let authCheckComplete = false;
  let token = '';

  // Debug: Show user state
  $: console.log('User:', $user, 'Auth loaded:', $authLoaded);

  async function redeemShareToken() {
    loading = true;
    error = '';
    success = false;

    try {
      // Check if user is logged in
      console.log('User state:', $user);
      if (!$user) {
        error = 'You must be logged in to access this shared dashboard.';
        loading = false;
        return;
      }

      // Redeem the share token
      console.log('Attempting to redeem token:', token);
      const response: ApiResponse<{ dashboard_id: number }> = await api.redeemShareToken(token);
      console.log('API response:', response);
      
      if (response.success && response.data?.dashboard_id) {
        success = true;
        const dashboardId = response.data.dashboard_id;
        
        // Redirect to the dashboard after a short delay
        setTimeout(() => {
          goto(`/editor/dashboards/${dashboardId}`);
        }, 2000);
      } else {
        error = response.message || response.error || 'Failed to redeem share token.';
        console.error('Token redemption failed:', response);
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'An unexpected error occurred.';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    // Extract token from URL parameters
    token = $page.params.token;
    console.log('Token from URL params:', token);
    
    if (!token) {
      error = 'Invalid share link - no token provided.';
      loading = false;
      return;
    }
    
    // Wait for authentication to be loaded before checking user
    const unsubscribe = authLoaded.subscribe((loaded) => {
      if (loaded) {
        authCheckComplete = true;
        redeemShareToken();
        unsubscribe();
      }
    });
    
    return unsubscribe;
  });
</script>

<svelte:head>
  <title>Access Shared Dashboard - Clairvoyance</title>
</svelte:head>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
  <div class="sm:mx-auto sm:w-full sm:max-w-md">
    <div class="flex justify-center">
      <img src="/clairvoyance-logo.png" alt="Clairvoyance Logo" class="h-12" />
    </div>
    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
      Access Shared Dashboard
    </h2>
    <p class="mt-2 text-center text-sm text-gray-600">
      You're being granted access to a shared dashboard
    </p>
  </div>

  <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
    <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
      
      {#if loading}
        <div class="text-center">
          <div class="flex justify-center">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <p class="mt-4 text-sm text-gray-600">
            {authCheckComplete ? 'Processing your access request...' : 'Checking authentication...'}
          </p>
        </div>
      {:else if success}
        <div class="text-center">
          <div class="flex justify-center">
            <svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Access Granted!</h3>
          <p class="mt-2 text-sm text-gray-600">
            You now have access to the shared dashboard. Redirecting you now...
          </p>
          <div class="mt-4">
            <div class="flex justify-center">
              <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
        </div>
      {:else if error}
        <div class="text-center">
          <div class="flex justify-center">
            <svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Access Denied</h3>
          <p class="mt-2 text-sm text-gray-600">{error}</p>
          
          {#if error.includes('logged in')}
            <div class="mt-6">
              <a 
                href="/auth/login" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Log In
              </a>
            </div>
          {:else}
            <div class="mt-6">
              <a 
                href="/editor/dashboards" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Go to Dashboards
              </a>
            </div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
</div>
