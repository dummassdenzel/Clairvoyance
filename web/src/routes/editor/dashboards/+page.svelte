<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import ShareModal from '$lib/components/ShareModal.svelte';
  import CreateDashboardModal from '$lib/components/CreateDashboardModal.svelte';

  const dashboards = writable<any[]>([]);
  const loading = writable(true);
  const error = writable<string | null>(null);

  let showShareModal = false;
  let showCreateModal = false;
  let currentShareLink = '';
  let sharingDashboardId: number | null = null;
  let deletingDashboardId: number | null = null;

  async function fetchDashboards() {
    loading.set(true);
    error.set(null);
    try {
      const data = await api.getDashboards();
      dashboards.set(data.data.dashboards || []);
    } catch (e) {
      error.set('Failed to load dashboards');
    }
    loading.set(false);
  }

  async function handleShare(dashboardId: number) {
    sharingDashboardId = dashboardId;
    try {
      const res = await api.generateShareLink(String(dashboardId));
      if (res.status === 'success' && res.data.token) {
        const baseUrl = window.location.origin;
        currentShareLink = `${baseUrl}/viewer/redeem/${res.data.token}`;
        showShareModal = true;
      } else {
        alert(res.message || 'Failed to generate share link.');
      }
    } catch (err) {
      alert('An unexpected error occurred while generating the link.');
    }
    sharingDashboardId = null;
  }

  async function handleDelete(dashboardId: number) {
    if (!window.confirm('Are you sure you want to delete this dashboard? This action cannot be undone.')) {
      return;
    }

    deletingDashboardId = dashboardId;
    try {
      const result = await api.deleteDashboard(String(dashboardId));
      if (result.status === 'success') {
        dashboards.update(currentDashboards => currentDashboards.filter(d => d.id !== dashboardId));
      } else {
        alert(result.message || 'Failed to delete dashboard.');
      }
    } catch (err) {
      alert('An unexpected error occurred while deleting the dashboard.');
    } finally {
      deletingDashboardId = null;
    }
  }

  onMount(fetchDashboards);
</script>

<svelte:head>
  <title>Editor Dashboards</title>
</svelte:head>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
  <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">My Dashboards</h1>
      <p class="mt-1 text-sm text-gray-600">Manage and view your KPI dashboards.</p>
    </div>
    <button on:click={() => showCreateModal = true} class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
      + Create Dashboard
    </button>
  </div>

  {#if $loading}
    <div class="text-center py-12 text-gray-500">Loading dashboards...</div>
  {:else if $error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <strong class="font-bold">Error:</strong>
      <span class="block sm:inline">{$error}</span>
    </div>
  {:else if $dashboards.length === 0}
    <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-lg">
      <h3 class="text-lg font-medium">No dashboards yet</h3>
      <p class="mt-1 text-sm">Click the "Create Dashboard" button to get started.</p>
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      {#each $dashboards as dash (dash.id)}
        <div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col">
          <div class="p-6 flex-grow">
            <h3 class="font-semibold text-lg text-gray-900">
              <a href={`/editor/dashboards/${dash.id}`} class="hover:text-blue-700 transition">{dash.name}</a>
            </h3>
            <p class="mt-2 text-sm text-gray-600 line-clamp-2">{dash.description || 'No description provided.'}</p>
          </div>
          <div class="p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <button
              on:click={() => handleDelete(dash.id)}
              disabled={deletingDashboardId === dash.id}
              class="inline-flex items-center justify-center rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
              {deletingDashboardId === dash.id ? 'Deleting...' : 'Delete'}
            </button>
            <div class="flex items-center space-x-3">
              <button 
                on:click={() => handleShare(dash.id)} 
                disabled={sharingDashboardId === dash.id}
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                {sharingDashboardId === dash.id ? 'Sharing...' : 'Share'}
              </button>
              <a href={`/editor/dashboards/${dash.id}`} class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">View</a>
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<ShareModal bind:show={showShareModal} shareLink={currentShareLink} on:close={() => showShareModal = false} />
<CreateDashboardModal bind:show={showCreateModal} on:success={fetchDashboards} />