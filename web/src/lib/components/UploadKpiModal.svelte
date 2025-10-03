<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import * as api from '$lib/services/api';
  import type { ApiResponse, KpiEntry } from '$lib/types';

  export let isOpen = false;
  export let kpis: { id: number; title: string }[] = [];

  let csvFile: File | null = null;
  let csvResult: any = null;
  let uploading = false;
  let selectedKpiForUpload: number | null = null;

  const dispatch = createEventDispatcher();

  function closeModal() {
    isOpen = false;
    csvResult = null; // Reset result on close
    dispatch('close');
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && isOpen) {
      closeModal();
    }
  }

  async function handleCsvUpload(event: Event) {
    event.preventDefault();
    if (!csvFile || selectedKpiForUpload === null) return;

    uploading = true;
    csvResult = null;
    try {
      const response: ApiResponse<{ inserted: number; failed: number; errors: string[] }> = await api.uploadKpiCsv(selectedKpiForUpload, csvFile);
      
      if (response.success) {
        csvResult = {
          status: 'success',
          message: 'CSV uploaded successfully',
          inserted: response.data?.inserted || 0,
          failed: response.data?.failed || 0,
          errors: response.data?.errors || []
        };
        // Dispatch success event to notify parent component to refetch data
        dispatch('success');
        // Optionally close the modal on success
        // closeModal(); 
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
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen}
  <div
    class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-50 z-40 flex items-center justify-center"
  >
    <div
      class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-semibold">Upload KPI Data</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <div class="p-4">
        <form class="space-y-4" on:submit={handleCsvUpload}>
          <div>
            <label for="kpi-select" class="block text-sm font-medium text-gray-700 mb-1">Select KPI</label>
            <select 
              id="kpi-select" 
              bind:value={selectedKpiForUpload} 
              class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" 
              required
            >
              <option value={null} disabled>-- Select a KPI --</option>
              {#each kpis as kpi}
                <option value={kpi.id}>{kpi.title}</option>
              {/each}
            </select>
          </div>

          <div>
            <label for="csv-upload" class="block text-sm font-medium text-gray-700 mb-1">Choose CSV File</label>
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
          </div>

          <button 
            class="w-full rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" 
            type="submit" 
            disabled={uploading || !selectedKpiForUpload || !csvFile}
          >
            {uploading ? 'Uploading...' : 'Upload and Refresh'}
          </button>
        </form>

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
                  <li>{err.error} (Row: {err.row})</li>
                {/each}
              </ul>
            {/if}
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
