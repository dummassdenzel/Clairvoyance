<script lang="ts">
  import { createEventDispatcher, tick } from 'svelte';
  import * as api from '$lib/services/api';

  export let show = false;
  export let widget: any = {};

  let internalWidget: any = {};
  let dialogElement: HTMLDivElement;
  let kpis: any[] = [];
  let kpisError: string | null = null;

  const dispatch = createEventDispatcher();

  async function loadKpis() {
    kpisError = null;
    try {
      const response = await api.getKpis();
      if (response && response.data) {
        kpis = response.data;
      } else {
        kpisError = response.message || 'Failed to load KPIs.';
      }
    } catch (e: any) {
      kpisError = e.message || 'An unexpected error occurred.';
    }
  }

  function handleSubmit() {
    dispatch('save', internalWidget);
    closeModal();
  }

  function closeModal() {
    show = false;
    dispatch('close');
  }

  function handleKeydown(event: KeyboardEvent) {
    if (show && event.key === 'Escape') {
      closeModal();
    }
  }

  function setDateRange(range: '7d' | '30d' | 'month' | 'ytd') {
    const endDate = new Date();
    let startDate = new Date();

    switch (range) {
      case '7d':
        startDate.setDate(endDate.getDate() - 7);
        break;
      case '30d':
        startDate.setDate(endDate.getDate() - 30);
        break;
      case 'month':
        startDate = new Date(endDate.getFullYear(), endDate.getMonth(), 1);
        break;
      case 'ytd':
        startDate = new Date(endDate.getFullYear(), 0, 1);
        break;
    }

    const formatDate = (date: Date) => date.toISOString().split('T')[0];

    internalWidget.startDate = formatDate(startDate);
    internalWidget.endDate = formatDate(endDate);
  }

  $: if (show && dialogElement) {
    tick().then(() => {
      dialogElement.focus();
    });
  }

  $: if (show) {
    // When the modal is shown, clone the widget data and load KPIs
    internalWidget = { ...widget };
    loadKpis();
  }

  const chartTypes = ['line', 'bar', 'pie', 'doughnut'];

  // Set default colors if they don't exist
  $: if (internalWidget) {
    internalWidget.backgroundColor = internalWidget.backgroundColor || '#36a2eb4d'; // Default to semi-transparent blue
    internalWidget.borderColor = internalWidget.borderColor || '#36a2eb'; // Default to solid blue

    // Set default aggregation if switching to single-value type
    if (internalWidget.type === 'single-value' && !internalWidget.aggregation) {
      internalWidget.aggregation = 'latest';
    }

    // Default showLegend to true if it's not defined
    if (internalWidget.showLegend === undefined) {
      internalWidget.showLegend = true;
    }
    // Default legendPosition to 'top' if it's not defined
    if (internalWidget.legendPosition === undefined) {
      internalWidget.legendPosition = 'top';
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)] z-50 flex items-center justify-center" on:click={closeModal} role="presentation">
    <div bind:this={dialogElement} class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" on:click|stopPropagation role="dialog" aria-modal="true" tabindex="-1">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Widget Settings</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <form on:submit|preventDefault={handleSubmit}>
        <div class="space-y-4">
          <div>
            <label for="widget-title" class="block text-sm font-medium text-gray-700">Title</label>
            <input type="text" id="widget-title" bind:value={internalWidget.title} required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>
          <div>
            <label for="widget-kpi" class="block text-sm font-medium text-gray-700">Data Source (KPI)</label>
            <select id="widget-kpi" bind:value={internalWidget.kpi_id} class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
              <option value={null}>-- Select a KPI --</option>
              {#if kpis.length > 0}
                {#each kpis as kpi}
                  <option value={kpi.id}>{kpi.name}</option>
                {/each}
              {/if}
            </select>
            {#if kpisError}
              <p class="mt-2 text-sm text-red-600">{kpisError}</p>
            {/if}
          </div>
          <div>
            <label for="widget-type" class="block text-sm font-medium text-gray-700">Widget Type</label>
            <select id="widget-type" bind:value={internalWidget.type} class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
              <optgroup label="Charts">
                <option value="line">Line Chart</option>
                <option value="bar">Bar Chart</option>
                <option value="pie">Pie Chart</option>
                <option value="doughnut">Doughnut Chart</option>
              </optgroup>
              <optgroup label="Other">
                <option value="single-value">Single Value</option>
              </optgroup>
            </select>
          </div>

          {#if ['line', 'bar', 'pie', 'doughnut'].includes(internalWidget.type)}
            <div class="p-3 bg-gray-50 rounded-md border border-gray-200">
              <div class="flex items-center justify-between">
                <label for="widget-show-legend" class="text-sm font-medium text-gray-900">Show Legend</label>
                <input id="widget-show-legend" type="checkbox" bind:checked={internalWidget.showLegend} class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
              </div>
              {#if internalWidget.showLegend}
                <div class="mt-3">
                  <label for="widget-legend-position" class="block text-sm font-medium text-gray-700">Legend Position</label>
                  <select id="widget-legend-position" bind:value={internalWidget.legendPosition} class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="top">Top</option>
                    <option value="bottom">Bottom</option>
                    <option value="left">Left</option>
                    <option value="right">Right</option>
                  </select>
                </div>
              {/if}
            </div>
          {/if}

          {#if internalWidget.type === 'single-value'}
          <div>
            <label for="widget-aggregation" class="block text-sm font-medium text-gray-700">Value to Display</label>
            <select id="widget-aggregation" bind:value={internalWidget.aggregation} class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
              <option value="latest">Most Recent Value</option>
              <option value="sum">Sum of Values</option>
              <option value="average">Average of Values</option>
            </select>
          </div>
          {/if}

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="widget-bg-color" class="block text-sm font-medium text-gray-700">Background Color</label>
              <input type="color" id="widget-bg-color" bind:value={internalWidget.backgroundColor} class="mt-1 block w-full h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
              <label for="widget-border-color" class="block text-sm font-medium text-gray-700">Border Color</label>
              <input type="color" id="widget-border-color" bind:value={internalWidget.borderColor} class="mt-1 block w-full h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="widget-start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
              <input type="date" id="widget-start-date" bind:value={internalWidget.startDate} class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label for="widget-end-date" class="block text-sm font-medium text-gray-700">End Date</label>
              <input type="date" id="widget-end-date" bind:value={internalWidget.endDate} class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
          </div>

          <div>
            <div class="flex justify-between items-center space-x-2">
              <button type="button" on:click={() => setDateRange('7d')} class="w-full text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">Last 7 Days</button>
              <button type="button" on:click={() => setDateRange('30d')} class="w-full text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">Last 30 Days</button>
              <button type="button" on:click={() => setDateRange('month')} class="w-full text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">This Month</button>
              <button type="button" on:click={() => setDateRange('ytd')} class="w-full text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">Year to Date</button>
            </div>
          </div>

        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <button type="button" on:click={closeModal} class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Cancel
          </button>
          <button type="submit" class="bg-blue-600 text-white py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
