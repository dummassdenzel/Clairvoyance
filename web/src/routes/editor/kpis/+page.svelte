<script lang="ts">
  import { onMount } from 'svelte';
  import { user } from '$lib/stores/auth';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import CreateKpiModal from '$lib/components/CreateKpiModal.svelte';
  import ConfirmModal from '$lib/components/ConfirmModal.svelte';
  import EditKpiModal from '$lib/components/EditKpiModal.svelte';

  const kpis = writable<any[]>([]);
  const loading = writable(true);
  const error = writable<string | null>(null);
  let showCreateModal = false;
  let showDeleteConfirm = false;
  let kpiToDelete: any = null;
  let showEditModal = false;
  let kpiToEdit: any = null;

  $: isEditor = $user?.role === 'editor';

  async function fetchKpis() {
    loading.set(true);
    error.set(null);
    try {
      const result = await api.getKpis();
      if (result.status === 'success') {
        kpis.set(result.data || []);
      } else {
        error.set(result.message || 'Failed to load KPIs');
      }
    } catch (e) {
      error.set('An unexpected error occurred while fetching KPIs.');
    }
    loading.set(false);
  }

  function handleCreateSuccess() {
    fetchKpis(); // Refetch KPIs to update the list
  }

  async function confirmDelete() {
    if (!kpiToDelete) return;

    try {
      const result = await api.deleteKpi(kpiToDelete.id);
      if (result.status === 'success') {
        // On success, remove the KPI from the local list
        kpis.update(currentKpis => currentKpis.filter(k => k.id !== kpiToDelete.id));
      } else {
        // If the API returns an error, show it
        error.set(result.message || 'Failed to delete KPI.');
      }
    } catch (e: any) {
      // Handle unexpected network errors
      error.set(e.message || 'An unexpected error occurred.');
    } finally {
      // Close modal and reset state
      showDeleteConfirm = false;
      kpiToDelete = null;
    }
  }

  function handleDelete(kpi: any) {
    kpiToDelete = kpi;
    showDeleteConfirm = true;
  }

  function handleEdit(kpi: any) {
    kpiToEdit = kpi;
    showEditModal = true;
  }

  function handleEditSuccess() {
    fetchKpis();
  }

  onMount(fetchKpis);
</script>

<CreateKpiModal bind:show={showCreateModal} on:success={handleCreateSuccess} />

<EditKpiModal bind:show={showEditModal} kpi={kpiToEdit} on:success={handleEditSuccess} />

<ConfirmModal
  bind:show={showDeleteConfirm}
  title="Delete KPI"
  message={`Are you sure you want to delete the KPI "${kpiToDelete?.name}"? This action cannot be undone.`}
  on:confirm={confirmDelete}
  on:close={() => (showDeleteConfirm = false)}
/>

<div class="container mx-auto">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">KPI Management</h1>
    {#if isEditor}
      <button on:click={() => (showCreateModal = true)} class="inline-flex items-center gap-2 bg-blue-900 text-sm hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
        Create KPI
      </button>
    {/if}
  </div>

  {#if $loading}
    <div>Loading...</div>
  {:else if $error}
    <div class="text-red-500">{$error}</div>
  {:else if !$user}
    <div class="text-gray-600">Please log in to view KPIs.</div>
  {:else}
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RAG Red</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RAG Amber</th>
            <th scope="col" class="relative px-6 py-3">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          {#each $kpis as kpi (kpi.id)}
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{kpi.name}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{kpi.target}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{kpi.rag_red}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{kpi.rag_amber}</td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button on:click={() => handleEdit(kpi)} class="text-indigo-600 hover:text-indigo-900">Edit</button>
                <button on:click={() => handleDelete(kpi)} class="text-red-600 hover:text-red-900 ml-4">Delete</button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>

  {/if}
</div> 