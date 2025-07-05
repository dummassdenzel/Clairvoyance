<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
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
  let openDropdownId: number | null = null;

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

  function toggleDropdown(id: number) {
    openDropdownId = openDropdownId === id ? null : id;
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

  onMount(() => {
    fetchDashboards();
    const handleGlobalClick = () => { openDropdownId = null; };
    window.addEventListener('click', handleGlobalClick);

    return () => window.removeEventListener('click', handleGlobalClick);
  });

</script>

<svelte:head>
  <title>Editor | My Dashboards</title>
</svelte:head>

<div class="space-y-8">
  <div class="flex justify-between items-start">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">My Dashboards</h1>
      <p class="mt-1 text-sm text-gray-600">Create, manage, and share your KPI dashboards.</p>
    </div>
    <button on:click={() => showCreateModal = true} class="inline-flex items-center gap-2 justify-center rounded-md border border-transparent bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
      <span>Create Dashboard</span>
    </button>
  </div>

  {#if $loading}
    <div class="text-center py-12 text-gray-500">Loading dashboards...</div>
  {:else if $error}
    <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4" role="alert">
      <p class="font-bold">Error</p>
      <p>{$error}</p>
    </div>
  {:else if $dashboards.length === 0}
    <div class="text-center py-20 px-6 bg-white rounded-lg border-2 border-dashed border-gray-300">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No dashboards yet</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a new dashboard.</p>
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {#each $dashboards as dash (dash.id)}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col transition hover:shadow-lg hover:border-blue-500">
          <a href={`/editor/dashboards/${dash.id}`} class="p-5 flex-grow block">
            <h3 class="font-semibold text-lg text-gray-900 truncate">{dash.name}</h3>
            <p class="mt-2 text-sm text-gray-500 line-clamp-2 h-10">{dash.description || 'No description provided.'}</p>
          </a>
          <div class="p-3 bg-gray-50 border-t border-gray-200 flex items-center justify-end">
            <div class="relative">
              <button 
                on:click|stopPropagation={() => toggleDropdown(dash.id)} 
                class="p-1 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg>
              </button>
              {#if openDropdownId === dash.id}
                <div on:click|stopPropagation class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-md shadow-lg z-20 border border-gray-200">
                  <button on:click={() => { handleShare(dash.id); openDropdownId = null; }} disabled={sharingDashboardId === dash.id} class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" /></svg>
                    <span>{sharingDashboardId === dash.id ? 'Sharing...' : 'Share'}</span>
                  </button>
                  <button on:click={() => { handleDelete(dash.id); openDropdownId = null; }} disabled={deletingDashboardId === dash.id} class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 flex items-center gap-3 disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    <span>{deletingDashboardId === dash.id ? 'Deleting...' : 'Delete'}</span>
                  </button>
                </div>
              {/if}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<ShareModal bind:show={showShareModal} shareLink={currentShareLink} on:close={() => showShareModal = false} />
<CreateDashboardModal bind:show={showCreateModal} on:success={fetchDashboards} />