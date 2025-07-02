<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import ShareModal from '$lib/components/ShareModal.svelte';

  const dashboards = writable<any[]>([]);
  const loading = writable(true);
  const error = writable<string | null>(null);

  let showShareModal = false;
  let currentShareLink = '';
  let sharingDashboardId: number | null = null;

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

  onMount(fetchDashboards);
</script>

<svelte:head>
  <title>Editor Dashboards</title>
</svelte:head>

<div class="container mx-auto p-6">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">My Dashboards</h1>
    <a href="/editor/dashboards/create" class="rounded-md bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition">+ Create Dashboard</a>
  </div>

  {#if $loading}
    <div class="text-center text-gray-500 py-10">Loading dashboards...</div>
  {:else if $error}
    <div class="text-center text-red-500 p-4 bg-red-100 rounded">{$error}</div>
  {:else if $dashboards.length === 0}
    <div class="text-center text-gray-500 p-10 bg-gray-50 rounded-lg">
      <h2 class="text-xl font-semibold">No dashboards yet</h2>
      <p class="mt-2">Click the button above to create your first dashboard.</p>
    </div>
  {:else}
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <ul class="divide-y divide-gray-200">
        {#each $dashboards as dash (dash.id)}
          <li class="p-4 flex items-center justify-between hover:bg-gray-50 transition">
            <a class="font-medium text-blue-700 hover:underline" href={`/editor/dashboards/${dash.id}`}>{dash.name}</a>
            <div class="flex items-center space-x-2">
              <button 
                on:click={() => handleShare(dash.id)} 
                disabled={sharingDashboardId === dash.id}
                class="text-sm text-gray-600 hover:text-blue-700 font-semibold py-1 px-3 rounded border border-gray-300 hover:border-blue-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                {sharingDashboardId === dash.id ? 'Sharing...' : 'Share'}
              </button>
              <a class="text-sm text-white bg-blue-600 hover:bg-blue-700 font-semibold py-1 px-3 rounded transition" href={`/editor/dashboards/${dash.id}`}>View</a>
            </div>
          </li>
        {/each}
      </ul>
    </div>
  {/if}
</div>

<ShareModal bind:show={showShareModal} shareLink={currentShareLink} on:close={() => showShareModal = false} />