<!-- Reports Page -->
<script lang="ts">
  import { onMount } from 'svelte';
  import { reports, fetchReports } from '$lib/stores/report';
  import { dashboards, fetchDashboards } from '$lib/stores/dashboard';
  import { token } from '$lib/stores/auth';

  let loading = true;
  let error: string | null = null;
  let selectedDashboard: number | null = null;
  let reportFormat: 'pdf' | 'xlsx' = 'pdf';
  let timeRange = {
    start: '',
    end: ''
  };

  onMount(async () => {
    try {
      loading = true;
      await Promise.all([
        fetchReports(),
        fetchDashboards()
      ]);
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to load reports';
    } finally {
      loading = false;
    }
  });

  async function generateReport() {
    if (!selectedDashboard) return;
    
    try {
      loading = true;
      // Report generation will be implemented
      // This is a placeholder for the actual implementation
      alert('Report generation will be implemented');
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to generate report';
    } finally {
      loading = false;
    }
  }
</script>

<div class="space-y-6">
  <!-- Header -->
  <div class="md:flex md:items-center md:justify-between">
    <div class="flex-1 min-w-0">
      <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Reports
      </h2>
    </div>
  </div>

  <!-- Loading State -->
  {#if loading}
    <div class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
    </div>
  <!-- Error State -->
  {:else if error}
    <div class="rounded-md bg-red-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading reports</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{error}</p>
          </div>
        </div>
      </div>
    </div>
  <!-- Reports Content -->
  {:else}
    <!-- Generate Report Form -->
    <div class="bg-white shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Generate New Report
        </h3>
        <div class="mt-5">
          <form class="space-y-6">
            <!-- Dashboard Selection -->
            <div>
              <label for="dashboard" class="block text-sm font-medium text-gray-700">
                Select Dashboard
              </label>
              <select
                id="dashboard"
                bind:value={selectedDashboard}
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                <option value={null}>Select a dashboard</option>
                {#each $dashboards as dashboard}
                  <option value={dashboard.id}>{dashboard.name}</option>
                {/each}
              </select>
            </div>

            <!-- Report Format -->
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Report Format
              </label>
              <div class="mt-2 space-x-4">
                <label class="inline-flex items-center">
                  <input
                    type="radio"
                    bind:group={reportFormat}
                    value="pdf"
                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                  >
                  <span class="ml-2 text-sm text-gray-700">PDF</span>
                </label>
                <label class="inline-flex items-center">
                  <input
                    type="radio"
                    bind:group={reportFormat}
                    value="xlsx"
                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                  >
                  <span class="ml-2 text-sm text-gray-700">Excel (XLSX)</span>
                </label>
              </div>
            </div>

            <!-- Time Range -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label for="start-date" class="block text-sm font-medium text-gray-700">
                  Start Date
                </label>
                <input
                  type="date"
                  id="start-date"
                  bind:value={timeRange.start}
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
              </div>
              <div>
                <label for="end-date" class="block text-sm font-medium text-gray-700">
                  End Date
                </label>
                <input
                  type="date"
                  id="end-date"
                  bind:value={timeRange.end}
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
              </div>
            </div>

            <div>
              <button
                type="button"
                on:click={generateReport}
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Generate Report
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Existing Reports -->
    <div class="bg-white shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Recent Reports
        </h3>
        <div class="mt-5">
          <div class="flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
              <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Format
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Created
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                          <span class="sr-only">Download</span>
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      {#each $reports as report}
                        <tr>
                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {report.name}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {report.format.toUpperCase()}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {new Date(report.created_at).toLocaleDateString()}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a
                              href={report.download_url}
                              class="text-indigo-600 hover:text-indigo-900"
                            >
                              Download
                            </a>
                          </td>
                        </tr>
                      {/each}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  {/if}
</div> 