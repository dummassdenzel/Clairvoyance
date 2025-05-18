<script lang="ts">
  import { onMount } from 'svelte';
  import { getWidgetData } from '$lib/services/widget';
  import type { Widget } from '$lib/services/dashboard';
  
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

<div class="widget" style="grid-column: span {widget.width}; grid-row: span {widget.height};">
  <div class="widget-header">
    <h3>{widget.title}</h3>
    <div class="widget-actions">
      <button class="btn-icon" on:click={handleEdit} title="Edit widget">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
      </button>
      <button class="btn-icon" on:click={handleDelete} title="Delete widget">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
      </button>
    </div>
  </div>
  
  <div class="widget-content">
    {#if isLoading}
      <div class="loading-spinner">Loading...</div>
    {:else if error}
      <div class="error-message">
        <p>{error}</p>
        <button on:click={fetchWidgetData} class="btn-retry">Retry</button>
      </div>
    {:else}
      {#if widget.widget_type === 'card'}
        <div class="metric-card">
          <div class="metric-value">{widgetData?.value || '0'}</div>
          <div class="metric-label">{widget.kpi_name} ({widget.kpi_unit})</div>
        </div>
      {:else if widget.widget_type === 'line' || widget.widget_type === 'bar'}
        <!-- Chart visualization would go here -->
        <div class="chart-placeholder">
          <p>Chart visualization for {widget.kpi_name}</p>
          <p>Type: {widget.widget_type}</p>
        </div>
      {:else}
        <div class="unknown-widget">
          <p>Unknown widget type: {widget.widget_type}</p>
        </div>
      {/if}
    {/if}
  </div>
</div>

<style>
  .widget {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  
  .widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
  }
  
  .widget-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #333;
  }
  
  .widget-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    color: #6c757d;
    padding: 0.25rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s, background-color 0.2s;
  }
  
  .btn-icon:hover {
    color: #495057;
    background-color: #e9ecef;
  }
  
  .widget-content {
    padding: 1rem;
    flex-grow: 1;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .loading-spinner {
    color: #6c757d;
    text-align: center;
  }
  
  .error-message {
    color: #dc3545;
    text-align: center;
  }
  
  .btn-retry {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    margin-top: 0.5rem;
  }
  
  .btn-retry:hover {
    background-color: #5a6268;
  }
  
  .metric-card {
    text-align: center;
    width: 100%;
  }
  
  .metric-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 0.5rem;
  }
  
  .metric-label {
    font-size: 0.875rem;
    color: #6c757d;
  }
  
  .chart-placeholder {
    width: 100%;
    height: 100%;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 4px;
    color: #6c757d;
    text-align: center;
    padding: 1rem;
  }
  
  .chart-placeholder p {
    margin: 0.25rem 0;
  }
  
  .unknown-widget {
    width: 100%;
    text-align: center;
    color: #dc3545;
  }
</style> 