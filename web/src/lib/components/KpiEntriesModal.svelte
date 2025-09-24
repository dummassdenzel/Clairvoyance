<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';
  import * as api from '$lib/services/api';
  import type { Kpi, KpiEntry, ApiResponse } from '$lib/types';
  import AddKpiEntryModal from './AddKpiEntryModal.svelte';
  import EditKpiEntryModal from './EditKpiEntryModal.svelte';
  import EditKpiModal from './EditKpiModal.svelte';

  export let isOpen = false;
  export let kpi: Kpi | null = null;
  export let kpiId: number | null = null;
  export let canEdit: boolean = true;

  const dispatch = createEventDispatcher();

  let entries: KpiEntry[] = [];
  let loading = true;
  let error: string | null = null;
  let startDate = '';
  let endDate = '';
  let isAddEntryModalOpen = false;
  let isEditEntryModalOpen = false;
  let isEditKpiModalOpen = false;
  let selectedEntry: KpiEntry | null = null;
  let deletingEntryId: number | null = null;
  let sortOrder: 'asc' | 'desc' = 'desc'; // Default to descending (newest first)
  let expandedDates: string[] = []; // Track which date groups are expanded

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

  function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric' 
    });
  }

  function formatCreatedAt(createdAt: string | undefined): string {
    if (!createdAt) return '';
    const date = new Date(createdAt);
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getRagClass(value: number): string {
    if (!kpi) return 'text-gray-900';

    const { direction, rag_red, rag_amber } = kpi;
    const redThreshold = Number(rag_red);
    const amberThreshold = Number(rag_amber);

    if (isNaN(redThreshold) || isNaN(amberThreshold)) return 'text-gray-900';

    if (direction === 'higher_is_better') {
      if (value < redThreshold) return 'text-red-700';
      if (value < amberThreshold) return 'text-yellow-700';
      return 'text-green-700';
    } else if (direction === 'lower_is_better') {
      if (value > redThreshold) return 'text-red-700';
      if (value > amberThreshold) return 'text-yellow-700';
      return 'text-green-700';
    }

    return 'text-gray-900';
  }

  function getRagStatus(value: number): string {
    if (!kpi) return '';
    const redThreshold = Number(kpi.rag_red);
    const amberThreshold = Number(kpi.rag_amber);
    if (isNaN(redThreshold) || isNaN(amberThreshold)) return '';
    if (kpi.direction === 'higher_is_better') {
      if (value < redThreshold) return 'Low';
      if (value < amberThreshold) return 'Fair';
      return 'Excellent';
    } else if (kpi.direction === 'lower_is_better') {
      if (value > redThreshold) return 'Low';
      if (value > amberThreshold) return 'Fair';
      return 'Excellent';
    }
    return '';
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

  function handleAddEntrySuccess(event: CustomEvent) {
    // Refresh the entries list after adding a new entry
    loadEntries();
    
    // Notify the parent (dashboard page) that entries have been updated
    // This will trigger a refresh of all widgets that use this KPI
    dispatch('entriesUpdated', { 
      kpiId: kpiId,
      kpi: kpi 
    });
  }

  async function handleEditKpiSuccess(event: CustomEvent) {
    // Update local KPI reference and re-render header values
    const updatedKpi = event.detail as Kpi | undefined;
    if (updatedKpi) {
      kpi = updatedKpi;
      // Force new reference to trigger reactive updates in template bindings
      kpi = kpi ? { ...kpi } as Kpi : kpi;
    }
    // Ensure freshest KPI thresholds from API (in case backend applies transformations)
    try {
      if (kpiId) {
        const fresh = await api.getKpi(kpiId);
        if (fresh.success && fresh.data?.kpi) {
          kpi = { ...fresh.data.kpi } as Kpi;
        }
      }
    } catch {}
    // Force list to re-evaluate status using new thresholds immediately
    entries = [...entries];
    // Notify parent to refresh dependent widgets/dashboards
    dispatch('kpiUpdated', {
      kpiId: kpiId,
      kpi: kpi
    });
  }

  async function deleteEntry(entryId: number) {
    if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
      return;
    }

    deletingEntryId = entryId;
    try {
      const response: ApiResponse<void> = await api.deleteKpiEntry(entryId);
      
      if (response.success) {
        // Remove the entry from the local array
        entries = entries.filter(entry => entry.id !== entryId);
        // Notify parent that entries have been updated
        dispatch('entriesUpdated', { 
          kpiId: kpiId,
          kpi: kpi 
        });
      } else {
        error = response.message || 'Failed to delete entry';
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'An unexpected error occurred';
    } finally {
      deletingEntryId = null;
    }
  }

  function editEntry(entry: KpiEntry) {
    selectedEntry = entry;
    isEditEntryModalOpen = true;
  }

  function handleEditEntrySuccess(event: CustomEvent) {
    // Update the entry in the local array
    const updatedEntry = event.detail.entry;
    entries = entries.map(entry => 
      entry.id === updatedEntry.id ? updatedEntry : entry
    );
    
    // Force re-render to update updated_at timestamps
    entries = [...entries];
    
    // Notify parent that entries have been updated
    dispatch('entriesUpdated', { 
      kpiId: kpiId,
      kpi: kpi 
    });
  }

  function toggleSortOrder() {
    sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
  }

  function getSortedEntries(): KpiEntry[] {
    return [...entries].sort((a, b) => {
      const dateA = new Date(a.date).getTime();
      const dateB = new Date(b.date).getTime();
      return sortOrder === 'asc' ? dateA - dateB : dateB - dateA;
    });
  }

  // Group entries by date for display
  function getGroupedEntries(): { date: string; entries: KpiEntry[]; totalValue: number; count: number }[] {
    const grouped = new Map<string, KpiEntry[]>();
    
    entries.forEach(entry => {
      if (!grouped.has(entry.date)) {
        grouped.set(entry.date, []);
      }
      grouped.get(entry.date)!.push(entry);
    });

    const result = Array.from(grouped.entries())
      .map(([date, entries]) => ({
        date,
        entries: entries.sort((a, b) => Number(a.value) - Number(b.value)), // Sort by value within date
        totalValue: entries.reduce((sum, entry) => sum + Number(entry.value), 0),
        count: entries.length
      }))
      .sort((a, b) => {
        const dateA = new Date(a.date).getTime();
        const dateB = new Date(b.date).getTime();
        return sortOrder === 'asc' ? dateA - dateB : dateB - dateA;
      });
    
    console.log('getGroupedEntries result:', result);
    return result;
  }

  function getTotalValue(): number {
    return entries.reduce((sum, entry) => sum + Number(entry.value), 0);
  }

  function toggleDateExpansion(date: string) {
    console.log('Accordion clicked for date:', date);
    console.log('Current expandedDates:', expandedDates);
    console.log('Is currently expanded:', expandedDates.includes(date));
    
    if (expandedDates.includes(date)) {
      expandedDates = expandedDates.filter(d => d !== date);
      console.log('Collapsing - new expandedDates:', expandedDates);
    } else {
      expandedDates = [...expandedDates, date];
      console.log('Expanding - new expandedDates:', expandedDates);
    }
  }

  function isDateExpanded(date: string): boolean {
    const isExpanded = expandedDates.includes(date);
    console.log(`isDateExpanded(${date}):`, isExpanded, 'expandedDates:', expandedDates);
    return isExpanded;
  }

  $: if (isOpen && kpiId) {
    loadEntries();
  }

  $: if (startDate || endDate) {
    loadEntries();
  }

  // When RAG thresholds or direction change, force status column to recompute immediately
  $: if (kpi) {
    // Access fields to create reactive dependency
    const _ragRed = kpi.rag_red;
    const _ragAmber = kpi.rag_amber;
    const _dir = kpi.direction;
    // Reassign entries to trigger row updates without reloading
    entries = [...entries];
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
        <div class="flex items-center space-x-3">
          {#if kpi && canEdit}
            <button
              on:click={() => (isEditKpiModalOpen = true)}
              class="bg-white hover:bg-gray-100 text-blue-900 font-medium py-1 px-3 text-sm rounded-md border border-blue-900 inline-flex items-center"
              title="Edit KPI"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2 2 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z" />
              </svg>
              Edit KPI
            </button>
          {/if}
          
          <button on:click={closeModal} aria-label="Close" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
      </div>

      <div class="p-6 flex-1 overflow-hidden flex flex-col">
        <!-- Read-only notice for viewers -->
        {#if !canEdit}
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-blue-800">
              <strong>Viewer mode:</strong> You can view KPI entries but cannot add, edit, or delete them.
            </p>
          </div>
        </div>
        {/if}
        
        <!-- Date Range Controls -->
        <div class="mb-6">
          <div class="flex flex-wrap gap-2 mb-4 justify-between">
            <div class="flex items-center space-x-2">
              <div class="flex gap-2">
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
              
              <!-- Sort Toggle -->
              <div class="flex items-center space-x-2 ml-4">
                <span class="text-sm text-gray-600">Sort:</span>
                <button 
                  on:click={toggleSortOrder}
                  class="flex items-center space-x-1 px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100"
                  title={`Sort ${sortOrder === 'asc' ? 'Ascending' : 'Descending'}`}
                >
                  {#if sortOrder === 'asc'}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    <span>Ascending</span>
                  {:else}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
                    </svg>
                    <span>Descending</span>
                  {/if}
                </button>
              </div>
            </div>

            {#if canEdit}
            <div>
              <button 
                on:click={() => isAddEntryModalOpen = true}
                class="bg-blue-900 cursor-pointer hover:bg-blue-800 text-white font-medium py-1 px-4 text-sm rounded-md inline-flex items-center"
                aria-label="Add KPI entry"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Entry
              </button>
            </div>
            {/if}
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
              <input 
                type="date" 
                id="start-date" 
                bind:value={startDate} 
                class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
            <div>
              <label for="end-date" class="block text-sm font-medium text-gray-700">End Date</label>
              <input 
                type="date" 
                id="end-date" 
                bind:value={endDate} 
                class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
          </div>
          
        </div>

        <!-- Entries Accordion -->
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
                <button 
                  on:click={() => isAddEntryModalOpen = true}
                  class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  Add your first entry
                </button>
              </div>
            </div>
          {:else}
            <div class="space-y-2">
              {#each getGroupedEntries() as group}
                {#if group.count === 1}
                  <!-- Single entry - show as simple card -->
                  <!-- DEBUG: Single entry for date {group.date} -->
                  {#each group.entries as entry}
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                      <div class="p-4">
                        <div class="flex items-center justify-between">
                          <div class="flex-1">
                            <div class="flex items-center">
                              <div class="flex-shrink-0 w-32">
                                <h3 class="text-sm font-medium text-gray-900">
                                  {formatDate(entry.date)}
                                </h3>
                              </div>
                              <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                  <span class="text-lg font-semibold {getRagClass(Number(entry.value))}">
                                    {formatValue(Number(entry.value))}
                                  </span>
                                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getRagClass(Number(entry.value))} bg-opacity-10">
                                    {#if kpi}
                                      {getRagStatus(Number(entry.value))}
                                    {/if}
                                  </span>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="flex items-center space-x-4">
                            {#if entry.updated_at}
                              <div class="text-right">
                                <p class="text-xs text-gray-500">{formatCreatedAt(entry.updated_at)}</p>
                              </div>
                            {/if}
                            {#if canEdit}
                            <div class="flex items-center space-x-2">
                              <button
                              on:click={() => editEntry(entry)}
                              class="text-blue-600 hover:text-blue-800 p-2 rounded-md hover:bg-blue-50 transition-colors"
                              title="Edit entry"
                              aria-label="Edit entry"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                              </svg>
                            </button>
                            <button
                              on:click={() => deleteEntry(entry.id)}
                              disabled={deletingEntryId === entry.id}
                              class="text-red-600 hover:text-red-800 p-2 rounded-md hover:bg-red-50 transition-colors disabled:opacity-50"
                              title="Delete entry"
                              aria-label="Delete entry"
                            >
                              {#if deletingEntryId === entry.id}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                              {:else}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                              {/if}
                            </button>
                            </div>
                            {/if}
                          </div>
                        </div>
                      </div>
                    </div>
                  {/each}
                {:else}
                  <!-- Multiple entries - show as accordion -->
                  <!-- DEBUG: Multiple entries for date {group.date}, count: {group.count} -->
                  <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <!-- Accordion Header -->
                    <button
                      on:click={() => {
                        console.log('Button clicked!');
                        toggleDateExpansion(group.date);
                      }}
                      class="w-full p-4 text-left hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset transition-colors"
                    >
                        <div class="flex items-center justify-between">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 w-32">
                            <h3 class="text-sm font-medium text-gray-900">
                              {formatDate(group.date)}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                              {group.count} {group.count === 1 ? 'entry' : 'entries'}
                            </p>
                          </div>
                          <div class="flex-1">
                            <div class="flex items-center space-x-3">
                              <span class="text-lg font-semibold {getRagClass(group.totalValue)}">
                                {formatValue(group.totalValue)}
                              </span>
                              <span class="text-xs text-gray-500">(total)</span>
                              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {getRagClass(group.totalValue)} bg-opacity-10">
                                {#if kpi}
                                  {getRagStatus(group.totalValue)}
                                {/if}
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="flex items-center space-x-4">
                          <!-- Commented for now -->
                          <!-- {#if group.entries.length > 0 && group.entries[0].updated_at}
                            <div class="text-right">
                              <p class="text-xs text-gray-500">
                                Latest at {formatCreatedAt(group.entries[0].updated_at)}
                              </p>
                            </div>
                          {/if} -->
                          <div class="flex-shrink-0">
                            <svg 
                              class="h-5 w-5 text-gray-400 transition-transform duration-200 {expandedDates.includes(group.date) ? 'rotate-180' : ''}" 
                              fill="none" 
                              viewBox="0 0 24 24" 
                              stroke="currentColor"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                          </div>
                        </div>
                      </div>
                    </button>
                    
                    <!-- Accordion Content -->
                    {#if expandedDates.includes(group.date)}
                      <div class="border-t border-gray-200 bg-gray-50">
                        <div class="p-4 space-y-3">
                          {#each group.entries as entry, index}
                            <div class="bg-white rounded-md border border-gray-200 p-3">
                              <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                  <span class="text-xs text-gray-400 font-mono bg-gray-100 px-2 py-1 rounded">
                                    #{index + 1}
                                  </span>
                                  <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-800">
                                      {formatValue(Number(entry.value))}
                                    </span>
                                  </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                  {#if entry.updated_at}
                                  <div class="text-right">
                                    <p class="text-xs text-gray-500">{formatCreatedAt(entry.updated_at)}</p>
                                  </div>
                                  {/if}
                                  {#if canEdit}
                                  <div class="flex items-center space-x-1">
                                    <button
                                    on:click={() => editEntry(entry)}
                                    class="text-blue-600 hover:text-blue-800 p-1.5 rounded-md hover:bg-blue-50 transition-colors"
                                    title="Edit entry"
                                    aria-label="Edit entry"
                                  >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                  </button>
                                  <button
                                    on:click={() => deleteEntry(entry.id)}
                                    disabled={deletingEntryId === entry.id}
                                    class="text-red-600 hover:text-red-800 p-1.5 rounded-md hover:bg-red-50 transition-colors disabled:opacity-50"
                                    title="Delete entry"
                                    aria-label="Delete entry"
                                  >
                                    {#if deletingEntryId === entry.id}
                                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                      </svg>
                                    {:else}
                                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                      </svg>
                                    {/if}
                                  </button>
                                  </div>
                                  {/if}
                                </div>
                              </div>
                            </div>
                          {/each}
                        </div>
                      </div>
                    {/if}
                  </div>
                {/if}
              {/each}
            </div>
          {/if}
        </div>

        <!-- Summary Stats -->
        {#if entries.length > 0}
          <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{entries.length}</p>
                <p class="text-sm text-gray-500">Total Entries</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-blue-900">{formatValue(getTotalValue())}</p>
                <p class="text-sm text-gray-500">Total Value</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-blue-900">{formatValue(Math.max(...entries.map(e => Number(e.value))))}</p>
                <p class="text-sm text-gray-500">Highest</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-blue-900">{formatValue(Math.min(...entries.map(e => Number(e.value))))}</p>
                <p class="text-sm text-gray-500">Lowest</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-blue-900">{formatValue(entries.reduce((sum, e) => sum + Number(e.value), 0) / entries.length)}</p>
                <p class="text-sm text-gray-500">Average</p>
              </div>
            </div>
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}

<!-- Edit KPI Entry Modal -->
<EditKpiEntryModal
  bind:isOpen={isEditEntryModalOpen}
  entry={selectedEntry}
  on:success={handleEditEntrySuccess}
  on:close={() => {
    isEditEntryModalOpen = false;
    selectedEntry = null;
  }}
/>

<!-- Edit KPI Modal -->
<EditKpiModal
  bind:show={isEditKpiModalOpen}
  kpi={kpi}
  on:success={handleEditKpiSuccess}
  on:close={() => (isEditKpiModalOpen = false)}
/>

<!-- Add KPI Entry Modal -->
<AddKpiEntryModal
  bind:isOpen={isAddEntryModalOpen}
  kpiId={kpiId}
  kpiName={kpi?.name || ''}
  on:success={handleAddEntrySuccess}
  on:close={() => isAddEntryModalOpen = false}
/>
