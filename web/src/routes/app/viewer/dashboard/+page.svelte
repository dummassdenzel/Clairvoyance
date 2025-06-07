<!-- Viewer Dashboard -->
<script lang="ts">
  import { onMount } from 'svelte';
  import { dashboards, fetchDashboards } from '$lib/stores/dashboard';
  import { kpis, fetchKPIs } from '$lib/stores/kpi';
  import { token } from '$lib/stores/auth';

  let loading = true;
  let error: string | null = null;

  onMount(async () => {
    try {
      loading = true;
      await Promise.all([
        fetchDashboards(),
        fetchKPIs()
      ]);
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to load dashboard data';
    } finally {
      loading = false;
    }
  });
</script>

<div class="space-y-6">
  <!-- Header -->
  <div class="md:flex md:items-center md:justify-between">
    <div class="flex-1 min-w-0">
      <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Dashboard
      </h2>
    </div>
  </div>

  <!-- Loading State -->
  {#if loading}
    <div class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
    </div>
  <!-- Error State -->
  {:else if error}
    <div class="rounded-md bg-red-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading dashboard</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{error}</p>
          </div>
        </div>
      </div>
    </div>
  <!-- Dashboard Content -->
  {:else}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      {#each $dashboards as dashboard}
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              {dashboard.name}
            </h3>
            {#if dashboard.description}
              <p class="mt-1 text-sm text-gray-500">
                {dashboard.description}
              </p>
            {/if}
            <div class="mt-4">
              <a
                href="/app/viewer/dashboard/{dashboard.id}"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                View Dashboard
              </a>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div> 