<script lang="ts">
  import { createEventDispatcher, tick } from 'svelte';
  import * as api from '$lib/services/api';

  export let show = false;

  let name = '';
  let description = '';
  let isSubmitting = false;
  let error = '';
  let dialogElement: HTMLDivElement;

  let kpis: any[] = [];
  let selectedKpiIds: number[] = [];
  let kpisLoading = true;
  let kpisError = '';

  const dispatch = createEventDispatcher();

  async function fetchKPIs() {
    kpisLoading = true;
    kpisError = '';
    try {
      const result = await api.getKpis();
      if (result.status === 'success') {
        kpis = result.data;
      } else {
        kpisError = result.message || 'Failed to load KPIs.';
      }
    } catch (e) {
      kpisError = 'An unexpected error occurred while fetching KPIs.';
    } finally {
      kpisLoading = false;
    }
  }

  async function handleSubmit() {
    if (!name) return;
    isSubmitting = true;
    error = '';

    const layout = selectedKpiIds.map((kpi_id, index) => {
      const kpi = kpis.find(k => k.id === kpi_id);
      return {
        type: 'line', // Use 'line' as a valid widget type
        kpi_id: kpi_id,
        position: index,
        title: kpi ? `${kpi.name} Trend` : 'KPI Trend' // Add a title
      };
    });

    try {
      const result = await api.createDashboard({ name, description, layout });
      if (result.status === 'success') {
        dispatch('success', result.data.dashboard);
        closeModal();
      } else {
        error = result.message || 'Failed to create dashboard.';
      }
    } catch (e) {
      error = 'An unexpected error occurred.';
    } finally {
      isSubmitting = false;
    }
  }

  function closeModal() {
    if (isSubmitting) return;
    show = false;
    name = '';
    description = '';
    error = '';
    selectedKpiIds = [];
    dispatch('close');
  }

  function handleKeydown(event: KeyboardEvent) {
    if (show && event.key === 'Escape') {
      closeModal();
    }
  }

  $: if (show && dialogElement) {
    tick().then(() => {
      dialogElement.focus();
    });
  }

  $: if (show) {
    fetchKPIs();
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)] z-50 flex items-center justify-center" on:click={closeModal} role="presentation">
    <div bind:this={dialogElement} class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" on:click|stopPropagation role="dialog" aria-modal="true" tabindex="-1">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Create New Dashboard</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <form on:submit|preventDefault={handleSubmit}>
        <div class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" id="name" bind:value={name} required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
            <textarea id="description" bind:value={description} rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
          </div>

          <div role="group" aria-labelledby="kpi-group-label">
            <div id="kpi-group-label" class="block text-sm font-medium text-gray-700">Initial KPIs (Optional)</div>
            <p class="text-xs text-gray-500 mb-2">Select KPIs to add to the new dashboard as widgets.</p>
            {#if kpisLoading}
              <div class="text-center p-4">
                <p class="text-sm text-gray-500">Loading KPIs...</p>
              </div>
            {:else if kpisError}
              <div class="text-center p-4">
                <p class="text-sm text-red-500">{kpisError}</p>
              </div>
            {:else if kpis.length === 0}
              <div class="text-center p-4">
                <p class="text-sm text-gray-500">No KPIs available to add.</p>
              </div>
            {:else}
              <div class="mt-2 border border-gray-200 rounded-md max-h-40 overflow-y-auto">
                {#each kpis as kpi (kpi.id)}
                  <label class="flex items-center p-3 border-b border-gray-200 last:border-b-0 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" bind:group={selectedKpiIds} value={kpi.id} class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3 text-sm text-gray-700">{kpi.name}</span>
                  </label>
                {/each}
              </div>
            {/if}
          </div>
        </div>

        {#if error}
          <div class="mt-4 text-sm text-red-600 bg-red-50 p-3 rounded-md">{error}</div>
        {/if}

        <div class="mt-6 flex justify-end space-x-3">
          <button type="button" on:click={closeModal} class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Cancel
          </button>
          <button type="submit" disabled={isSubmitting} class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
            {isSubmitting ? 'Creating...' : 'Create Dashboard'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
