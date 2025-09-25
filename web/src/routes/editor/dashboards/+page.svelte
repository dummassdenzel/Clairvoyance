<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import type { Dashboard, ApiResponse, ShareToken } from '$lib/types';
  import ShareModal from '$lib/components/ShareModal.svelte';
  import CreateDashboardModal from '$lib/components/CreateDashboardModal.svelte';
  import EditDashboardModal from '$lib/components/EditDashboardModal.svelte';

  const dashboards = writable<Dashboard[]>([]);
  const ownedDashboards = writable<Dashboard[]>([]);
  const sharedDashboards = writable<Dashboard[]>([]);
  const loading = writable(true);
  const error = writable<string | null>(null);

  let showShareModal = false;
  let showCreateModal = false;
  let showEditModal = false;
  let currentShareLink = '';
  let sharingDashboardId: number | null = null;
  let deletingDashboardId: number | null = null;
  let openDropdownId: number | null = null;
  let editingDashboard: Dashboard | null = null;

  async function fetchDashboards() {
    loading.set(true);
    error.set(null);
    try {
      const response: ApiResponse<{ dashboards: Dashboard[] }> = await api.getDashboards();
      
      if (response.success && response.data?.dashboards) {
        // Ensure unique dashboards by ID (frontend safeguard)
        const uniqueDashboards = response.data.dashboards.filter((dashboard, index, self) => 
          index === self.findIndex(d => d.id === dashboard.id)
        );
        dashboards.set(uniqueDashboards);
        
        // Categorize dashboards based on permission level
        const owned = uniqueDashboards.filter(d => d.permission_level === 'owner');
        const shared = uniqueDashboards.filter(d => d.permission_level !== 'owner');
        
        ownedDashboards.set(owned);
        sharedDashboards.set(shared);
      } else {
        error.set(response.message || 'Failed to load dashboards');
      }
    } catch (e) {
      error.set(e instanceof Error ? e.message : 'Failed to load dashboards');
    }
    loading.set(false);
  }

  function toggleDropdown(id: number) {
    openDropdownId = openDropdownId === id ? null : id;
  }

  function handleEditClick(dashboard: Dashboard) {
    editingDashboard = dashboard;
    showEditModal = true;
    openDropdownId = null;
  }

  async function handleShare(dashboardId: number) {
    sharingDashboardId = dashboardId;
    try {
      // Use the correct function name and parameters
      const response: ApiResponse<{ token: ShareToken }> = await api.generateShareToken(dashboardId, '7'); // 7 days
      
      if (response.success && response.data?.token) {
        const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';
        currentShareLink = `${baseUrl}/dashboards/share/${response.data.token.token}`;
        showShareModal = true;
      } else {
        alert(response.message || 'Failed to generate share link.');
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'An unexpected error occurred while generating the link.');
    }
    sharingDashboardId = null;
  }

  async function handleDelete(dashboardId: number) {
    if (typeof window !== 'undefined' && !window.confirm('Are you sure you want to delete this dashboard? This action cannot be undone.')) {
      return;
    }

    deletingDashboardId = dashboardId;
    try {
      const response: ApiResponse = await api.deleteDashboard(dashboardId);
      
      if (response.success) {
        // Update all dashboard stores
        dashboards.update(currentDashboards => currentDashboards.filter(d => d.id !== dashboardId));
        ownedDashboards.update(currentOwned => currentOwned.filter(d => d.id !== dashboardId));
        sharedDashboards.update(currentShared => currentShared.filter(d => d.id !== dashboardId));
      } else {
        alert(response.message || 'Failed to delete dashboard.');
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'An unexpected error occurred while deleting the dashboard.');
    } finally {
      deletingDashboardId = null;
    }
  }

  // Handle modal events
  function handleCreateSuccess() {
    showCreateModal = false;
    fetchDashboards(); // Refresh the dashboard list
  }

  function handleCreateClose() {
    showCreateModal = false;
  }

  function handleEditSuccess() {
    showEditModal = false;
    fetchDashboards(); // Refresh the dashboard list
  }

  function handleEditClose() {
    showEditModal = false;
  }

  onMount(() => {
    fetchDashboards();
    const handleGlobalClick = () => { openDropdownId = null; };
    
    if (typeof window !== 'undefined') {
      window.addEventListener('click', handleGlobalClick);
      return () => window.removeEventListener('click', handleGlobalClick);
    }
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
    <button 
      on:click={() => showCreateModal = true} 
      class="inline-flex items-center gap-2 justify-center rounded-md border border-transparent bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
      </svg>
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
    <div class="space-y-8">
      <!-- Owned Dashboards Section -->
      {#if $ownedDashboards.length > 0}
        <div>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">My Dashboards</h2>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
            {#each $ownedDashboards as dash (dash.id)}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col transition hover:shadow-lg hover:border-blue-500">
          <a href={`/editor/dashboards/${dash.id}`} class="p-5 flex-grow block">
            <h3 class="font-semibold text-lg text-gray-900 truncate">{dash.name}</h3>
            <p class="mt-2 text-sm text-gray-500 line-clamp-2 h-10">{dash.description || 'No description provided.'}</p>
          </a>
          <div class="p-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <a href={`/editor/dashboards/${dash.id}`} class="px-3 text-xs text-gray-500">View Dashboard &rarr;</a>
            <div class="relative">
              <button 
                on:click|stopPropagation={() => toggleDropdown(dash.id)} 
                aria-label="Dashboard options"
                class="p-1 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
              </button>
              {#if openDropdownId === dash.id}
                <div 
                  on:click|stopPropagation 
                  on:keydown={() => {}} 
                  role="menu" 
                  tabindex="-1" 
                  class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-md shadow-lg z-20 border border-gray-200"
                >
                  <button 
                    on:click={() => handleEditClick(dash)} 
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                      <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                    </svg>
                    <span>Edit</span>
                  </button>
                  <button 
                    on:click={() => { handleShare(dash.id); openDropdownId = null; }} 
                    disabled={sharingDashboardId === dash.id} 
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3 disabled:opacity-50"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
                    </svg>
                    <span>{sharingDashboardId === dash.id ? 'Sharing...' : 'Share'}</span>
                  </button>
                  <button 
                    on:click={() => { handleDelete(dash.id); openDropdownId = null; }} 
                    disabled={deletingDashboardId === dash.id} 
                    class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 flex items-center gap-3 disabled:opacity-50"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>{deletingDashboardId === dash.id ? 'Deleting...' : 'Delete'}</span>
                  </button>
                </div>
              {/if}
            </div>
          </div>
        </div>
            {/each}
          </div>
        </div>
      {/if}

      <!-- Shared Dashboards Section -->
      {#if $sharedDashboards.length > 0}
        <div>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Shared with Me</h2>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
            {#each $sharedDashboards as dash (dash.id)}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col transition hover:shadow-lg hover:border-blue-500">
          <a href={`/editor/dashboards/${dash.id}`} class="p-5 flex-grow block">
            <h3 class="font-semibold text-lg text-gray-900 truncate">{dash.name}</h3>
            <p class="mt-2 text-sm text-gray-500 line-clamp-2 h-10">{dash.description || 'No description provided.'}</p>
            <div class="mt-2 flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {dash.permission_level || 'viewer'}
              </span>
              <span class="text-xs text-gray-500">by {dash.owner_email || 'Unknown'}</span>
            </div>
          </a>
          <div class="p-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <a href={`/editor/dashboards/${dash.id}`} class="px-3 text-xs text-gray-500">View Dashboard &rarr;</a>
            <div class="relative">
              <button 
                on:click|stopPropagation={() => toggleDropdown(dash.id)} 
                aria-label="Dashboard options"
                class="p-1 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
              </button>
              {#if openDropdownId === dash.id}
                <div 
                  on:click|stopPropagation 
                  on:keydown={() => {}} 
                  role="menu" 
                  tabindex="-1" 
                  class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-md shadow-lg z-20 border border-gray-200"
                >
                  <button 
                    on:click={() => handleEditClick(dash)} 
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                      <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                    </svg>
                    <span>Edit</span>
                  </button>
                  <button 
                    on:click={() => { handleShare(dash.id); openDropdownId = null; }} 
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
                    </svg>
                    <span>{sharingDashboardId === dash.id ? 'Sharing...' : 'Share'}</span>
                  </button>
                </div>
              {/if}
            </div>
          </div>
        </div>
            {/each}
          </div>
        </div>
      {/if}
    </div>
  {/if}
</div>

<ShareModal bind:show={showShareModal} shareLink={currentShareLink} on:close={() => showShareModal = false} />
<CreateDashboardModal 
  bind:show={showCreateModal} 
  on:success={handleCreateSuccess}
  on:close={handleCreateClose}
/>
<EditDashboardModal 
  bind:show={showEditModal} 
  dashboard={editingDashboard} 
  on:success={handleEditSuccess}
  on:close={handleEditClose}
/>