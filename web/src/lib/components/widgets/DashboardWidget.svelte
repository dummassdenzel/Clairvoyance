<script lang="ts">
  import { onMount } from 'svelte';
  import { getWidgetData } from '$lib/stores/widget';
  import type { Widget } from '$lib/stores/dashboard';
  
  export let widget: Widget;
  export let dashboardId: number;
  export let onEdit: (widget: Widget) => void;
  export let onDelete: (widget: Widget) => void;
  
  let isLoading = true;
  let error: string | null = null;
  let widgetData: any = null;
  
  onMount(async () => {
    await fetchWidgetData();
  });
  
  async function fetchWidgetData() {
    isLoading = true;
    error = null;
    
    try {
      const response = await getWidgetData(dashboardId, widget.id);
      
      if (response.success) {
        widgetData = response.data;
      } else {
        error = response.message || 'Failed to load widget data';
      }
    } catch (err) {
      console.error('Error fetching widget data:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
  
  function handleEdit() {
    onEdit(widget);
  }
  
  function handleDelete() {
    onDelete(widget);
  }
</script>

<div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col col-span-[{widget.width}] row-span-[{widget.height}]">
  <div class="flex justify-between items-center p-3 bg-gray-50 border-b border-gray-200">
    <h3 class="text-base font-semibold text-gray-800">{widget.title}</h3>
    <div class="flex gap-2">
      <button class="p-1 rounded text-gray-500 hover:text-gray-700 hover:bg-gray-200 transition-colors duration-200 flex items-center justify-center" on:click={handleEdit} title="Edit widget">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
      </button>
      <button class="p-1 rounded text-gray-500 hover:text-red-600 hover:bg-red-100 transition-colors duration-200 flex items-center justify-center" on:click={handleDelete} title="Delete widget">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
      </button>
    </div>
  </div>
  
  <div class="p-4 flex-grow flex items-center justify-center">
    {#if isLoading}
      <div class="text-gray-500 text-center">Loading...</div>
    {:else if error}
      <div class="text-red-600 text-center">
        <p>{error}</p>
        <button 
          on:click={fetchWidgetData} 
          class="mt-2 px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600 transition-colors"
        >
          Retry
        </button>
      </div>
    {:else}
      {#if widget.widget_type === 'card'}
        <div class="text-center w-full">
          <div class="text-4xl font-bold text-blue-600 mb-2">{widgetData?.value || '0'}</div>
          <div class="text-sm text-gray-600">{widget.kpi_name} ({widget.kpi_unit})</div>
        </div>
      {:else if widget.widget_type === 'line' || widget.widget_type === 'bar'}
        <div class="w-full h-full min-h-[120px] flex flex-col items-center justify-center text-gray-500 border border-dashed border-gray-300 rounded-md p-4">
          <p>Chart: {widget.kpi_name}</p>
          <p class="text-xs">Type: {widget.widget_type}</p>
          <p class="text-xs mt-2">(Chart visualization not yet implemented)</p>
        </div>
      {:else}
        <div class="text-center text-gray-500">
          <p>Unknown widget type: {widget.widget_type}</p>
        </div>
      {/if}
    {/if}
  </div>
</div>