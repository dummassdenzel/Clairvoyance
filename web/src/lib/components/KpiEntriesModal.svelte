<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';
  import * as api from '$lib/services/api';
  import type { Kpi, KpiEntry, ApiResponse } from '$lib/types';

  export let isOpen = false;
  export let kpi: Kpi | null = null;
  export let kpiId: number | null = null;

  const dispatch = createEventDispatcher();

  let entries: KpiEntry[] = [];
  let loading = true;
  let error: string | null = null;
  let startDate = '';
  let endDate = '';

  function closeModal() {
    isOpen = false;
    dispatch('close');
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && isOpen) {
      closeModal();
    }
  }

  async function loadEntries() {
    if (!kpiId) return;

    loading = true;
    error = null;
    try {
      const response: ApiResponse<{ entries: KpiEntry[] }> = await api.getKpiEntries(
        kpiId, 
        startDate || undefined, 
        endDate || undefined
      );
      
      if (response.success && response.data) {
        entries = response.data.entries || [];
      } else {
        error = response.message || 'Failed to load KPI entries';
        entries = [];
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'An unexpected error occurred';
      entries = [];
    } finally {
      loading = false;
    }
  }

  function formatValue(value: number): string {
    if (!kpi) return value.toString();
    return `${kpi.format_prefix || ''}${value.toLocaleString()}${kpi.format_suffix || ''}`;
  }

  function getRagClass(value: number): string {
    if (!kpi) return 'text-gray-900';

    const { direction, rag_red, rag_amber } = kpi;
    const redThreshold = Number(rag_red);
    const amberThreshold = Number(rag_amber);

    if (isNaN(redThreshold) || isNaN(amberThreshold)) return 'text-gray-900';

    if (direction === 'higher_is_better') {
      if (value < redThreshold) return 'text-red-500';
      if (value < amberThreshold) return 'text-yellow-500';
      return 'text-green-500';
    } else if (direction === 'lower_is_better') {
      if (value > redThreshold) return 'text-red-500';
      if (value > amberThreshold) return 'text-yellow-500';
      return 'text-green-500';
    }

    return 'text-gray-900';
  }

  function setDateRange(range: '7d' | '30d' | 'month' | 'ytd' | 'all') {
    if (range === 'all') {
      startDate = '';
      endDate = '';
    } else {
      const end = new Date();
      let start = new Date();

      switch (range) {
        case '7d':
          start.setDate(end.getDate() - 7);
          break;
        case '30d':
          start.setDate(end.getDate() - 30);
          break;
        case 'month':
          start = new Date(end.getFullYear(), end.getMonth(), 1);
          break;
        case 'ytd':
          start = new Date(end.getFullYear(), 0, 1);
          break;
      }

      const formatDate = (date: Date) => date.toISOString().split('T')[0];
      startDate = formatDate(start);
      endDate = formatDate(end);
    }
    
    loadEntries();
  }

  $: if (isOpen && kpiId) {
    loadEntries();
  }

  $: if (startDate || endDate) {
    loadEntries();
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen}
  <div
    class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-50 z-50 flex items-center justify-center"
  >
    <div
      class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] flex flex-col"
      role="dialog"
      aria-modal="true"
    >
      <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">{kpi?.name || 'KPI Entries'}</h2>
          {#if kpi}
            <p class="mt-1 text-sm text-gray-600">
              Target: {formatValue(Number(kpi.target))} | 
              Direction: {kpi.direction === 'higher_is_better' ? 'Higher is Better' : 'Lower is Better'}
            </p>
          {/if}
        </div>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
      </div>

      <div class="p-6 flex-1 overflow-hidden flex flex-col">
        <!-- Date Range Controls -->
        <div class="mb-6">
          <div class="flex flex-wrap gap-2 mb-4">
            <button 
              on:click={() => setDateRange('all')} 
              class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100 {!startDate && !endDate ? 'bg-blue-100 border-blue-300' : ''}"
            >
              All Time
            </button>
            <button 
              on:click={() => setDateRange('7d')} 
              class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100 {startDate && endDate ? 'bg-blue-100 border-blue-300' : ''}"
            >
              Last 7 Days
            </button>
            <button 
              on:click={() => setDateRange('30d')} 
              class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100"
            >
              Last 30 Days
            </button>
            <button 
              on:click={() => setDateRange('month')} 
              class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100"
            >
              This Month
            </button>
            <button 
              on:click={() => setDateRange('ytd')} 
              class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100"
            >
              Year to Date
            </button>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
              <input 
                type="date" 
                id="start-date" 
                bind:value={startDate} 
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
            <div>
              <label for="end-date" class="block text-sm font-medium text-gray-700">End Date</label>
              <input 
                type="date" 
                id="end-date" 
                bind:value={endDate} 
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
          </div>
        </div>

        <!-- Entries Table -->
        <div class="flex-1 overflow-auto">
          {#if loading}
            <div class="flex items-center justify-center h-32">
              <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2 text-sm text-gray-500">Loading entries...</p>
              </div>
            </div>
          {:else if error}
            <div class="flex items-center justify-center h-32">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p class="mt-2 text-sm text-red-600">{error}</p>
              </div>
            </div>
          {:else if entries.length === 0}
            <div class="flex items-center justify-center h-32">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="mt-2 text-sm text-gray-500">No entries found for the selected date range</p>
              </div>
            </div>
          {:else}
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  {#each entries as entry}
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {new Date(entry.date).toLocaleDateString()}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {getRagClass(Number(entry.value))}">
                        {formatValue(Number(entry.value))}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getRagClass(Number(entry.value))} bg-opacity-10">
                          {#if kpi}
                            {@const value = Number(entry.value)}
                            {@const redThreshold = Number(kpi.rag_red)}
                            {@const amberThreshold = Number(kpi.rag_amber)}
                            {#if kpi.direction === 'higher_is_better'}
                              {#if value < redThreshold}
                                Red
                              {:else if value < amberThreshold}
                                Amber
                              {:else}
                                Green
                              {/if}
                            {:else}
                              {#if value > redThreshold}
                                Red
                              {:else if value > amberThreshold}
                                Amber
                              {:else}
                                Green
                              {/if}
                            {/if}
                          {/if}
                        </span>
                      </td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          {/if}
        </div>

        <!-- Summary Stats -->
        {#if entries.length > 0}
          <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{entries.length}</p>
                <p class="text-sm text-gray-500">Total Entries</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-blue-600">{formatValue(Math.max(...entries.map(e => Number(e.value))))}</p>
                <p class="text-sm text-gray-500">Highest</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-green-600">{formatValue(Math.min(...entries.map(e => Number(e.value))))}</p>
                <p class="text-sm text-gray-500">Lowest</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-purple-600">{formatValue(entries.reduce((sum, e) => sum + Number(e.value), 0) / entries.length)}</p>
                <p class="text-sm text-gray-500">Average</p>
              </div>
            </div>
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
