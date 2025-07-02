<script lang="ts">
  import { page } from '$app/stores';
  import { user } from '$lib/stores/auth';
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import DashboardWidget from '$lib/components/DashboardWidget.svelte';

  const dashboard = writable<any>(null);
  const loading = writable(true);
  const error = writable<string | null>(null);
  let csvFile: File | null = null;
  let csvResult: any = null;
  let uploading = false;
  let selectedKpiForUpload: number | null = null;

  let removingViewerId: string | null = null;
  let editMode = false;

  function handleCancel() {
    editMode = false;
    fetchDashboard();
  }

  async function handleSave() {
    console.log('Saving layout...');
    editMode = false;
  }

  $: dashboardId = $page.params.id;
  $: isEditor = $user?.role === 'editor';

  async function fetchDashboard() {
    loading.set(true);
    error.set(null);
    try {
      const data = await api.getDashboard(dashboardId);
      if (data.error || !data.data || !data.data.dashboard) {
        error.set(data.error || 'Invalid dashboard data received.');
        dashboard.set(null);
      } else {
        dashboard.set(data.data.dashboard);
      }
    } catch (e) {
      error.set('Failed to load dashboard');
      dashboard.set(null);
    }
    loading.set(false);
  }





  async function handleCsvUpload(event: Event) {
    event.preventDefault();
    if (!csvFile || selectedKpiForUpload === null) return;
    uploading = true;
    csvResult = null;
    const data = await api.uploadKpiCsv(selectedKpiForUpload, csvFile);
    csvResult = data;
    uploading = false;
    // Refresh dashboard data to reload widgets
    fetchDashboard();
  }



  async function handleRemoveViewer(viewerId: string) {
    removingViewerId = viewerId;
    await api.removeViewer(dashboardId, viewerId);
    removingViewerId = null;
    fetchDashboard();
  }



  onMount(() => {
    fetchDashboard();
  });

  $: kpisForSelect = widgetsArr
    .filter(w => w.kpi_id)
    .map(w => ({ id: w.kpi_id, title: w.title || `KPI ${w.kpi_id}` }))
    .filter((kpi, index, self) => self.findIndex(k => k.id === kpi.id) === index);
  $: widgetsArr = (() => {
    const layoutData = $dashboard?.layout || $dashboard?.widgets;
    if (typeof layoutData === 'string') {
      try {
        const parsed = JSON.parse(layoutData);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        console.error("Failed to parse widgets JSON:", e);
        return [];
      }
    }
    if (Array.isArray(layoutData)) {
      return layoutData;
    }
    return [];
  })();
</script>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
  {#if $loading}
    <div class="text-center py-12">Loading...</div>
  {:else if $error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <strong class="font-bold">Error:</strong>
      <span class="block sm:inline">{$error}</span>
    </div>
  {:else if !$user}
    <div class="text-center py-12 text-gray-600">Please log in to view this dashboard.</div>
  {:else if !$dashboard}
    <div class="text-center py-12 text-gray-600">Dashboard not found or you do not have access.</div>
  {:else}
    <!-- Page Header -->
    <div class="mb-6">
      <div class="mb-2 text-sm text-gray-500">
        <a href="/editor/dashboards" class="hover:underline">Dashboards</a>
        <span class="mx-2">/</span>
        <span class="font-medium text-gray-700">{$dashboard.name}</span>
      </div>
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{$dashboard.name}</h1>
          {#if $dashboard.description}
            <p class="mt-1 text-sm text-gray-600">{$dashboard.description}</p>
          {/if}
        </div>

        {#if isEditor}
          <div class="flex-shrink-0 ml-4">
            {#if editMode}
              <div class="flex items-center space-x-2">
                <button on:click={handleCancel} class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                  Cancel
                </button>
                <button on:click={handleSave} class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                  Save Layout
                </button>
              </div>
            {:else}
              <button on:click={() => editMode = true} class="px-4 py-2 text-sm font-medium text-white bg-gray-600 border border-transparent rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Edit Layout
              </button>
            {/if}
          </div>
        {/if}
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 lg:gap-8">
      <!-- Main Content -->
      <div class="lg:col-span-2">
        {#if widgetsArr.length > 0}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {#each widgetsArr as widget (widget.id)}
              <DashboardWidget {widget} />
            {/each}
          </div>
        {:else}
          <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-medium">No widgets yet</h3>
            <p class="mt-1 text-sm">This dashboard doesn't have any widgets configured.</p>
          </div>
        {/if}
      </div>

      <!-- Sidebar -->
      <div class="space-y-6 mt-8 lg:mt-0">
        <!-- Viewers Card -->
        {#if isEditor}
          <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="p-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-800">Current Viewers</h3>
            </div>
            <div class="p-4">
              {#if $dashboard.viewers && $dashboard.viewers.length > 0}
                <ul class="divide-y divide-gray-200">
                  {#each $dashboard.viewers as viewer}
                    <li class="py-3 flex items-center justify-between">
                      <span class="text-sm text-gray-800">{viewer.email}</span>
                      <button 
                        on:click={() => handleRemoveViewer(viewer.id)} 
                        disabled={removingViewerId === viewer.id}
                        class="text-xs text-red-600 hover:text-red-800 font-semibold py-1 px-2 rounded border border-red-300 hover:border-red-500 transition disabled:opacity-50">
                        {removingViewerId === viewer.id ? 'Removing...' : 'Remove'}
                      </button>
                    </li>
                  {/each}
                </ul>
              {:else}
                <p class="text-sm text-gray-500">This dashboard has not been shared with any viewers.</p>
              {/if}
            </div>
          </div>
        {/if}

        <!-- Upload Card -->
        {#if isEditor}
          <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="p-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-800">Upload KPI Data</h3>
            </div>
            <div class="p-4">
              <form class="space-y-4" on:submit={handleCsvUpload}>
                <div>
                  <label for="kpi-select" class="block text-sm font-medium text-gray-700 mb-1">Select KPI</label>
                  <select id="kpi-select" bind:value={selectedKpiForUpload} class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                    <option value={null} disabled>-- Select a KPI --</option>
                    {#each kpisForSelect as kpi}
                      <option value={kpi.id}>{kpi.title}</option>
                    {/each}
                  </select>
                </div>

                <div>
                  <label for="csv-upload" class="block text-sm font-medium text-gray-700 mb-1">Choose CSV File</label>
                  <input id="csv-upload" type="file" accept=".csv" on:change={e => {
                    const input = e.target as HTMLInputElement;
                    if (input && input.files && input.files.length > 0) {
                      csvFile = input.files[0];
                    }
                  }} class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required />
                </div>

                <button class="w-full rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" type="submit" disabled={uploading || !selectedKpiForUpload || !csvFile}>
                  {uploading ? 'Uploading...' : 'Upload and Refresh'}
                </button>
              </form>
              {#if csvResult}
                <div class="mt-4 p-3 rounded-md bg-gray-50 text-sm">
                  <div><strong>Status:</strong> {csvResult.status}</div>
                  <div><strong>Inserted:</strong> {csvResult.inserted}</div>
                  <div><strong>Failed:</strong> {csvResult.failed}</div>
                  {#if csvResult.errors && csvResult.errors.length > 0}
                    <div class="text-red-500 mt-2"><strong>Errors:</strong></div>
                    <ul class="list-disc ml-6 text-red-600">
                      {#each csvResult.errors as err}
                        <li>{err.error} (Row: {err.row})</li>
                      {/each}
                    </ul>
                  {/if}
                </div>
              {/if}
            </div>
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div> 