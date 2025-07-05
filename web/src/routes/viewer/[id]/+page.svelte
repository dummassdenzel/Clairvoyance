<script lang="ts">
  import type { PageData } from './$types';
  import { onMount } from 'svelte';
  import { Chart } from 'chart.js/auto';

  export let data: PageData;
  const { dashboard } = data;

  let chartCanvas: HTMLCanvasElement;

  onMount(() => {
    if (chartCanvas) {
      new Chart(chartCanvas, {
        type: 'bar',
        data: {
          labels: ['January', 'February', 'March', 'April', 'May', 'June'],
          datasets: [{
            label: 'Sample Data',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }
  });
</script>

<div class="max-w-7xl mx-auto">
  <div class="mb-6">
    <a href="/viewer" class="text-blue-600 hover:underline text-sm">&larr; Back to Dashboards</a>
    <h1 class="text-3xl font-bold text-gray-900 mt-2">{dashboard.name}</h1>
    <p class="mt-1 text-sm text-gray-600">{dashboard.description}</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">Sample Chart</h2>
      <canvas bind:this={chartCanvas}></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">Another Widget</h2>
      <p class="text-gray-600">This is a placeholder for another dashboard widget.</p>
    </div>
  </div>
</div>
