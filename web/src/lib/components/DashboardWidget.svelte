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
  let aggregateData: { value: number } | null = null;
  let isLoading = true;
  let error: string | null = null;
  let canvasElement: HTMLCanvasElement;

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

  async function fetchAggregateData(kpiId: number, aggregation: string, startDate?: string, endDate?: string) {
    if (!kpiId) {
      aggregateData = null;
      error = 'No KPI selected.';
      isLoading = false;
      return;
    }

    isLoading = true;
    error = null;
    try {
      const response = await api.getKpiAggregate(kpiId, aggregation, startDate, endDate);
      if (response && response.data) {
        aggregateData = response.data;
      } else {
        aggregateData = null;
        error = 'No data available for this KPI.';
      }
    } catch (e) {
      aggregateData = null;
      console.error(`Failed to load aggregate KPI data for widget ${kpiId}:`, e);
      error = 'Failed to load KPI data.';
    } finally {
      isLoading = false;
    }
  }

  // When data-related properties change, re-fetch the appropriate data
  $: {
    if (widget.type === 'single-value') {
      fetchAggregateData(widget.kpi_id, widget.aggregation, widget.startDate, widget.endDate);
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
        maintainAspectRatio: false,
        plugins: { 
          legend: { display: true } 
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
  <div on:pointerdown={movePointerDown} class="p-4 flex-grow relative flex items-center justify-center">
    {#if isLoading}
      <div class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
        <p>Loading...</p>
      </div>
    {:else if error}
      <p class="text-red-500">{error}</p>
    {:else if widget.type === 'single-value'}
      {#if aggregateData !== null && aggregateData.value !== null}
        <div class="text-center">
          <h3 class="text-lg text-gray-500">{widget.aggregation.charAt(0).toUpperCase() + widget.aggregation.slice(1)}</h3>
          <p class="text-5xl font-bold">{Number(aggregateData.value).toLocaleString()}</p>
        </div>
      {:else}
        <p>No data</p>
      {/if}
    {:else if ['line', 'bar', 'pie', 'doughnut'].includes(widget.type)}
      <div class="w-full h-full">
        <canvas bind:this={canvasElement}></canvas>
      </div>
    {:else if widget.type === 'table'}
      <p class="text-sm text-gray-500">Table widget coming soon.</p>
    {:else if widget.type === 'number'}
      <p class="text-4xl font-bold text-gray-900 text-center pt-10">{kpiData?.values[0] || 'N/A'}</p>
    {:else}
      <p class="text-sm text-gray-500">Unsupported widget type: {widget.type}</p>
    {/if}
  </div>
</div>
