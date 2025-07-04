<script lang="ts">
  import { onMount, onDestroy, getContext } from 'svelte';
  import Chart from 'chart.js/auto';
  import * as api from '$lib/services/api';

  export let widget: any;
  export let editMode = false;
  export let movePointerDown: (e: PointerEvent) => void;

  const { openWidgetSettings }: any = getContext('dashboard-layout');

  let chartInstance: Chart | null = null;
  let kpiData: { labels: string[]; values: number[] } | null = null;
  let aggregateValue: number | null = null;
  let isLoading = true;
  let error: string | null = null;
  let canvasElement: HTMLCanvasElement;

  async function fetchAggregateValue(kpiId: number, aggregation: string, startDate?: string, endDate?: string) {
    if (!kpiId || !aggregation) {
      aggregateValue = null;
      return;
    }
    isLoading = true;
    error = null;
    try {
      const response = await api.getAggregateKpiValue(kpiId, aggregation, startDate, endDate);
      if (response && response.data && response.data.value !== null) {
        aggregateValue = Number(response.data.value);
      } else {
        aggregateValue = null;
        error = 'No data available.';
      }
    } catch (e) {
      aggregateValue = null;
      console.error(`Failed to load aggregate KPI value for widget ${kpiId}:`, e);
      error = 'Failed to load data.';
    } finally {
      isLoading = false;
    }
  }

  async function fetchKpiData(kpiId: number, startDate?: string, endDate?: string) {
    if (!kpiId) {
      kpiData = null;
      error = 'No KPI selected.';
      isLoading = false;
      return;
    }

    isLoading = true;
    error = null;
    try {
      const response = await api.getKpiEntries(kpiId, startDate, endDate);
      if (response && response.data && Array.isArray(response.data)) {
        const entries = response.data;
        kpiData = {
          labels: entries.map((d: any) => d.date),
          values: entries.map((d: any) => Number(d.value))
        };
      } else {
        kpiData = null;
        error = 'No data available for this KPI.';
      }
    } catch (e) {
      kpiData = null;
      console.error(`Failed to load KPI data for widget ${kpiId}:`, e);
      error = 'Failed to load KPI data.';
    } finally {
      isLoading = false;
    }
  }

  // When data-related properties change, re-fetch the data
  $: {
    if (widget.type === 'single-value') {
      fetchAggregateValue(widget.kpi_id, widget.aggregation, widget.startDate, widget.endDate);
    } else {
      fetchKpiData(widget.kpi_id, widget.startDate, widget.endDate);
    }
  }

  function renderChart(canvas: HTMLCanvasElement) {
    if (!canvas || !kpiData) return;

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
        plugins: {
          legend: { 
            display: widget.showLegend 
          } 
        } 
      }
    });
  }

  onDestroy(() => {
    console.log(`Widget ${widget.id} destroyed.`);
    if (chartInstance) {
      chartInstance.destroy();
    }
  });

  // Reactive block to handle chart rendering and updates
  $: if (canvasElement && kpiData) {
    if (chartInstance) {
      chartInstance.destroy();
      chartInstance = null;
    }
    
    // Render chart if the type is one of the supported visual types
    if (['line', 'bar', 'pie', 'doughnut'].includes(widget.type)) {
      renderChart(canvasElement);
    }
  }
</script>

<div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col h-full">
  <div class="p-4 border-b border-gray-200 flex justify-between items-center">
    <h3 class="text-lg font-semibold text-gray-800">{widget.title || widget.type}</h3>
    {#if editMode}
      <button on:click|stopPropagation={() => openWidgetSettings(widget)} on:mousedown|stopPropagation aria-label="Widget settings" class="p-1 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>
    {/if}
  </div>
  <div on:pointerdown={editMode ? movePointerDown : undefined} class:cursor-move={editMode} class="flex-grow p-4 min-h-0">
    {#if isLoading}
      <div class="flex items-center justify-center h-full">
        <p>Loading...</p>
      </div>
    {:else if error}
      <div class="flex items-center justify-center h-full">
        <p class="text-red-500">{error}</p>
      </div>
    {:else if ['line', 'bar', 'pie', 'doughnut'].includes(widget.type)}
      <div class="w-full h-full relative flex items-center justify-center">
        <canvas bind:this={canvasElement}></canvas>
      </div>
    {:else if widget.type === 'single-value'}
      <div class="flex flex-col items-center justify-center h-full text-center">
        <h3 class="text-4xl font-bold">{aggregateValue !== null ? aggregateValue.toLocaleString() : 'N/A'}</h3>
        <p class="text-sm text-gray-500">{widget.aggregation.charAt(0).toUpperCase() + widget.aggregation.slice(1)} Value</p>
      </div>
    {:else}
      <div class="flex items-center justify-center h-full">
        <p class="text-sm text-gray-500">Unsupported widget type: {widget.type}</p>
      </div>
    {/if}
  </div>
</div>
