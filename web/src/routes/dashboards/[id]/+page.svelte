<script lang="ts">
  import { page } from '$app/stores';
  import { user } from '$lib/stores/auth';
  import { onMount } from 'svelte';
  import { writable, get } from 'svelte/store';

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

  onMount(() => {
    fetchDashboard();
    if (isEditor) {
      fetchUsers();
    }
  });
  $: if (isEditor && $users) fetchViewers();
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
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Widgets (JSON)</label>
      <pre class="bg-gray-100 rounded p-2 text-xs overflow-x-auto">{JSON.stringify($dashboard.widgets || $dashboard.layout, null, 2)}</pre>
    </div>
    {#if $dashboard.viewers && $dashboard.viewers.length > 0}
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Viewers</label>
        <ul class="list-disc ml-6 text-sm">
          {#each $dashboard.viewers as v}
            <li>{v.email}</li>
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
</style> 