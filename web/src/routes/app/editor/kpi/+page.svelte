<!-- KPI Management Page -->
<script lang="ts">
  import { onMount } from 'svelte';
  import { kpis, type KPI, createKPI, deleteKPI, fetchKPIs } from '$lib/stores/kpi';
  import { authStore } from '$lib/stores/auth';

  let kpiList: KPI[] = [];
  let loading = true;
  let error: string | null = null;
  let showCreateModal = false;
  let newKpi: Partial<KPI> = {
    name: '',
    description: '',
    category_id: 0,
    unit: '',
    target: 0
  };

  // Subscribe to KPI store
  kpis.subscribe(state => {
    kpiList = state;
    loading = false;
  });

  onMount(async () => {
    // Load KPIs on mount
    const result = await fetchKPIs();
    if (!result.success) {
      error = result.message || 'Failed to load KPIs';
    }
  });

  async function handleCreateKPI() {
    try {
      const result = await createKPI(newKpi as Pick<KPI, 'name' | 'description' | 'category_id' | 'unit' | 'target'>);
      if (result.success) {
        showCreateModal = false;
        // Reset form
        newKpi = {
          name: '',
          description: '',
          category_id: 0,
          unit: '',
          target: 0
        };
      } else {
        error = result.message || 'Failed to create KPI';
      }
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to create KPI';
    }
  }

  async function handleDeleteKPI(id: number) {
    if (confirm('Are you sure you want to delete this KPI?')) {
      try {
        const result = await deleteKPI(id);
        if (!result.success) {
          error = result.message || 'Failed to delete KPI';
        }
      } catch (err) {
        error = err instanceof Error ? err.message : 'Failed to delete KPI';
      }
    }
  }
</script>

<div class="space-y-6">
  <!-- Header -->
  <div class="sm:flex sm:items-center sm:justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">KPI Management</h1>
      <p class="mt-2 text-sm text-gray-700">
        Create and manage Key Performance Indicators for your organization.
      </p>
    </div>
    <div class="mt-4 sm:mt-0">
      <button
        type="button"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        on:click={() => (showCreateModal = true)}
      >
        Create KPI
      </button>
    </div>
  </div>

  <!-- Error Alert -->
  {#if error}
    <div class="rounded-md bg-red-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{error}</p>
          </div>
        </div>
      </div>
    </div>
  {/if}

  <!-- Loading State -->
  {#if loading}
    <div class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>
  {:else}
    <!-- KPI List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <ul role="list" class="divide-y divide-gray-200">
        {#each kpiList as kpi (kpi.id)}
          <li>
            <div class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-indigo-600 truncate">{kpi.name}</p>
                  <p class="mt-1 text-sm text-gray-500">{kpi.description}</p>
                </div>
                <div class="ml-4 flex-shrink-0 flex space-x-4">
                  <button
                    type="button"
                    class="text-sm text-gray-500 hover:text-gray-700"
                    on:click={() => handleDeleteKPI(kpi.id)}
                  >
                    Delete
                  </button>
                </div>
              </div>
              <div class="mt-2 sm:flex sm:justify-between">
                <div class="sm:flex">
                  <p class="flex items-center text-sm text-gray-500">
                    Category: {kpi.category_name || 'Uncategorized'}
                  </p>
                  <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                    Unit: {kpi.unit}
                  </p>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                  <p>Target: {kpi.target} {kpi.unit}</p>
                </div>
              </div>
            </div>
          </li>
        {/each}
      </ul>
    </div>
  {/if}

  <!-- Create KPI Modal -->
  {#if showCreateModal}
    <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Create New KPI</h3>
            <div class="mt-4 space-y-4">
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input
                  type="text"
                  id="name"
                  bind:value={newKpi.name}
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea
                  id="description"
                  bind:value={newKpi.description}
                  rows="3"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                ></textarea>
              </div>
              <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Category ID</label>
                <input
                  type="number"
                  id="category"
                  bind:value={newKpi.category_id}
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="unit" class="block text-sm font-medium text-gray-700">Unit</label>
                <input
                  type="text"
                  id="unit"
                  bind:value={newKpi.unit}
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="target" class="block text-sm font-medium text-gray-700">Target</label>
                <input
                  type="number"
                  id="target"
                  bind:value={newKpi.target}
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
          </div>
          <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
            <button
              type="button"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm"
              on:click={handleCreateKPI}
            >
              Create
            </button>
            <button
              type="button"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
              on:click={() => (showCreateModal = false)}
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  {/if}
</div> 