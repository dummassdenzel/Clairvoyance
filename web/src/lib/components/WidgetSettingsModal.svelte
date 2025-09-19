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

  function setDateRange(range: '7d' | '30d' | 'month' | 'ytd' | 'all') {
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
      case 'all':
        // Clear both dates to include all data (better approach than setting to 1900)
        internalWidget.startDate = null;
        internalWidget.endDate = null;
        return; // Exit early since we're not using the date formatting below
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

    // Set default gap threshold for line charts
    if (internalWidget.type === 'line' && internalWidget.gapThresholdDays === undefined) {
      internalWidget.gapThresholdDays = 7;
    }

    // Set default time unit and aggregation method for line charts
    if (internalWidget.type === 'line' && internalWidget.timeUnit === undefined) {
      internalWidget.timeUnit = 'day';
    }
    if (internalWidget.type === 'line' && internalWidget.aggregationMethod === undefined) {
      internalWidget.aggregationMethod = 'average';
    }

    // Auto-assign gap threshold based on time unit
    if (internalWidget.type === 'line' && internalWidget.timeUnit) {
      switch (internalWidget.timeUnit) {
        case 'day':
          internalWidget.gapThresholdDays = 7; // 1 week
          break;
        case 'week':
          internalWidget.gapThresholdDays = 14; // 2 weeks
          break;
        case 'month':
          internalWidget.gapThresholdDays = 60; // 2 months
          break;
        case 'year':
          internalWidget.gapThresholdDays = 365; // 1 year
          break;
      }
    }

    // Remove gap handling mode since we only use broken lines
    // if (internalWidget.type === 'line' && internalWidget.gapHandlingMode === undefined) {
    //   internalWidget.gapHandlingMode = 'broken';
    // }

    // Set default chart mode for bar charts
    if (internalWidget.type === 'bar' && internalWidget.chartMode === undefined) {
      internalWidget.chartMode = 'time-based';
    }

    // Set default time unit and aggregation method for bar charts
    if (internalWidget.type === 'bar' && internalWidget.timeUnit === undefined) {
      internalWidget.timeUnit = 'day';
    }
    if (internalWidget.type === 'bar' && internalWidget.aggregationMethod === undefined) {
      internalWidget.aggregationMethod = 'sum';
    }

    // Set default category field for categorical bar charts
    if (internalWidget.type === 'bar' && internalWidget.chartMode === 'categorical' && internalWidget.categoryField === undefined) {
      internalWidget.categoryField = 'date';
    }

    // Set default range method for pie/doughnut charts
    if (['pie', 'doughnut'].includes(internalWidget.type) && internalWidget.rangeMethod === undefined) {
      internalWidget.rangeMethod = 'target-based';
    }

    // Set default datalabels display for pie/doughnut charts
    if (['pie', 'doughnut'].includes(internalWidget.type) && internalWidget.datalabelsDisplay === undefined) {
      internalWidget.datalabelsDisplay = 'count-percentage';
    }

    // Set default custom ranges based on KPI target value
    if (['pie', 'doughnut'].includes(internalWidget.type) && internalWidget.rangeMethod === 'custom' && !internalWidget.customRanges) {
      const selectedKpi = kpis.find(kpi => kpi.id === internalWidget.kpi_id);
      const targetValue = selectedKpi && selectedKpi.target ? Number(selectedKpi.target) : 500;
      
      internalWidget.customRanges = {
        low: Math.floor(targetValue * 0.2),      // 20% of target
        medium: Math.floor(targetValue * 0.6),   // 60% of target
        high: targetValue                        // Target value
      };
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)] z-50 flex items-center justify-center" on:click={closeModal} role="presentation">
    <div bind:this={dialogElement} class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto" on:click|stopPropagation role="dialog" aria-modal="true" tabindex="-1">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Widget Settings</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <form on:submit|preventDefault={handleSubmit}>
        <div class="space-y-6">
          <!-- Basic Settings Row -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label for="widget-title" class="block text-sm font-medium text-gray-700">Title</label>
              <input type="text" id="widget-title" bind:value={internalWidget.title} required class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
              <label for="widget-type" class="block text-sm font-medium text-gray-700">Widget Type</label>
              <select id="widget-type" bind:value={internalWidget.type} class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
            <div>
              <label for="widget-kpi" class="block text-sm font-medium text-gray-700">Data Source (KPI)</label>
              <select id="widget-kpi" bind:value={internalWidget.kpi_id} class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
          </div>

            
              {#if internalWidget.type === 'single-value'}
                <div>
                  <label for="widget-aggregation" class="block text-sm font-medium text-gray-700">Value to Display</label>
                  <select id="widget-aggregation" bind:value={internalWidget.aggregation} class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="latest">Most Recent Value</option>
                    <option value="sum">Sum of Values</option>
                    <option value="average">Average of Values</option>
                  </select>
                </div>
              {/if}

          <!-- Time Series Settings for Line Charts -->
          {#if internalWidget.type === 'line'}
            <div class="">
              <h4 class="text-sm font-medium text-blue-900 mb-4">Time Series Settings</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="widget-time-unit" class="block text-sm font-medium text-gray-700">Time Unit</label>
                  <select 
                    id="widget-time-unit" 
                    bind:value={internalWidget.timeUnit} 
                    class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="day">Daily</option>
                    <option value="week">Weekly</option>
                    <option value="month">Monthly</option>
                    <option value="year">Yearly</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">How to group data points</p>
                </div>
                
                <div>
                  <label for="widget-aggregation-method" class="block text-sm font-medium text-gray-700">Aggregation Method</label>
                  <select 
                    id="widget-aggregation-method" 
                    bind:value={internalWidget.aggregationMethod} 
                    class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="average">Average</option>
                    <option value="sum">Sum</option>
                    <option value="max">Maximum</option>
                    <option value="min">Minimum</option>
                    <option value="latest">Latest Value</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">How to combine entries</p>
                </div>
              </div>
              
              <!-- <div class="mt-3 p-2 bg-blue-100 rounded text-xs text-blue-800">
                <strong>Gap Detection:</strong> Automatically detects gaps larger than 1 week (daily), 2 weeks (weekly), 2 months (monthly), or 1 year (yearly) and creates visual breaks in the line.
              </div> -->
            </div>
          {/if}

          <!-- Bar Chart Settings -->
          {#if internalWidget.type === 'bar'}
            <div class="">
              <h4 class="text-sm font-medium text-blue-900 mb-4">Bar Chart Settings</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="widget-chart-mode" class="block text-sm font-medium text-gray-700">Chart Mode</label>
                  <select 
                    id="widget-chart-mode" 
                    bind:value={internalWidget.chartMode} 
                    class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="time-based">Time-based</option>
                    <option value="categorical">Categorical</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">How to group and display data</p>
                </div>
                
                {#if internalWidget.chartMode === 'time-based'}
                  <div>
                    <label for="widget-time-unit-bar" class="block text-sm font-medium text-gray-700">Time Unit</label>
                    <select 
                      id="widget-time-unit-bar" 
                      bind:value={internalWidget.timeUnit} 
                      class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option value="day">Daily</option>
                      <option value="week">Weekly</option>
                      <option value="month">Monthly</option>
                      <option value="year">Yearly</option>
                    </select>
                  </div>
                {/if}
              </div>
              
              {#if internalWidget.chartMode === 'time-based'}
                <div class="mt-4">
                  <label for="widget-aggregation-method-bar" class="block text-sm font-medium text-gray-700">Aggregation Method</label>
                  <select 
                    id="widget-aggregation-method-bar" 
                    bind:value={internalWidget.aggregationMethod} 
                    class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="sum">Sum</option>
                    <option value="average">Average</option>
                    <option value="max">Maximum</option>
                    <option value="min">Minimum</option>
                    <option value="latest">Latest Value</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">How to combine entries within each time period</p>
                </div>
              {/if}
              
              {#if internalWidget.chartMode === 'categorical'}
                <div class="mt-4">
                  <label for="widget-category-field" class="block text-sm font-medium text-gray-700">Category Field</label>
                  <select 
                    id="widget-category-field" 
                    bind:value={internalWidget.categoryField} 
                    class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="date">Date</option>
                    <option value="value">Value Range</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">How to group data into categories</p>
                </div>
              {/if}
            </div>
          {/if}

          <!-- Pie/Doughnut Chart Settings -->
          {#if ['pie', 'doughnut'].includes(internalWidget.type)}
            <div class="">
              <h4 class="text-sm font-medium text-blue-900 mb-4">Pie/Doughnut Chart Settings</h4>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="widget-range-method" class="block text-sm font-medium text-gray-700">Range Method</label>
                  <select 
                    id="widget-range-method" 
                    bind:value={internalWidget.rangeMethod} 
                    class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="target-based">Target-based</option>
                    <option value="quartile-based">Quartile-based</option>
                    <option value="custom">Custom Ranges</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">How to define value ranges</p>
                </div>
                <div>
                  <label for="widget-datalabels-display" class="block text-sm font-medium text-gray-700">Data Labels Display</label>
                  <select 
                    id="widget-datalabels-display" 
                    bind:value={internalWidget.datalabelsDisplay} 
                    class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  >
                    <option value="count-percentage">Count & Percentage</option>
                    <option value="count-only">Count Only</option>
                    <option value="percentage-only">Percentage Only</option>
                    <option value="value-percentage">Value & Percentage</option>
                    <option value="value-only">Value Only</option>
                    <option value="none">None</option>
                  </select>
                  <p class="mt-1 text-xs text-gray-500">What to show on chart segments</p>
                </div>
              </div>
              
              {#if internalWidget.rangeMethod === 'custom'}
                <div class="mt-4">
                  <h5 class="text-sm font-medium text-gray-700 mb-2">Custom Ranges</h5>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label for="custom-low-range" class="block text-xs text-gray-600">Low Range</label>
                      <input id="custom-low-range" type="number" bind:value={internalWidget.customRanges.low} placeholder="20% of target" class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                      <label for="custom-medium-range" class="block text-xs text-gray-600">Medium Range</label>
                      <input id="custom-medium-range" type="number" bind:value={internalWidget.customRanges.medium} placeholder="60% of target" class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                      <label for="custom-high-range" class="block text-xs text-gray-600">High Range</label>
                      <input id="custom-high-range" type="number" bind:value={internalWidget.customRanges.high} placeholder="Target value" class="mt-1 py-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">Define custom value thresholds</p>
                </div>
              {/if}
            </div>
          {/if}

          <!-- Colors and Date Range Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="text-sm font-medium text-blue-900 mb-3">Colors</h4>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="widget-bg-color" class="block text-sm font-medium text-gray-700">Shape Color</label>
                  <input type="color" id="widget-bg-color" bind:value={internalWidget.backgroundColor} class="mt-1 block w-full h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                  <label for="widget-border-color" class="block text-sm font-medium text-gray-700">Line Color</label>
                  <input type="color" id="widget-border-color" bind:value={internalWidget.borderColor} class="mt-1 block w-full h-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
              </div>

              {#if ['line', 'bar', 'pie', 'doughnut'].includes(internalWidget.type)}
                <div class=" mt-2 flex items-center justify-between">
                  <div class="flex items-center py-3">
                    <label for="widget-show-legend" class="text-sm font-medium text-gray-900">Show Legend: </label>
                    <input id="widget-show-legend" type="checkbox" bind:checked={internalWidget.showLegend} class="h-4 w-4 ml-2 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                  </div>
                  {#if internalWidget.showLegend}
                    <div class="flex items-center">
                      <label for="widget-legend-position" class="block text-sm font-medium text-gray-700">Legend Position: </label>
                      <select id="widget-legend-position" bind:value={internalWidget.legendPosition} class="block ml-2 pl-3 pr-10 py-2 text-base border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="top">Top</option>
                        <option value="bottom">Bottom</option>
                        <option value="left">Left</option>
                        <option value="right">Right</option>
                      </select>
                    </div>
                  {/if}
                </div>
              {/if}
            </div>
            
            <div>
              <h4 class="text-sm font-medium text-blue-900  mb-3">Date Range</h4>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="widget-start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
                  <input type="date" id="widget-start-date" bind:value={internalWidget.startDate} class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                  <label for="widget-end-date" class="block text-sm font-medium text-gray-700">End Date</label>
                  <input type="date" id="widget-end-date" bind:value={internalWidget.endDate} class="mt-1 py-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
              </div>
              
              <div class="mt-3">
                <div class="grid grid-cols-2 gap-2">
                  <button type="button" on:click={() => setDateRange('7d')} class="text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">Last 7 Days</button>
                  <button type="button" on:click={() => setDateRange('30d')} class="text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">Last 30 Days</button>
                  <button type="button" on:click={() => setDateRange('month')} class="text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">This Month</button>
                  <button type="button" on:click={() => setDateRange('ytd')} class="text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100">Year to Date</button>
                </div>
                <div class="mt-2">
                  <button type="button" on:click={() => setDateRange('all')} class="w-full text-xs py-1 px-2 border border-gray-300 rounded-md hover:bg-gray-100 font-medium">All Time</button>
                </div>
              </div>
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
