<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import * as api from '$lib/services/api';
  import type { Kpi, ApiResponse, CreateKpiForm } from '$lib/types';

  export let show = false;

  let name = '';
  let target = '';
  let rag_red = '';
  let rag_amber = '';
  let direction = 'higher_is_better';
  let format_prefix = '';
  let format_suffix = '';
  let isSubmitting = false;
  let error = '';

  const dispatch = createEventDispatcher();

  function closeModal() {
    if (isSubmitting) return;
    show = false;
    name = '';
    target = '';
    rag_red = '';
    rag_amber = '';
    direction = 'higher_is_better';
    format_prefix = '';
    format_suffix = '';
    error = '';
    dispatch('close');
  }

  async function handleSubmit() {
    if (!name) return;
    isSubmitting = true;
    error = '';

    try {
      const response: ApiResponse<{ kpi: Kpi }> = await api.createKpi({ 
        name, 
        target: parseFloat(target) || 0, 
        rag_red: parseFloat(rag_red) || 0, 
        rag_amber: parseFloat(rag_amber) || 0, 
        direction: direction as 'higher_is_better' | 'lower_is_better', 
        format_prefix, 
        format_suffix 
      });
      
      if (response.success) {
        dispatch('success', response.data?.kpi);
        closeModal();
      } else {
        error = response.message || 'Failed to create KPI.';
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'An unexpected error occurred.';
    } finally {
      isSubmitting = false;
    }
  }

  function handleKeydown(event: KeyboardEvent) {
    if (show && event.key === 'Escape') {
      closeModal();
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)] z-50 flex items-center justify-center" on:click={closeModal} role="presentation">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" on:click|stopPropagation role="dialog" aria-modal="true" tabindex="-1">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Create New KPI</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <form on:submit|preventDefault={handleSubmit}>
        <div class="space-y-4">
          <div>
            <label for="kpi-name" class="block text-sm font-medium text-gray-700">Name</label>
            <input 
              type="text" 
              id="kpi-name" 
              bind:value={name} 
              required 
              class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
          </div>
          <div>
            <label for="kpi-target" class="block text-sm font-medium text-gray-700">Target</label>
            <input 
              type="number" 
              id="kpi-target" 
              bind:value={target} 
              required 
              step="0.01"
              class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
          </div>
          <div>
            <label for="kpi-rag-red" class="block text-sm font-medium text-gray-700">RAG Red Threshold</label>
            <input 
              type="number" 
              id="kpi-rag-red" 
              bind:value={rag_red} 
              required 
              step="0.01"
              class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
          </div>
          <div>
            <label for="kpi-rag-amber" class="block text-sm font-medium text-gray-700">RAG Amber Threshold</label>
            <input 
              type="number" 
              id="kpi-rag-amber" 
              bind:value={rag_amber} 
              required 
              step="0.01"
              class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
          </div>

          <div>
            <label for="kpi-direction" class="block text-sm font-medium text-gray-700">Performance Direction</label>
            <select 
              id="kpi-direction" 
              bind:value={direction} 
              class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="higher_is_better">Higher is better</option>
              <option value="lower_is_better">Lower is better</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="kpi-format-prefix" class="block text-sm font-medium text-gray-700">Format Prefix</label>
              <input 
                type="text" 
                id="kpi-format-prefix" 
                bind:value={format_prefix} 
                class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                placeholder="e.g. $"
              >
            </div>
            <div>
              <label for="kpi-format-suffix" class="block text-sm font-medium text-gray-700">Format Suffix</label>
              <input 
                type="text" 
                id="kpi-format-suffix" 
                bind:value={format_suffix} 
                class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                placeholder="e.g. %"
              >
            </div>
          </div>
        </div>

        {#if error}
          <div class="mt-4 text-sm text-red-600 bg-red-50 p-3 rounded-md">{error}</div>
        {/if}

        <div class="mt-6 flex justify-end space-x-3">
          <button 
            type="button" 
            on:click={closeModal} 
            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            disabled={isSubmitting} 
            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            {isSubmitting ? 'Creating...' : 'Create KPI'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
