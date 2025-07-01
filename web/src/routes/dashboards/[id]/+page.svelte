<script lang="ts">
  import { page } from '$app/stores';
  import { user } from '$lib/stores/auth';
  import { onMount } from 'svelte';
  import { writable, get } from 'svelte/store';
  import Chart from 'chart.js/auto';
  import { onDestroy } from 'svelte';

  const dashboard = writable<any>(null);
  const loading = writable(true);
  const error = writable<string | null>(null);
  let csvFile: File | null = null;
  let csvResult: any = null;
  let uploading = false;

  // Dashboard sharing state
  const users = writable<any[]>([]);
  const viewers = writable<any[]>([]);
  let selectedViewer = '';
  let shareError: string | null = null;
  let shareSuccess: string | null = null;
  let sharing = false;

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
      const res = await fetch(`/api/routes/dashboards.php?id=${dashboardId}`, {
        credentials: 'include'
      });
      const data = await res.json();
      if (data.error) {
        error.set(data.error);
        dashboard.set(null);
      } else {
        dashboard.set(data);
      }
    } catch (e) {
      error.set('Failed to load dashboard');
      dashboard.set(null);
    }
    loading.set(false);
  }

  async function fetchUsers() {
    try {
      const res = await fetch('/api/routes/users.php', { credentials: 'include' });
      const data = await res.json();
      users.set(data.users || []);
    } catch (e) {}
  }

  async function fetchViewers() {
    // For now, fetch all users with role viewer and assume all are assignable
    const allUsers = get(users);
    if ($dashboard && allUsers) {
      // You may want to fetch assigned viewers from the backend in the future
      viewers.set(allUsers.filter(u => u.role === 'viewer'));
    }
  }

  async function handleCsvUpload(event: Event) {
    event.preventDefault();
    if (!csvFile) return;
    uploading = true;
    csvResult = null;
    const formData = new FormData();
    formData.append('file', csvFile);
    const res = await fetch('/api/routes/kpi_entries.php?action=upload_csv', {
      method: 'POST',
      credentials: 'include',
      body: formData
    });
    csvResult = await res.json();
    uploading = false;
  }

  async function handleAssignViewer(event: Event) {
    event.preventDefault();
    shareError = null;
    shareSuccess = null;
    sharing = true;
    const res = await fetch('/api/routes/dashboards.php?action=assign_viewer', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ dashboard_id: dashboardId, user_id: selectedViewer })
    });
    const data = await res.json();
    sharing = false;
    if (data.success) {
      shareSuccess = 'Viewer assigned!';
      selectedViewer = '';
    } else {
      shareError = data.error || 'Failed to assign viewer.';
    }
  }

  async function handleRemoveViewer(viewerId: string) {
    removingViewerId = viewerId;
    const res = await fetch('/api/routes/dashboards.php?action=remove_viewer', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ dashboard_id: dashboardId, user_id: viewerId })
    });
    await res.json();
    removingViewerId = null;
    fetchDashboard();
  }

  async function fetchWidgetKpiData(widgets: any[]) {
    widgetKpiData = {};
    widgetDataLoading = true;
    await Promise.all(widgets.map(async (widget: any) => {
      if (widget.kpi_id) {
        const res = await fetch(`/api/routes/kpi_entries.php?kpi_id=${widget.kpi_id}`, { credentials: 'include' });
        const data = await res.json();
        widgetKpiData[widget.kpi_id] = {
          labels: data.map((d: any) => d.date),
          values: data.map((d: any) => Number(d.value))
        };
      }
    }));
    widgetDataLoading = false;
  }

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

  onMount(() => {
    fetchDashboard();
    if (isEditor) {
      fetchUsers();
    }
  });
  $: if (isEditor && $users) fetchViewers();
  $: widgetsArr = $dashboard?.widgets || $dashboard?.layout || [];
  $: if (widgetsArr && widgetsArr.length > 0) {
    fetchWidgetKpiData(widgetsArr).then(() => setTimeout(() => renderCharts(widgetsArr), 0));
  }
  onDestroy(() => { chartInstances.forEach(c => c.destroy()); });
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
                  <table class="min-w-full text-xs border">
                    <thead>
                      <tr><th class="border px-2 py-1">Header 1</th><th class="border px-2 py-1">Header 2</th></tr>
                    </thead>
                    <tbody>
                      <tr><td class="border px-2 py-1">Value 1</td><td class="border px-2 py-1">Value 2</td></tr>
                      <tr><td class="border px-2 py-1">Value 3</td><td class="border px-2 py-1">Value 4</td></tr>
                    </tbody>
                  </table>
                </div>
              {:else}
                <div class="text-gray-400">Unknown widget type: {widget.type}</div>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    {/if}
    <!-- End Widget Visualization -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Widgets (JSON)</label>
      <pre class="bg-gray-100 rounded p-2 text-xs overflow-x-auto">{JSON.stringify($dashboard.widgets || $dashboard.layout, null, 2)}</pre>
    </div>
    {#if $dashboard.viewers && $dashboard.viewers.length > 0}
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Viewers</label>
        <ul class="list-disc ml-6 text-sm">
          {#each $dashboard.viewers as v}
            <li class="flex items-center gap-2">{v.email}
              {#if isEditor}
                <button class="btn btn-xs btn-outline" on:click={() => handleRemoveViewer(v.id)} disabled={removingViewerId === v.id}>
                  {removingViewerId === v.id ? 'Removing...' : 'Remove'}
                </button>
              {/if}
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
        }} class="input input-bordered w-full" required />
        <button class="btn btn-primary" type="submit" disabled={uploading}>{uploading ? 'Uploading...' : 'Upload CSV'}</button>
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
      <!-- Dashboard Sharing UI -->
      <form class="mt-6 space-y-2" on:submit={handleAssignViewer}>
        <label class="block text-sm font-medium text-gray-700">Assign Viewer</label>
        <select class="input input-bordered w-full" bind:value={selectedViewer} required>
          <option value="" disabled selected>Select a viewer...</option>
          {#each $users.filter(u => u.role === 'viewer') as viewer}
            <option value={viewer.id}>{viewer.email}</option>
          {/each}
        </select>
        <button class="btn btn-primary" type="submit" disabled={sharing || !selectedViewer}>{sharing ? 'Assigning...' : 'Assign Viewer'}</button>
        {#if shareError}
          <div class="text-red-500 text-sm">{shareError}</div>
        {/if}
        {#if shareSuccess}
          <div class="text-green-600 text-sm">{shareSuccess}</div>
        {/if}
      </form>
      <!-- End Dashboard Sharing UI -->
    {/if}
  {/if}
</div>

<style>
  .input { @apply border rounded px-3 py-2; }
  .btn { @apply rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50; }
  .btn-outline { @apply border border-blue-600 text-blue-600 bg-white hover:bg-blue-50; }
  .btn-xs { @apply text-xs px-2 py-1; }
</style> 