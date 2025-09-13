<script lang="ts">
  import { onMount, onDestroy, createEventDispatcher } from 'svelte';
  import Chart from 'chart.js/auto';
  import annotationPlugin from 'chartjs-plugin-annotation';
  import * as api from '$lib/services/api';
  import type { Kpi, ApiResponse, KpiEntry } from '$lib/types';

  Chart.register(annotationPlugin);

  export let widget: any;
  export let editMode: boolean = false;
  export let movePointerDown: (e: PointerEvent) => void = () => {};

  const dispatch = createEventDispatcher();

  let isConfirmingDelete = false;

  let chartInstance: Chart | null = null;
  let kpiData: { labels: string[]; values: number[] } | null = null;
  let kpiDetails: Kpi | null = null;
  let aggregateValue: number | null = null;
  let isLoading = true;
  let error: string | null = null;
  let canvasElement: HTMLCanvasElement;

  async function loadWidgetData() {
    if (!widget.kpi_id) {
      error = 'No KPI selected for this widget.';
      isLoading = false;
      return;
    }

    isLoading = true;
    error = null;

    try {
      const detailsPromise = api.getKpi(widget.kpi_id);
      let dataPromise;

      if (widget.type === 'single-value') {
        dataPromise = api.getAggregateKpiValue(widget.kpi_id, widget.aggregation || 'latest', widget.startDate, widget.endDate);
      } else {
        dataPromise = api.getKpiEntries(widget.kpi_id, widget.startDate, widget.endDate);
      }

      const [detailsResult, dataResult] = await Promise.all([detailsPromise, dataPromise]);

      if (detailsResult.success) {
        kpiDetails = detailsResult.data?.kpi || null;
      } else {
        throw new Error(detailsResult.message || 'Failed to load KPI details');
      }

      if (dataResult.success && dataResult.data) {
        if (widget.type === 'single-value') {
          // For single-value widgets, dataResult.data should have a 'value' property
          const valueData = dataResult.data as { value: number };
          aggregateValue = valueData.value !== null ? Number(valueData.value) : null;
          kpiData = null;
        } else {
          // For chart widgets, handle different possible data structures
          let entries: KpiEntry[] = [];
          
          // Type guard to check if data is an array
          if (Array.isArray(dataResult.data)) {
            entries = dataResult.data as KpiEntry[];
          } else {
            // Type guard to check if data has entries property
            const dataWithEntries = dataResult.data as { entries?: KpiEntry[] };
            if (dataWithEntries.entries && Array.isArray(dataWithEntries.entries)) {
              entries = dataWithEntries.entries;
            } else {
              console.error('Unexpected data structure:', dataResult.data);
              throw new Error('Invalid data structure received');
            }
          }

          // Check if we have any entries
          if (entries.length === 0) {
            error = 'No data available for this KPI.';
            kpiData = null;
          } else {
            kpiData = {
              labels: entries.map((d: KpiEntry) => d.date),
              values: entries.map((d: KpiEntry) => Number(d.value))
            };
            console.log(`Widget ${widget.id} loaded data:`, kpiData);
          }
          aggregateValue = null;
        }
      } else {
        throw new Error(dataResult.message || 'Failed to load widget data');
      }
    } catch (e) {
      console.error(`Failed to load data for widget ${widget.id}:`, e);
      error = e instanceof Error ? e.message : 'Unknown error occurred';
      kpiData = null;
      kpiDetails = null;
      aggregateValue = null;
    } finally {
      isLoading = false;
    }
  }

  async function loadKpiDetails() {
    if (!widget.kpi_id || kpiDetails) return kpiDetails;

    try {
      console.log(`Widget ${widget.id}: Loading KPI details on demand`);
      const response: ApiResponse<{ kpi: Kpi }> = await api.getKpi(widget.kpi_id);
      if (response.success && response.data?.kpi) {
        kpiDetails = response.data.kpi;
        console.log(`Widget ${widget.id}: KPI details loaded`, kpiDetails);
        return kpiDetails;
      } else {
        console.error(`Widget ${widget.id}: Failed to load KPI details`, response.message);
        return null;
      }
    } catch (e) {
      console.error(`Widget ${widget.id}: Error loading KPI details`, e);
      return null;
    }
  }

  function getRagClass(value: number | null, details: Kpi | null): string {
    if (value === null || !details) return 'text-gray-900';

    const { direction, rag_red, rag_amber } = details;
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

  async function handleWidgetClick() {
    console.log(`Widget ${widget.id} clicked!`, {
      editMode,
      kpiId: widget.kpi_id,
      kpiDetails: !!kpiDetails,
      canClick: !editMode && widget.kpi_id && kpiDetails
    });

    // Only handle clicks in view mode (not edit mode) and when we have a KPI ID
    if (!editMode && widget.kpi_id) {
      // Load KPI details if not already loaded
      let details = kpiDetails;
      if (!details) {
        console.log(`Widget ${widget.id}: KPI details not loaded, loading on demand`);
        details = await loadKpiDetails();
      }

      if (details) {
        console.log(`Widget ${widget.id}: Dispatching viewEntries event`, {
          kpi: details,
          kpiId: widget.kpi_id
        });
        dispatch('viewEntries', { 
          kpi: details, 
          kpiId: widget.kpi_id 
        });
      } else {
        console.error(`Widget ${widget.id}: Failed to load KPI details for modal`);
      }
    } else {
      console.log(`Widget ${widget.id}: Click ignored - conditions not met`, {
        editMode,
        hasKpiId: !!widget.kpi_id,
        hasKpiDetails: !!kpiDetails
      });
    }
  }

  // When data-related properties change, re-fetch all data
  $: if (widget.kpi_id || widget.startDate || widget.endDate || widget.type || widget.aggregation) {
    loadWidgetData();
  }

  function renderChart(canvas: HTMLCanvasElement) {
    if (!canvas || !kpiData) {
      console.log(`Widget ${widget.id}: Cannot render chart - canvas: ${!!canvas}, kpiData: ${!!kpiData}`);
      return;
    }

    try {
      console.log(`Widget ${widget.id}: Rendering chart with data:`, kpiData);

      const chartPlugins: any = {
        legend: { 
          display: widget.showLegend !== false, // Default to true if not specified
          position: widget.legendPosition || 'top'
        },
        autocolors: false
      };

      // Only add goal line for line/bar charts and if a valid target exists
      if (['line', 'bar'].includes(widget.type)) {
        const targetValue = kpiDetails ? Number(kpiDetails.target) : null;
        if (targetValue !== null && !isNaN(targetValue)) {
          chartPlugins.annotation = {
            annotations: {
              goalLine: {
                type: 'line',
                yMin: targetValue,
                yMax: targetValue,
                borderColor: 'red',
                borderWidth: 2,
                borderDash: [6, 6],
                label: {
                  content: 'Target',
                  enabled: true,
                  position: 'end'
                }
              }
            }
          };
        }
      }

      chartInstance = new Chart(canvas, {
        type: widget.type,
        data: {
          labels: kpiData.labels,
          datasets: [{
            label: widget.title || `KPI ${widget.kpi_id}`,
            data: kpiData.values,
            backgroundColor: widget.backgroundColor || 'rgba(54, 162, 235, 0.2)',
            borderColor: widget.borderColor || 'rgba(54, 162, 235, 1)',
            borderWidth: 2
          }]
        },
        options: { 
          responsive: true, 
          maintainAspectRatio: ['pie', 'doughnut'].includes(widget.type),
          plugins: chartPlugins,
          // Add some basic options to prevent rendering issues
          scales: ['pie', 'doughnut'].includes(widget.type) ? {} : {
            x: {
              display: true
            },
            y: {
              display: true
            }
          }
        }
      });

      console.log(`Widget ${widget.id}: Chart rendered successfully`);
    } catch (chartError) {
      console.error(`Widget ${widget.id}: Chart rendering failed:`, chartError);
      error = `Chart rendering failed: ${chartError instanceof Error ? chartError.message : 'Unknown error'}`;
    }
  }

  onDestroy(() => {
    console.log(`Widget ${widget.id} destroyed.`);
    if (chartInstance) {
      chartInstance.destroy();
    }
  });

  // Reactive block to handle chart rendering and updates
  $: if (canvasElement) {
    const isChart = ['line', 'bar', 'pie', 'doughnut'].includes(widget.type);

    if (chartInstance) {
      chartInstance.destroy();
      chartInstance = null;
    }

    console.log(`Widget ${widget.id}: Canvas element ready, isChart: ${isChart}, kpiData: ${!!kpiData}, kpiDetails: ${!!kpiDetails}`);

    // For charts, render when we have data
    if (isChart && kpiData) {
      // For line/bar charts, we prefer to have both data and details (for goal line)
      // but we'll render even without details
      if (['line', 'bar'].includes(widget.type)) {
        if (kpiDetails) {
          renderChart(canvasElement);
        } else {
          // Render without goal line if details aren't available yet
          console.log(`Widget ${widget.id}: Rendering line/bar chart without goal line (no details yet)`);
          renderChart(canvasElement);
        }
      } else if (['pie', 'doughnut'].includes(widget.type)) {
        // Pie/Doughnut charts don't need details
        renderChart(canvasElement);
      }
    }
  }

  // Debug logging for widget state changes
  $: console.log(`Widget ${widget.id} state:`, {
    editMode,
    kpiId: widget.kpi_id,
    kpiDetails: !!kpiDetails,
    isLoading,
    error: !!error,
    canClick: !editMode && widget.kpi_id
  });
</script>

<div class="bg-white rounded-lg shadow-md h-full w-full flex flex-col">
  <div class="p-2 border-b border-gray-200 flex justify-between items-center" on:pointerdown={editMode ? movePointerDown : undefined} class:cursor-move={editMode}>
    <h3 class="font-semibold text-sm text-gray-700 truncate">{widget.title}</h3>
    {#if editMode}
      <div class="flex items-center space-x-1">
        {#if isConfirmingDelete}
          <span class="text-xs text-gray-600">Sure?</span>
          <button 
            on:click|stopPropagation={() => { dispatch('remove', { id: widget.id }); isConfirmingDelete = false; }} 
            class="px-2 py-1 text-xs text-white bg-red-600 rounded hover:bg-red-700" 
            aria-label="Confirm Remove"
          >
            Yes
          </button>
          <button 
            on:click|stopPropagation={() => isConfirmingDelete = false} 
            class="px-2 py-1 text-xs text-gray-700 bg-gray-200 rounded hover:bg-gray-300" 
            aria-label="Cancel Remove"
          >
            No
          </button>
        {:else}
          <button 
            on:click={() => dispatch('openSettings', { widget })} 
            class="p-1 text-gray-400 hover:text-gray-700" 
            aria-label="Widget Settings"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>
          <button 
            on:click|stopPropagation={() => isConfirmingDelete = true} 
            class="p-1 text-red-400 hover:text-red-700" 
            aria-label="Remove Widget"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        {/if}
      </div>
    {/if}
  </div>
  <div 
    on:pointerdown={editMode ? movePointerDown : undefined} 
    on:click={handleWidgetClick}
    class:cursor-move={editMode}
    class:cursor-pointer={!editMode && widget.kpi_id}
    class="flex-grow p-4 min-h-0"
  >
    {#if isLoading}
      <div class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-500">Loading...</p>
        </div>
      </div>
    {:else if error}
      <div class="flex items-center justify-center h-full">
        <div class="text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <p class="mt-2 text-sm text-gray-400 ">{error}</p>
          {#if error.includes('No data available')}
            <p class="mt-1 text-xs text-gray-400">Add KPI entries to see the chart</p>
          {/if}
        </div>
      </div>
    {:else if ['line', 'bar', 'pie', 'doughnut'].includes(widget.type)}
      <div class="w-full h-full relative flex items-center justify-center">
        <canvas bind:this={canvasElement}></canvas>
      </div>
    {:else if widget.type === 'single-value'}
      <div class="flex flex-col items-center justify-center h-full text-center">
        <h3 class="text-4xl font-bold {getRagClass(aggregateValue, kpiDetails)}">
          {kpiDetails?.format_prefix || ''}{aggregateValue !== null ? aggregateValue.toLocaleString() : 'N/A'}{kpiDetails?.format_suffix || ''}
        </h3>
        <p class="text-sm text-gray-500">{widget.aggregation.charAt(0).toUpperCase() + widget.aggregation.slice(1)} Value</p>
        {#if !editMode && widget.kpi_id}
          <p class="text-xs text-gray-400 mt-2">Click to view entries</p>
        {/if}
      </div>
    {:else}
      <div class="flex items-center justify-center h-full">
        <p class="text-sm text-gray-500">Unsupported widget type: {widget.type}</p>
      </div>
    {/if}
  </div>
</div>
