<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import * as api from '$lib/services/api';

  export let isOpen: boolean = false;
  export let kpiId: number;

  const dispatch = createEventDispatcher();

  let csvFile: File | null = null;
  let csvPreview: { headers: string[]; preview_rows: string[][]; total_rows: number } | null = null;
  let csvOptions = {
    dateColumn: '',
    valueColumn: '',
    hasHeader: true
  };
  let uploading = false;
  let previewing = false;
  let csvResult: { status: 'success' | 'error'; message: string; inserted: number; failed: number; errors: string[] } | null = null;
  let error = '';

  function closeModal() {
    isOpen = false;
    csvFile = null;
    csvPreview = null;
    csvOptions = { dateColumn: '', valueColumn: '', hasHeader: true };
    csvResult = null;
    error = '';
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      closeModal();
    }
  }

  async function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input && input.files && input.files.length > 0) {
      csvFile = input.files[0];
      csvPreview = null;
      csvResult = null;
      error = '';
      
      // Auto-preview the file
      await previewCsv();
    }
  }

  async function previewCsv() {
    if (!csvFile) return;

    previewing = true;
    error = '';
    
    try {
      const response = await api.previewKpiCsv(csvFile);
      
      if (response.success && response.data) {
        csvPreview = response.data;
        
        // Auto-detect likely date and value columns
        const headers = response.data.headers;
        const dateColumn = headers.find((h: string) => 
          h.toLowerCase().includes('date') || 
          h.toLowerCase().includes('time') ||
          h.toLowerCase().includes('created') ||
          h.toLowerCase().includes('updated')
        );
        const valueColumn = headers.find((h: string) => 
          h.toLowerCase().includes('value') || 
          h.toLowerCase().includes('score') ||
          h.toLowerCase().includes('amount') ||
          h.toLowerCase().includes('count') ||
          h.toLowerCase().includes('total') ||
          h.toLowerCase().includes('exam_score') ||
          h.toLowerCase().includes('attendance_percent')
        );
        
        csvOptions.dateColumn = dateColumn || headers[0] || '';
        csvOptions.valueColumn = valueColumn || headers[1] || '';
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to preview CSV file';
    } finally {
      previewing = false;
    }
  }

  async function handleCsvUpload(event: Event) {
    event.preventDefault();
    if (!csvFile || !kpiId) return;

    uploading = true;
    csvResult = null;
    error = '';
    
    try {
      const response = await api.uploadKpiCsv(kpiId, csvFile, csvOptions);
      
      if (response.success) {
        csvResult = {
          status: 'success',
          message: response.message || 'CSV uploaded successfully',
          inserted: response.data?.inserted || 0,
          failed: response.data?.failed || 0,
          errors: response.data?.errors || []
        };
        dispatch('success');
        // Don't close modal immediately so user can see results
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
    class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-50 z-60 flex items-center justify-center"
    on:click={closeModal}
    on:keydown={(e) => e.key === 'Escape' && closeModal()}
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    tabindex="-1"
  >
    <div
      class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto"
      on:click|stopPropagation
      on:keydown|stopPropagation
      role="document"
    >
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 id="modal-title" class="text-lg font-medium text-gray-900">
            Upload KPI Data from CSV
          </h3>
          <button
            type="button"
            on:click={closeModal}
            class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="Close modal"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="px-6 py-4 space-y-6">
        <!-- File Selection -->
        <div>
          <label for="csv-upload" class="block text-sm font-medium text-gray-700 mb-2">
            Choose CSV File
          </label>
          <input 
            id="csv-upload" 
            type="file" 
            accept=".csv" 
            on:change={handleFileSelect}
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" 
            required 
          />
          <p class="mt-1 text-xs text-gray-500">
            Select a CSV file with your data. We'll help you map the columns.
          </p>
        </div>

        <!-- Preview Section -->
        {#if previewing}
          <div class="flex items-center justify-center py-8">
            <div class="flex items-center space-x-2">
              <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-sm text-gray-600">Analyzing CSV file...</span>
            </div>
          </div>
        {/if}

        {#if csvPreview}
          <!-- Column Mapping -->
          <div class="bg-gray-50 rounded-lg p-4 space-y-4">
            <h4 class="text-sm font-medium text-gray-900">Map Your Data Columns</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="date-column" class="block text-sm font-medium text-gray-700 mb-1">
                  Date Column
                </label>
                <select 
                  id="date-column" 
                  bind:value={csvOptions.dateColumn}
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option value="">Select date column...</option>
                  {#each csvPreview.headers as header, index}
                    <option value={header}>{header} (Column {index + 1})</option>
                  {/each}
                </select>
              </div>

              <div>
                <label for="value-column" class="block text-sm font-medium text-gray-700 mb-1">
                  Value Column
                </label>
                <select 
                  id="value-column" 
                  bind:value={csvOptions.valueColumn}
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option value="">Select value column...</option>
                  {#each csvPreview.headers as header, index}
                    <option value={header}>{header} (Column {index + 1})</option>
                  {/each}
                </select>
              </div>
            </div>

            <div class="flex items-center">
              <input 
                id="has-header" 
                type="checkbox" 
                bind:checked={csvOptions.hasHeader}
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="has-header" class="ml-2 block text-sm text-gray-700">
                First row contains column headers
              </label>
            </div>
          </div>

          <!-- Data Preview -->
          <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
              <h4 class="text-sm font-medium text-gray-900">
                Data Preview ({csvPreview.total_rows} total rows)
              </h4>
            </div>
            
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    {#each csvPreview.headers as header, index}
                      <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {header}
                        {#if csvOptions.dateColumn === header}
                          <span class="ml-1 text-blue-600 text-xs">(Date)</span>
                        {/if}
                        {#if csvOptions.valueColumn === header}
                          <span class="ml-1 text-green-600 text-xs">(Value)</span>
                        {/if}
                      </th>
                    {/each}
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  {#each csvPreview.preview_rows as row}
                    <tr>
                      {#each row as cell, index}
                        <td class="px-3 py-2 text-sm text-gray-900 {csvOptions.dateColumn === csvPreview.headers[index] ? 'bg-blue-50' : ''} {csvOptions.valueColumn === csvPreview.headers[index] ? 'bg-green-50' : ''}">
                          {cell}
                        </td>
                      {/each}
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          </div>
        {/if}

        <!-- Error Display -->
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

        <!-- Upload Results -->
        {#if csvResult}
          <div class="p-4 rounded-md {csvResult.status === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}">
            <div class="flex">
              {#if csvResult.status === 'success'}
                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              {:else}
                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              {/if}
              <div class="flex-1">
                <p class="text-sm font-medium {csvResult.status === 'success' ? 'text-green-800' : 'text-red-800'}">
                  {csvResult.message}
                </p>
                {#if csvResult.status === 'success'}
                  <div class="mt-2 text-sm text-green-700">
                    <p>✅ {csvResult.inserted} entries added successfully</p>
                    {#if csvResult.failed > 0}
                      <p>⚠️ {csvResult.failed} entries failed to import</p>
                    {/if}
                    {#if csvResult.errors.length > 0}
                      <details class="mt-2">
                        <summary class="cursor-pointer text-sm font-medium">View error details</summary>
                        <ul class="mt-1 text-xs text-green-600 list-disc list-inside">
                          {#each csvResult.errors as error}
                            <li>{error}</li>
                          {/each}
                        </ul>
                      </details>
                    {/if}
                  </div>
                {/if}
              </div>
            </div>
          </div>
        {/if}
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
        <button 
          type="button" 
          on:click={closeModal} 
          class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          {csvResult ? 'Close' : 'Cancel'}
        </button>
        
        {#if csvPreview && csvOptions.dateColumn && csvOptions.valueColumn}
          <button 
            type="button" 
            on:click={handleCsvUpload}
            disabled={uploading}
            class="bg-blue-900 text-white py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {uploading ? 'Uploading...' : 'Upload Data'}
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
