<script lang="ts">
  import { onMount } from 'svelte';
  import { user } from '$lib/stores/auth';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import type { Kpi, ApiResponse } from '$lib/types';
  import CreateKpiModal from '$lib/components/CreateKpiModal.svelte';
  import ConfirmModal from '$lib/components/ConfirmModal.svelte';
  import EditKpiModal from '$lib/components/EditKpiModal.svelte';
  import KpiEntriesModal from '$lib/components/KpiEntriesModal.svelte';

  const kpis = writable<Kpi[]>([]);
  const loading = writable(true);
  const error = writable<string | null>(null);
  let showCreateModal = false;
  let showDeleteConfirm = false;
  let kpiToDelete: Kpi | null = null;
  let showEditModal = false;
  let kpiToEdit: Kpi | null = null;
  let showKpiEntriesModal = false;
  let selectedKpiForEntries: Kpi | null = null;
  let selectedKpiIdForEntries: number | null = null;

  $: isEditor = $user?.role === 'editor';

  // Helper function to format KPI values
  function formatKpiValue(value: string | number, prefix?: string, suffix?: string): string {
    const formattedValue = typeof value === 'string' ? value : value.toString();
    return `${prefix || ''}${formattedValue}${suffix || ''}`;
  }

  // Helper function to format the format display
  function formatDisplay(prefix?: string, suffix?: string): string {
    if (prefix && suffix) {
      return `${prefix}${suffix}`;
    } else if (prefix) {
      return prefix;
    } else if (suffix) {
      return suffix;
    } else {
      return 'None';
    }
  }

  async function fetchKpis() {
    loading.set(true);
    error.set(null);
    try {
      const response: ApiResponse<Kpi[]> = await api.getKpis();
      
      if (response.success) {
        // The KPIs are directly in the data array, not in data.kpis
        kpis.set(response.data || []);
      } else {
        error.set(response.message || 'Failed to load KPIs');
      }
    } catch (e) {
      error.set(e instanceof Error ? e.message : 'An unexpected error occurred while fetching KPIs.');
    }
    loading.set(false);
  }

  function handleCreateSuccess() {
    fetchKpis(); // Refetch KPIs to update the list
  }

  async function confirmDelete() {
    if (!kpiToDelete) return;

    try {
      const response: ApiResponse = await api.deleteKpi(kpiToDelete.id);
      
      if (response.success) {
        // On success, remove the KPI from the local list
        kpis.update(currentKpis => currentKpis.filter(k => k.id !== kpiToDelete!.id));
      } else {
        // If the API returns an error, show it
        error.set(response.message || 'Failed to delete KPI.');
      }
    } catch (e) {
      // Handle unexpected network errors
      error.set(e instanceof Error ? e.message : 'An unexpected error occurred.');
    } finally {
      // Close modal and reset state
      showDeleteConfirm = false;
      kpiToDelete = null;
    }
  }

  function handleDelete(kpi: Kpi) {
    kpiToDelete = kpi;
    showDeleteConfirm = true;
  }

  function handleEdit(kpi: Kpi) {
    kpiToEdit = kpi;
    showEditModal = true;
  }

  function handleEditSuccess() {
    fetchKpis();
  }

  function handleKpiClick(kpi: Kpi) {

    console.log('KPI clicked:', kpi);
    selectedKpiForEntries = kpi;
    selectedKpiIdForEntries = kpi.id;
    showKpiEntriesModal = true;
  }

  function handleEntriesUpdated(event: CustomEvent) {
    // When KPI entries are updated, we could refresh the KPI list if needed
    // For now, we'll just close the modal
    console.log('KPI entries updated:', event.detail);
  }

  onMount(() => {
    fetchKpis();
    
    // Listen for KPI creation events from the navbar
    const handleKpiCreated = () => {
      fetchKpis();
    };
    
    window.addEventListener('kpiCreated', handleKpiCreated);
    
    return () => {
      window.removeEventListener('kpiCreated', handleKpiCreated);
    };
  });
</script>

<CreateKpiModal bind:show={showCreateModal} on:success={handleCreateSuccess} />

<EditKpiModal bind:show={showEditModal} kpi={kpiToEdit} on:success={handleEditSuccess} />

<KpiEntriesModal 
  bind:isOpen={showKpiEntriesModal} 
  kpi={selectedKpiForEntries} 
  kpiId={selectedKpiIdForEntries}
  on:entriesUpdated={handleEntriesUpdated}
/>

<ConfirmModal
  bind:show={showDeleteConfirm}
  title="Delete KPI"
  message={`Are you sure you want to delete the KPI "${kpiToDelete?.name}"? This action cannot be undone.`}
  on:confirm={confirmDelete}
  on:close={() => (showDeleteConfirm = false)}
/>

<div class="space-y-8">
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">KPI Management</h1>
      <p class="mt-1 text-sm text-gray-600">Create, manage, and track your Key Performance Indicators.</p>
    </div>
    {#if isEditor}
      <button 
        on:click={() => (showCreateModal = true)} 
        class="inline-flex items-center gap-2 justify-center rounded-md border border-transparent bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        <span>Create KPI</span>
      </button>
    {/if}
  </div>

  {#if $loading}
    <div class="text-center py-12 text-gray-500">Loading KPIs...</div>
  {:else if $error}
    <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4" role="alert">
      <p class="font-bold">Error</p>
      <p>{$error}</p>
    </div>
  {:else if !$user}
    <div class="text-center py-12 text-gray-600">Please log in to view KPIs.</div>
  {:else if $kpis.length === 0}
    <div class="text-center py-20 px-6 bg-white rounded-lg border-2 border-dashed border-gray-300">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      <h3 class="mt-2 text-lg font-medium text-gray-900">No KPIs yet</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating your first KPI to track performance.</p>
      {#if isEditor}
        <div class="mt-6">
          <button 
            on:click={() => (showCreateModal = true)} 
            class="inline-flex items-center gap-2 justify-center rounded-md border border-transparent bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            <span>Create Your First KPI</span>
          </button>
        </div>
      {/if}
    </div>
  {:else}
    <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Name
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Direction
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Target
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                RAG Red
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                RAG Amber
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Format
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            {#each $kpis as kpi (kpi.id)}
              <tr class="hover:bg-gray-50 cursor-pointer" on:click={() => handleKpiClick(kpi)} on:keydown={(e) => e.key === 'Enter' && handleKpiClick(kpi)} role="button" tabindex="0">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 flex items-center">
                    {kpi.name}
                    <svg class="ml-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {kpi.direction === 'higher_is_better' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    {kpi.direction === 'higher_is_better' ? 'Higher is Better' : 'Lower is Better'}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {formatKpiValue(kpi.target, kpi.format_prefix, kpi.format_suffix)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {formatKpiValue(kpi.rag_red, kpi.format_prefix, kpi.format_suffix)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {formatKpiValue(kpi.rag_amber, kpi.format_prefix, kpi.format_suffix)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {formatDisplay(kpi.format_prefix, kpi.format_suffix)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <button 
                      on:click|stopPropagation={() => handleEdit(kpi)} 
                      class="text-blue-800 hover:text-blue-900 cursor-pointer p-1 rounded-md hover:bg-blue-50"
                      title="Edit KPI"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button 
                      on:click|stopPropagation={() => handleDelete(kpi)} 
                      class="text-red-500 hover:text-red-900 cursor-pointer p-1 rounded-md hover:bg-red-50"
                      title="Delete KPI"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </div>
  {/if}
</div> 