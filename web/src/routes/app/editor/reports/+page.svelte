<!-- Reports Page -->
<script lang="ts">
  import { onMount } from 'svelte';
  import { exportData, type ExportParams } from '$lib/stores/data';
  import { kpis, type KPI, fetchKPIs } from '$lib/stores/kpi';
  import { authStore } from '$lib/stores/auth';

  let loading = true;
  let error: string | null = null;
  let success: string | null = null;
  let kpiList: KPI[] = [];
  let selectedKPIs: number[] = [];
  let dateFrom: string = '';
  let dateTo: string = '';
  let exportFormat: 'csv' | 'xlsx' | 'json' | 'pdf' = 'pdf';
  let reportType: 'kpis' | 'dashboard' | 'measurements' = 'measurements';

  // Subscribe to KPI store
  kpis.subscribe(state => {
    kpiList = state;
  });

  onMount(async () => {
    const result = await fetchKPIs();
    if (!result.success) {
      error = result.message || 'Failed to load KPIs';
    }
    loading = false;
  });

  async function handleExport() {
    try {
      loading = true;
      const params: ExportParams = {
        entity: reportType,
        format: exportFormat,
        kpi_ids: selectedKPIs.length > 0 ? selectedKPIs : undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined
      };

      const result = await exportData(params);
      
      if (result.success) {
        success = 'Report generated successfully';
      } else {
        error = result.message || 'Failed to generate report';
      }
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to generate report';
      success = null;
    } finally {
      loading = false;
    }
  }

  function clearMessages() {
    error = null;
    success = null;
  }

  function toggleKPI(kpiId: number) {
    const index = selectedKPIs.indexOf(kpiId);
    if (index === -1) {
      selectedKPIs = [...selectedKPIs, kpiId];
    } else {
      selectedKPIs = selectedKPIs.filter(id => id !== kpiId);
    }
  }
</script>

<div class="space-y-6">
  <!-- Header -->
  <div class="sm:flex sm:items-center sm:justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
      <p class="mt-2 text-sm text-gray-700">
        Generate and export reports for your KPIs and dashboards.
      </p>
    </div>
  </div>

  <!-- Messages -->
  {#if error}
    <div class="rounded-md bg-red-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{error}</p>
          </div>
        </div>
        <div class="ml-auto pl-3">
          <div class="-mx-1.5 -my-1.5">
            <button
              type="button"
              class="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50"
              on:click={clearMessages}
            >
              <span class="sr-only">Dismiss</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  {/if}

  {#if success}
    <div class="rounded-md bg-green-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">Success</h3>
          <div class="mt-2 text-sm text-green-700">
            <p>{success}</p>
          </div>
        </div>
        <div class="ml-auto pl-3">
          <div class="-mx-1.5 -my-1.5">
            <button
              type="button"
              class="inline-flex rounded-md bg-green-50 p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50"
              on:click={clearMessages}
            >
              <span class="sr-only">Dismiss</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  {/if}

  <!-- Report Generation Form -->
  <div class="bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:p-6">
      <h3 class="text-lg font-medium leading-6 text-gray-900">Generate Report</h3>
      <div class="mt-5">
        <div class="space-y-4">
          <div>
            <label for="report-type" class="block text-sm font-medium text-gray-700">Report Type</label>
            <select
              id="report-type"
              bind:value={reportType}
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="measurements">KPI Measurements</option>
              <option value="kpis">KPI Overview</option>
              <option value="dashboard">Dashboard Report</option>
            </select>
          </div>

          <div>
            <label for="export-format" class="block text-sm font-medium text-gray-700">Export Format</label>
            <select
              id="export-format"
              bind:value={exportFormat}
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="pdf">PDF</option>
              <option value="csv">CSV</option>
              <option value="xlsx">Excel</option>
              <option value="json">JSON</option>
            </select>
          </div>

          {#if reportType === 'measurements'}
            <div>
              <label class="block text-sm font-medium text-gray-700">Select KPIs</label>
              <div class="mt-2 space-y-2 max-h-60 overflow-y-auto">
                {#each kpiList as kpi}
                  <div class="flex items-center">
                    <input
                      type="checkbox"
                      id="kpi-{kpi.id}"
                      checked={selectedKPIs.includes(kpi.id)}
                      on:change={() => toggleKPI(kpi.id)}
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label for="kpi-{kpi.id}" class="ml-3 text-sm text-gray-700">
                      {kpi.name}
                    </label>
                  </div>
                {/each}
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label for="date-from" class="block text-sm font-medium text-gray-700">Date From</label>
                <input
                  type="date"
                  id="date-from"
                  bind:value={dateFrom}
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="date-to" class="block text-sm font-medium text-gray-700">Date To</label>
                <input
                  type="date"
                  id="date-to"
                  bind:value={dateTo}
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
          {/if}
        </div>
        <div class="mt-5">
          <button
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            on:click={handleExport}
            disabled={loading || (reportType === 'measurements' && selectedKPIs.length === 0)}
          >
            {#if loading}
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            {/if}
            Generate Report
          </button>
        </div>
      </div>
    </div>
  </div>
</div> 