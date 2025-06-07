<!-- Individual Dashboard View -->
<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';
  import { dashboards, currentDashboard, fetchDashboard } from '$lib/stores/dashboard';
  import { fetchAllWidgetsForUser } from '$lib/stores/widget';
  import { kpis, fetchKPIs } from '$lib/stores/kpi';
  import { token } from '$lib/stores/auth';
  import type { Widget } from '$lib/stores/dashboard';

  let loading = true;
  let error: string | null = null;
  const dashboardId = $page.params.id;
  const widgets = writable<Widget[]>([]);

  onMount(async () => {
    try {
      loading = true;
      const [dashboardData, widgetsData] = await Promise.all([
        fetchDashboard(parseInt(dashboardId)),
        fetchAllWidgetsForUser()
      ]);
      
      // Filter widgets for this dashboard
      widgets.set(widgetsData.filter(w => w.dashboard_id === parseInt(dashboardId)));
      await fetchKPIs();
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to load dashboard';
    } finally {
      loading = false;
    }
  });

  // Get current dashboard
  $: currentDashboardData = $currentDashboard;
</script>

<div class="space-y-6">
  <!-- Header -->
  <div class="md:flex md:items-center md:justify-between">
    <div class="flex-1 min-w-0">
      <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        {currentDashboardData?.name || 'Dashboard'}
      </h2>
      {#if currentDashboardData?.description}
        <p class="mt-1 text-sm text-gray-500">
          {currentDashboardData.description}
        </p>
      {/if}
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
      <button
        type="button"
        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        on:click={() => window.location.reload()}
      >
        Refresh
      </button>
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
      {#each $widgets as widget}
        <div 
          class="bg-white overflow-hidden shadow rounded-lg"
          style="grid-column: span {widget.width}; grid-row: span {widget.height};"
        >
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              {widget.title}
            </h3>
            <!-- Widget content will be implemented based on widget_type -->
            <div class="mt-4">
              {#if widget.widget_type === 'line'}
                <!-- Line chart will be implemented -->
                <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                  <p class="text-gray-500">Line chart visualization</p>
                </div>
              {:else if widget.widget_type === 'bar'}
                <!-- Bar chart will be implemented -->
                <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                  <p class="text-gray-500">Bar chart visualization</p>
                </div>
              {:else if widget.widget_type === 'card'}
                <!-- Card widget will be implemented -->
                <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                  <p class="text-gray-500">Card visualization</p>
                </div>
              {:else if widget.widget_type === 'pie' || widget.widget_type === 'donut'}
                <!-- Pie/Donut chart will be implemented -->
                <div class="h-64 bg-gray-50 rounded flex items-center justify-center">
                  <p class="text-gray-500">{widget.widget_type === 'pie' ? 'Pie' : 'Donut'} chart visualization</p>
                </div>
              {/if}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div> 