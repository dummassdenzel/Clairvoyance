<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import Chart from 'chart.js/auto';
  import * as api from '$lib/services/api';

  export let widget: any;

  let chartInstance: Chart | null = null;
  let kpiData: { labels: string[]; values: number[] } | null = null;
  let isLoading = true;
  let error: string | null = null;

  onMount(async () => {
    if (widget.kpi_id) {
      try {
        const response = await api.getKpiEntries(widget.kpi_id);
        if (response && response.data && Array.isArray(response.data)) {
          const entries = response.data;
          kpiData = {
            labels: entries.map((d: any) => d.date),
            values: entries.map((d: any) => Number(d.value))
          };
          if (widget.type === 'bar' || widget.type === 'line') {
            renderChart();
          }
        } else {
          error = 'No data available for this KPI.';
        }
      } catch (e) {
        console.error(`Failed to load KPI data for widget ${widget.kpi_id}:`, e);
        error = 'Failed to load KPI data.';
      } finally {
        isLoading = false;
      }
    }
  });

  function renderChart() {
    const canvas = document.getElementById(`widget-chart-${widget.id}`) as HTMLCanvasElement;
    if (!canvas || !kpiData) return;

    chartInstance = new Chart(canvas, {
      type: widget.type,
      data: {
        labels: kpiData.labels,
        datasets: [{
          label: widget.title || `KPI ${widget.kpi_id}`,
          data: kpiData.values,
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          borderColor: 'rgba(54, 162, 235, 1)',
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
    if (chartInstance) {
      chartInstance.destroy();
    }
  });
</script>

<div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col h-full">
  <div class="p-4 border-b border-gray-200">
    <h3 class="text-lg font-semibold text-gray-800">{widget.title || widget.type}</h3>
  </div>
  <div class="p-4 flex-grow">
    {#if isLoading}
      <div class="text-center py-10">Loading...</div>
    {:else if error}
      <div class="text-center py-10 text-red-500">{error}</div>
    {:else if widget.type === 'bar' || widget.type === 'line'}
      <div class="relative h-64">
        <canvas id={`widget-chart-${widget.id}`}></canvas>
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
