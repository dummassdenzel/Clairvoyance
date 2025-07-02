<script lang="ts">
  import { page } from '$app/stores';
  import { user } from '$lib/stores/auth';
  import { onMount } from 'svelte';
  import { writable, get } from 'svelte/store';
  import Chart from 'chart.js/auto';
  import { onDestroy } from 'svelte';
  import * as api from '$lib/services/api';

  const dashboard = writable<any>(null);
  const loading = writable(true);
  const error = writable<string | null>(null);
  let csvFile: File | null = null;
  let csvResult: any = null;
  let uploading = false;



  let removingViewerId: string | null = null;

  let chartInstances: Chart[] = [];

  // Store KPI data for widgets
  let widgetKpiData: Record<number, { labels: string[]; values: number[] }> = {};
  let widgetDataLoading = false;

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



  async function fetchWidgetKpiData(widgets: any[]) {
    widgetKpiData = {};
    widgetDataLoading = true;
    try {
      await Promise.all(widgets.map(async (widget: any) => {
        if (widget.kpi_id) {
          try {
            const data = await api.getKpiEntries(widget.kpi_id);
            if (data && !data.error) {
              widgetKpiData[widget.kpi_id] = {
                labels: data.map((d: any) => d.date),
                values: data.map((d: any) => Number(d.value))
              };
            }
          } catch (e) {
            console.error(`Failed to load KPI data for widget ${widget.kpi_id}:`, e);
          }
        }
      }));
    } catch (e) {
      console.error('An error occurred while fetching widget data:', e);
    } finally {
      widgetDataLoading = false;
    }
  }

  async function handleCsvUpload(event: Event) {
    event.preventDefault();
    if (!csvFile) return;
    uploading = true;
    csvResult = null;
    const data = await api.uploadKpiCsv(csvFile);
    csvResult = data;
    uploading = false;
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
  $: if (widgetsArr && widgetsArr.length > 0) {
    fetchWidgetKpiData(widgetsArr).then(() => setTimeout(() => renderCharts(widgetsArr), 0));
  }
  onDestroy(() => { chartInstances.forEach(c => c.destroy()); });

  function renderCharts(widgets: any[]) {
    // Clean up previous charts
    chartInstances.forEach(c => c.destroy());
    chartInstances = [];
    widgets?.forEach((widget: any, i: number) => {
      if (widget.type === 'bar' || widget.type === 'line') {
        const ctx = document.getElementById(`widget-chart-${i}`) as HTMLCanvasElement;
        if (ctx) {
          let labels = ['Jan', 'Feb', 'Mar', 'Apr'];
          let values = [10, 20, 15, 30];
          if (widget.kpi_id && widgetKpiData[widget.kpi_id]) {
            labels = widgetKpiData[widget.kpi_id].labels;
            values = widgetKpiData[widget.kpi_id].values;
          }
          const chart = new Chart(ctx, {
            type: widget.type,
            data: {
              labels,
              datasets: [{
                label: widget.title || 'Sample',
                data: values,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2
              }]
            },
            options: { responsive: true, plugins: { legend: { display: true } } }
          });
          chartInstances.push(chart);
        }
      }
    });
  }
</script>

<div class="max-w-2xl mx-auto mt-8 p-4">
  {#if $loading}
    <div>Loading...</div>
  {:else if $error}
    <div class="text-red-500">{$error}</div>
  {:else if !$user}
    <div class="text-gray-600">Please log in to view this dashboard.</div>
  {:else if !$dashboard}
    <div class="text-gray-600">Dashboard not found or you do not have access.</div>
  {:else}
    <h1 class="text-2xl font-bold mb-4">{$dashboard.name}</h1>
    <!-- Widget Visualization -->
    {#if widgetsArr.length > 0}
      {#if widgetDataLoading}
        <div class="mb-6">Loading widget data...</div>
      {:else}
        <div class="grid gap-6 mb-6">
          {#each widgetsArr as widget, i}
            <div class="bg-white rounded shadow p-4">
              <div class="font-semibold mb-2">{widget.title || widget.type}</div>
              {#if widget.type === 'bar' || widget.type === 'line'}
                <canvas id={`widget-chart-${i}`} class="w-full h-64"></canvas>
              {:else if widget.type === 'table'}
                <div class="overflow-x-auto">
                  <table class="min-w-full text-sm border border-gray-200 divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-600">Value</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      {#if widget.kpi_id && widgetKpiData[widget.kpi_id] && widgetKpiData[widget.kpi_id].labels.length > 0}
                        {#each widgetKpiData[widget.kpi_id].labels as label, j}
                          <tr>
                            <td class="px-4 py-2 whitespace-nowrap">{label}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{widgetKpiData[widget.kpi_id].values[j]}</td>
                          </tr>
                        {/each}
                      {:else}
                        <tr>
                          <td colspan="2" class="px-4 py-2 text-center text-gray-500">No data available for this KPI.</td>
                        </tr>
                      {/if}
                    </tbody>
                  </table>
                </div>
              {:else}
                <div class="text-gray-400">Unknown widget type: {widget.type}</div>
              {/if}
            </div>
          {/each}
        </div>

        <!-- Viewers List -->
        {#if isEditor && $dashboard.viewers && $dashboard.viewers.length > 0}
          <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Current Viewers</h2>
            <ul class="bg-white rounded-lg shadow overflow-hidden divide-y divide-gray-200">
              {#each $dashboard.viewers as viewer}
                <li class="p-4 flex items-center justify-between">
                  <span class="text-gray-800">{viewer.email}</span>
                  <button 
                    on:click={() => handleRemoveViewer(viewer.id)} 
                    disabled={removingViewerId === viewer.id}
                    class="text-sm text-red-600 hover:text-red-800 font-semibold py-1 px-3 rounded border border-red-300 hover:border-red-500 transition disabled:opacity-50">
                    {removingViewerId === viewer.id ? 'Removing...' : 'Remove'}
                  </button>
                </li>
              {/each}
            </ul>
          </div>
        {/if}
      {/if}
    {/if}
    <!-- End Widget Visualization -->
    <!-- Viewers List -->
    {#if isEditor && $dashboard.viewers && $dashboard.viewers.length > 0}
      <div class="mt-8 mb-6">
        <h2 class="text-xl font-bold mb-4">Current Viewers</h2>
        <ul class="bg-white rounded-lg shadow overflow-hidden divide-y divide-gray-200">
          {#each $dashboard.viewers as viewer}
            <li class="p-4 flex items-center justify-between">
              <span class="text-gray-800">{viewer.email}</span>
              <button 
                on:click={() => handleRemoveViewer(viewer.id)} 
                disabled={removingViewerId === viewer.id}
                class="text-sm text-red-600 hover:text-red-800 font-semibold py-1 px-3 rounded border border-red-300 hover:border-red-500 transition disabled:opacity-50">
                {removingViewerId === viewer.id ? 'Removing...' : 'Remove'}
              </button>
            </li>
          {/each}
        </ul>
      </div>
    {/if}
    {#if isEditor}
      <form class="mt-6 space-y-2" on:submit={handleCsvUpload}>
        <label class="block text-sm font-medium text-gray-700">Upload KPI Entries (CSV)</label>
        <input type="file" accept=".csv" on:change={e => {
          const input = e.target as HTMLInputElement;
          if (input && input.files && input.files.length > 0) {
            csvFile = input.files[0];
          }
        }} class="border rounded px-3 py-2 w-full" required />
        <button class="rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50" type="submit" disabled={uploading}>{uploading ? 'Uploading...' : 'Upload CSV'}</button>
      </form>
      {#if csvResult}
        <div class="mt-2 text-sm">
          <div>Inserted: {csvResult.inserted}</div>
          <div>Failed: {csvResult.failed}</div>
          {#if csvResult.errors && csvResult.errors.length > 0}
            <div class="text-red-500">Errors:</div>
            <ul class="list-disc ml-6">
              {#each csvResult.errors as err}
                <li>{err.error}</li>
              {/each}
            </ul>
          {/if}
        </div>
      {/if}

    {/if}
  {/if}
</div> 