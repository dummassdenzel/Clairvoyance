<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import * as api from '$lib/services/api';
  import type { ApiResponse, KpiEntry } from '$lib/types';

  export let isOpen = false;
  export let kpiId: number | null = null;
  export let kpiName: string = '';

  const dispatch = createEventDispatcher();

  let date = '';
  let value = '';
  let submitting = false;
  let error: string | null = null;

  function closeModal() {
    isOpen = false;
    resetForm();
    dispatch('close');
  }

  function resetForm() {
    date = '';
    value = '';
    error = null;
    submitting = false;
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && isOpen) {
      closeModal();
    }
  }

  async function handleSubmit(event: Event) {
    event.preventDefault();
    
    if (!kpiId || !date || !value) {
      error = 'Please fill in all fields';
      return;
    }

    submitting = true;
    error = null;

    try {
      const response: ApiResponse<{ entry: KpiEntry }> = await api.createKpiEntry({
        kpi_id: kpiId,
        date: date,
        value: parseFloat(value)
      });

      if (response.success) {
        dispatch('success', { entry: response.data?.entry });
        closeModal();
      } else {
        error = response.message || 'Failed to create KPI entry';
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'An unexpected error occurred';
    } finally {
      submitting = false;
    }
  }

  function setToday() {
    const today = new Date();
    date = today.toISOString().split('T')[0];
  }

  function setYesterday() {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    date = yesterday.toISOString().split('T')[0];
  }

  // Set default date to today when modal opens
  $: if (isOpen && !date) {
    setToday();
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen}
  <div
    class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-50 z-60 flex items-center justify-center"
  >
    <div
      class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Add KPI Entry</h2>
          {#if kpiName}
            <p class="mt-1 text-sm text-gray-600">for {kpiName}</p>
          {/if}
        </div>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
      </div>

      <form on:submit={handleSubmit} class="p-6">
        <div class="space-y-4">
          <div>
            <label for="entry-date" class="block text-sm font-medium text-gray-700">Date</label>
            <div class="mt-1 flex space-x-2">
              <input 
                type="date" 
                id="entry-date" 
                bind:value={date} 
                required
                class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
              <button 
                type="button" 
                on:click={setToday}
                class="px-3 py-2 text-xs border border-gray-300 rounded-md hover:bg-gray-100"
              >
                Today
              </button>
              <button 
                type="button" 
                on:click={setYesterday}
                class="px-3 py-2 text-xs border border-gray-300 rounded-md hover:bg-gray-100"
              >
                Yesterday
              </button>
            </div>
          </div>

          <div>
            <label for="entry-value" class="block text-sm font-medium text-gray-700">Value</label>
            <input 
              type="number" 
              id="entry-value" 
              bind:value={value} 
              step="0.01"
              min="0"
              required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Enter the KPI value"
            />
          </div>

          {#if error}
            <div class="p-3 bg-red-50 border border-red-200 rounded-md">
              <div class="flex">
                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p class="text-sm text-red-600">{error}</p>
              </div>
            </div>
          {/if}
        </div>

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
            disabled={submitting}
            class="bg-blue-900 text-white py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {submitting ? 'Adding...' : 'Add Entry'}
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
