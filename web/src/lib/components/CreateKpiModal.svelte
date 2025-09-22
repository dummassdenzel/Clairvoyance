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
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-75 transition-opacity z-50 flex items-center justify-center" on:click={closeModal} role="presentation">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto" on:click|stopPropagation role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
      <div class="flex items-center justify-between mb-6">
        <h2 id="modal-title" class="text-xl font-semibold leading-6 text-gray-900">Create New KPI</h2>
        <button 
          on:click={closeModal} 
          class="text-gray-400 hover:text-gray-600 transition-colors"
          aria-label="Close modal"
        >
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form on:submit|preventDefault={handleSubmit} class="space-y-6">
        <!-- Basic Information Section -->
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="kpi-name" class="block text-sm font-medium text-gray-700">KPI Name</label>
              <input 
                type="text" 
                id="kpi-name" 
                bind:value={name} 
                required 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="Enter KPI name"
              >
            </div>
            <div>
              <label for="kpi-target" class="block text-sm font-medium text-gray-700">Target Value</label>
              <input 
                type="number" 
                id="kpi-target" 
                bind:value={target} 
                required 
                step="0.01"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="Enter target value"
              >
            </div>
          </div>
        </div>

        <!-- Performance Thresholds Section -->
        <div class="space-y-4">
          <div>
            <label for="kpi-direction" class="block text-sm font-medium text-gray-700">Performance Direction</label>
            <select 
              id="kpi-direction" 
              bind:value={direction} 
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="higher_is_better">Higher is better</option>
              <option value="lower_is_better">Lower is better</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Determines how values are evaluated against thresholds</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="kpi-rag-red" class="block text-sm font-medium text-gray-700">Poor Threshold</label>
              <input 
                type="number" 
                id="kpi-rag-red" 
                bind:value={rag_red} 
                required 
                step="0.01"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="0.00"
              >
              <p class="mt-1 text-xs text-gray-500">Values below this are "Poor"</p>
            </div>
            <div>
              <label for="kpi-rag-amber" class="block text-sm font-medium text-gray-700">Fair Threshold</label>
              <input 
                type="number" 
                id="kpi-rag-amber" 
                bind:value={rag_amber} 
                required 
                step="0.01"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="0.00"
              >
              <p class="mt-1 text-xs text-gray-500">Values below this are "Fair"</p>
            </div>
          </div>
          <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-700">
                  <strong>Note:</strong> Values above the Fair threshold will be marked as "Excellent"
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Display Format Section -->
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="kpi-format-prefix" class="block text-sm font-medium text-gray-700">Prefix</label>
              <input 
                type="text" 
                id="kpi-format-prefix" 
                bind:value={format_prefix} 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                placeholder="e.g. $"
              >
              <p class="mt-1 text-xs text-gray-500">Symbol before the value</p>
            </div>
            <div>
              <label for="kpi-format-suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
              <input 
                type="text" 
                id="kpi-format-suffix" 
                bind:value={format_suffix} 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                placeholder="e.g. %"
              >
              <p class="mt-1 text-xs text-gray-500">Symbol after the value</p>
            </div>
          </div>
        </div>

        {#if error}
          <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-700">{error}</p>
              </div>
            </div>
          </div>
        {/if}

        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
          <button 
            type="button" 
            on:click={closeModal} 
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            disabled={isSubmitting} 
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {#if isSubmitting}
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            {/if}
            {isSubmitting ? 'Creating...' : 'Create KPI'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
