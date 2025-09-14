<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import * as api from '$lib/services/api';
  import type { ApiResponse, KpiEntry } from '$lib/types';

  export let isOpen = false;
  export let kpiId: number | null = null;
  export let kpiName: string = '';

  const dispatch = createEventDispatcher();

  // Toggle between manual entry and CSV upload
  let entryMode: 'manual' | 'csv' = 'manual';

  // Manual entry fields
  let date = '';
  let value = '';
  let submitting = false;
  let error: string | null = null;

  // CSV upload fields
  let csvFile: File | null = null;
  let csvResult: any = null;
  let uploading = false;

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
    csvFile = null;
    csvResult = null;
    uploading = false;
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && isOpen) {
      closeModal();
    }
  }

  // Manual entry submission
  async function handleManualSubmit(event: Event) {
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

  // CSV upload submission
  async function handleCsvUpload(event: Event) {
    event.preventDefault();
    if (!csvFile || !kpiId) return;

    uploading = true;
    csvResult = null;
    try {
      const response: ApiResponse<{ inserted: number; failed: number; errors: string[] }> = await api.uploadKpiCsv(kpiId, csvFile);
      
      if (response.success) {
        csvResult = {
          status: 'success',
          message: 'CSV uploaded successfully',
          inserted: response.data?.inserted || 0,
          failed: response.data?.failed || 0,
          errors: response.data?.errors || []
        };
        dispatch('success');
        // Don't close modal immediately for CSV uploads so user can see results
      } else {
        csvResult = {
          status: 'error',
          message: response.message || 'Upload failed',
          inserted: 0,
          failed: 0,
          errors: []
        };
      }
    } catch (e) {
      csvResult = { 
        status: 'error', 
        message: e instanceof Error ? e.message : 'An unexpected error occurred.',
        inserted: 0,
        failed: 0,
        errors: []
      };
      console.error('Upload failed', e);
    } finally {
      uploading = false;
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

  // Set default date to today when modal opens in manual mode
  $: if (isOpen && !date && entryMode === 'manual') {
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

      <div class="p-6">
        <!-- Mode Toggle -->
        <div class="mb-6">
          <div class="flex rounded-lg border border-gray-300 p-1">
            <button
              type="button"
              on:click={() => entryMode = 'manual'}
              class="flex-1 py-2 cursor-pointer px-3 text-sm font-medium rounded-md transition-colors {entryMode === 'manual' ? 'bg-blue-900 text-white' : 'text-gray-700 hover:text-gray-900'}"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Manual Entry
            </button>
            <button
              type="button"
              on:click={() => entryMode = 'csv'}
              class="flex-1 py-2 cursor-pointer px-3 text-sm font-medium rounded-md transition-colors {entryMode === 'csv' ? 'bg-blue-900 text-white' : 'text-gray-700 hover:text-gray-900'}"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
              </svg>
              CSV Upload
            </button>
          </div>
        </div>

        <!-- Manual Entry Form -->
        {#if entryMode === 'manual'}
          <form on:submit={handleManualSubmit} class="space-y-4">
            <div>
              <label for="entry-date" class="block text-sm font-medium text-gray-700">Date</label>
              <div class="mt-1 flex space-x-2">
                <input 
                  type="date" 
                  id="entry-date" 
                  bind:value={date} 
                  required
                  class="flex-1 py-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
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
                class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
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

            <div class="flex justify-end space-x-3 pt-4">
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
        {/if}

        <!-- CSV Upload Form -->
        {#if entryMode === 'csv'}
          <form on:submit={handleCsvUpload} class="space-y-4">
            <div>
              <label for="csv-upload" class="block text-sm font-medium text-gray-700 mb-2">Choose CSV File</label>
              <input 
                id="csv-upload" 
                type="file" 
                accept=".csv" 
                on:change={e => {
                  const input = e.target as HTMLInputElement;
                  if (input && input.files && input.files.length > 0) {
                    csvFile = input.files[0];
                  }
                }} 
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" 
                required 
              />
              <p class="mt-1 text-xs text-gray-500">
                CSV should have 2 columns: Date (YYYY-MM-DD) and Value
              </p>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button 
                type="button" 
                on:click={closeModal} 
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                disabled={uploading || !csvFile}
                class="bg-blue-900 text-white py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {uploading ? 'Uploading...' : 'Upload CSV'}
              </button>
            </div>
          </form>
        {/if}

        <!-- CSV Upload Results -->
        {#if csvResult}
          <div class="mt-4 p-3 rounded-md {csvResult.status === 'success' ? 'bg-green-50' : 'bg-red-50'} text-sm">
            <div class="flex items-center">
              {#if csvResult.status === 'success'}
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              {:else}
                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              {/if}
              <strong class={csvResult.status === 'success' ? 'text-green-800' : 'text-red-800'}>
                {csvResult.status === 'success' ? 'Success' : 'Error'}:
              </strong>
              <span class={csvResult.status === 'success' ? 'text-green-700' : 'text-red-700'}>
                {csvResult.message}
              </span>
            </div>
            
            {#if csvResult.inserted !== undefined}
              <div class="mt-2">
                <strong>Inserted:</strong> {csvResult.inserted} entries
              </div>
            {/if}
            
            {#if csvResult.failed !== undefined && csvResult.failed > 0}
              <div class="mt-1">
                <strong>Failed:</strong> {csvResult.failed} entries
              </div>
            {/if}
            
            {#if csvResult.errors && csvResult.errors.length > 0}
              <div class="text-red-500 mt-2">
                <strong>Errors:</strong>
              </div>
              <ul class="list-disc ml-6 text-red-600">
                {#each csvResult.errors as err}
                  <li>{err}</li>
                {/each}
              </ul>
            {/if}

            {#if csvResult.status === 'success'}
              <div class="mt-3 pt-3 border-t border-green-200">
                <button 
                  on:click={closeModal}
                  class="text-green-700 hover:text-green-800 text-sm font-medium"
                >
                  Close Modal
                </button>
              </div>
            {/if}
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
