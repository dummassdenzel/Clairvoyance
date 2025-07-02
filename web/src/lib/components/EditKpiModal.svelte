<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';
  import * as api from '$lib/services/api';

  export let show = false;
  export let kpi: any | null = null;

  let name = '';
  let target = '';
  let rag_red = '';
  let rag_amber = '';
  let submitting = false;
  let error: string | null = null;

  const dispatch = createEventDispatcher();

  // When the modal is shown with a KPI, populate the form fields.
  $: if (kpi && show) {
    name = kpi.name;
    target = kpi.target;
    rag_red = kpi.rag_red;
    rag_amber = kpi.rag_amber;
  } else {
    // Reset form when hidden
    name = target = rag_red = rag_amber = '';
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

    const data = { id: kpi.id, name, target, rag_red, rag_amber };

    try {
      const result = await api.updateKpi(data);

      if (result.status === 'success') {
        dispatch('success');
        handleClose();
      } else {
        error = result.message || 'Failed to update KPI.';
      }
    } catch (e: any) {
      error = e.message || 'An unexpected error occurred.';
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
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50 flex items-center justify-center" on:click={handleClose} role="presentation">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" on:click|stopPropagation role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
      <h3 id="modal-title" class="text-xl font-semibold leading-6 text-gray-900">Edit KPI</h3>
      <form on:submit|preventDefault={handleSubmit} class="mt-4 space-y-4">
        <div>
          <label for="kpi-name" class="block text-sm font-medium text-gray-700">Name</label>
          <input id="kpi-name" type="text" bind:value={name} required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
          <label for="kpi-target" class="block text-sm font-medium text-gray-700">Target</label>
          <input id="kpi-target" type="number" bind:value={target} required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
          <label for="kpi-rag-red" class="block text-sm font-medium text-gray-700">RAG Red Threshold</label>
          <input id="kpi-rag-red" type="number" bind:value={rag_red} required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
          <label for="kpi-rag-amber" class="block text-sm font-medium text-gray-700">RAG Amber Threshold</label>
          <input id="kpi-rag-amber" type="number" bind:value={rag_amber} required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        {#if error}
          <p class="text-sm text-red-600">{error}</p>
        {/if}

        <div class="mt-6 flex justify-end space-x-3">
          <button type="button" on:click={handleClose} class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Cancel
          </button>
          <button type="submit" disabled={submitting} class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
            {submitting ? 'Saving...' : 'Save Changes'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
