<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';
  import * as api from '$lib/services/api';
  import type { Kpi, ApiResponse, CreateKpiForm } from '$lib/types';

  export let show = false;
  export let kpi: Kpi | null = null;

  let name = '';
  let target = '';
  let rag_red = '';
  let rag_amber = '';
  let direction = 'higher_is_better';
  let format_prefix = '';
  let format_suffix = '';
  let submitting = false;
  let error: string | null = null;

  const dispatch = createEventDispatcher();

  // When the modal is shown with a KPI, populate the form fields.
  $: if (kpi && show) {
    name = kpi.name;
    target = kpi.target.toString();
    rag_red = kpi.rag_red.toString();
    rag_amber = kpi.rag_amber.toString();
    direction = kpi.direction || 'higher_is_better';
    format_prefix = kpi.format_prefix || '';
    format_suffix = kpi.format_suffix || '';
  } else {
    // Reset form when hidden
    name = target = rag_red = rag_amber = format_prefix = format_suffix = '';
    direction = 'higher_is_better';
    error = null;
  }

  function handleClose() {
    show = false;
    dispatch('close');
  }

  async function handleSubmit() {
    if (!kpi) return;
    submitting = true;
    error = null;

    const data: Partial<CreateKpiForm> = { 
      name, 
      target: parseFloat(target) || 0, 
      rag_red: parseFloat(rag_red) || 0, 
      rag_amber: parseFloat(rag_amber) || 0, 
      direction: direction as 'higher_is_better' | 'lower_is_better', 
      format_prefix, 
      format_suffix 
    };

    try {
      const response: ApiResponse<{ kpi: Kpi }> = await api.updateKpi(kpi.id, data);

      if (response.success) {
        dispatch('success', response.data?.kpi);
        handleClose();
      } else {
        error = response.message || 'Failed to update KPI.';
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'An unexpected error occurred.';
    } finally {
      submitting = false;
    }
  }

  function handleKeydown(event: KeyboardEvent) {
    if (show && event.key === 'Escape') {
      handleClose();
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-75 transition-opacity z-50 flex items-center justify-center" on:click={handleClose} role="presentation">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" on:click|stopPropagation role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
      <h3 id="modal-title" class="text-xl font-semibold leading-6 text-gray-900">Edit KPI</h3>
      <form on:submit|preventDefault={handleSubmit} class="mt-4 space-y-4">
        <div>
          <label for="kpi-name" class="block text-sm font-medium text-gray-700">Name</label>
          <input 
            id="kpi-name" 
            type="text" 
            bind:value={name} 
            required 
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
        </div>
        <div>
          <label for="kpi-target" class="block text-sm font-medium text-gray-700">Target</label>
          <input 
            id="kpi-target" 
            type="number" 
            bind:value={target} 
            required 
            step="0.01"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
        </div>
        <div>
          <label for="kpi-rag-red" class="block text-sm font-medium text-gray-700">RAG Red Threshold</label>
          <input 
            id="kpi-rag-red" 
            type="number" 
            bind:value={rag_red} 
            required 
            step="0.01"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
        </div>
        <div>
          <label for="kpi-rag-amber" class="block text-sm font-medium text-gray-700">RAG Amber Threshold</label>
          <input 
            id="kpi-rag-amber" 
            type="number" 
            bind:value={rag_amber} 
            required 
            step="0.01"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
        </div>

        <div>
          <label for="edit-kpi-direction" class="block text-sm font-medium text-gray-700">Performance Direction</label>
          <select 
            id="edit-kpi-direction" 
            bind:value={direction} 
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
            <option value="higher_is_better">Higher is better</option>
            <option value="lower_is_better">Lower is better</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="edit-kpi-format-prefix" class="block text-sm font-medium text-gray-700">Format Prefix</label>
            <input 
              type="text" 
              id="edit-kpi-format-prefix" 
              bind:value={format_prefix} 
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
              placeholder="e.g. $"
            >
          </div>
          <div>
            <label for="edit-kpi-format-suffix" class="block text-sm font-medium text-gray-700">Format Suffix</label>
            <input 
              type="text" 
              id="edit-kpi-format-suffix" 
              bind:value={format_suffix} 
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
              placeholder="e.g. %"
            >
          </div>
        </div>

        {#if error}
          <p class="text-sm text-red-600">{error}</p>
        {/if}

        <div class="mt-6 flex justify-end space-x-3">
          <button 
            type="button" 
            on:click={handleClose} 
            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            disabled={submitting} 
            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {submitting ? 'Saving...' : 'Save Changes'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
