<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import * as api from '$lib/services/api';

  export let show = false;

  let name = '';
  let target = '';
  let rag_red = '';
  let rag_amber = '';
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
    error = '';
    dispatch('close');
  }

  async function handleSubmit() {
    if (!name) return;
    isSubmitting = true;
    error = '';

    try {
      const result = await api.createKpi({ name, target, rag_red, rag_amber });
      if (result.status === 'success') {
        dispatch('success', result.data.kpi);
        closeModal();
      } else {
        error = result.message || 'Failed to create KPI.';
      }
    } catch (e) {
      error = 'An unexpected error occurred.';
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
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" on:click|stopPropagation role="dialog" aria-modal="true">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Create New KPI</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <form on:submit|preventDefault={handleSubmit}>
        <div class="space-y-4">
          <div>
            <label for="kpi-name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" id="kpi-name" bind:value={name} required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>
          <div>
            <label for="kpi-target" class="block text-sm font-medium text-gray-700">Target</label>
            <input type="number" id="kpi-target" bind:value={target} required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>
          <div>
            <label for="kpi-rag-red" class="block text-sm font-medium text-gray-700">RAG Red Threshold</label>
            <input type="number" id="kpi-rag-red" bind:value={rag_red} required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>
          <div>
            <label for="kpi-rag-amber" class="block text-sm font-medium text-gray-700">RAG Amber Threshold</label>
            <input type="number" id="kpi-rag-amber" bind:value={rag_amber} required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
            {isSubmitting ? 'Creating...' : 'Create KPI'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
