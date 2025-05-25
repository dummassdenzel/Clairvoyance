<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { createWidget } from '$lib/services/widget';
  import { fetchKPIs, kpis } from '$lib/services/kpi';
  import { authStore } from '$lib/services/auth';
  import { goto } from '$app/navigation';
  
  // Get dashboard ID from URL params
  const dashboardId = parseInt($page.params.id);
  
  // Define the allowed widget types
  type WidgetType = "card" | "line" | "bar" | "pie" | "donut";
  
  let isLoading = false;
  let error: string | null = null;
  let formData = {
    kpi_id: '',
    title: '',
    widget_type: 'card' as WidgetType,
    position_x: 0,
    position_y: 0,
    width: 1,
    height: 1,
    settings: {}
  };
  
  // Widget type options
  const widgetTypes = [
    { value: 'card', label: 'Card' },
    { value: 'line', label: 'Line Chart' },
    { value: 'bar', label: 'Bar Chart' },
    { value: 'pie', label: 'Pie Chart' },
    { value: 'donut', label: 'Donut Chart' }
  ] as const;
  
  // Size options
  const sizeOptions = [
    { value: 1, label: '1x1 (Small)' },
    { value: 2, label: '2x1 (Medium)' },
    { value: 3, label: '3x1 (Large)' },
    { value: 4, label: '4x1 (Full width)' }
  ];
  
  // Check if user is authenticated
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  }
  
  onMount(async () => {
    await loadKPIs();
  });
  
  async function loadKPIs() {
    isLoading = true;
    error = null;
    
    try {
      const result = await fetchKPIs();
      
      if (!result.success) {
        error = result.message || 'Failed to load KPIs';
        return;
      }
      
      // Set default KPI if available
      if ($kpis.length > 0) {
        formData.kpi_id = $kpis[0].id.toString();
        
        // Set default title based on KPI name
        formData.title = $kpis[0].name;
      }
    } catch (err) {
      console.error('Error loading KPIs:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
  
  // Update title when KPI changes
  function handleKpiChange() {
    const selectedKpi = $kpis.find(k => k.id.toString() === formData.kpi_id);
    if (selectedKpi) {
      formData.title = selectedKpi.name;
    }
  }
  
  async function handleSubmit() {
    isLoading = true;
    error = null;
    
    try {
      // Prepare widget data
      const widgetData = {
        kpi_id: parseInt(formData.kpi_id),
        title: formData.title,
        widget_type: formData.widget_type,
        position_x: formData.position_x,
        position_y: formData.position_y,
        width: formData.width,
        height: formData.height,
        settings: formData.settings
      };
      
      const result = await createWidget(dashboardId, widgetData);
      
      if (result.success) {
        // Navigate back to dashboard
        goto(`/user/dashboard`);
      } else {
        error = result.message || 'Failed to create widget';
      }
    } catch (err) {
      console.error('Error creating widget:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="form-page">
  <header class="form-header">
    <h1>Add Widget to Dashboard</h1>
  </header>
  
  {#if error}
    <div class="error-message">
      {error}
    </div>
  {/if}
  
  <form on:submit|preventDefault={handleSubmit} class="widget-form">
    <div class="form-group">
      <label for="kpi">KPI <span class="required">*</span></label>
      <select 
        id="kpi" 
        bind:value={formData.kpi_id}
        on:change={handleKpiChange}
        required
        disabled={isLoading || $kpis.length === 0}
      >
        {#if $kpis.length === 0}
          <option value="">No KPIs available</option>
        {:else}
          {#each $kpis as kpi}
            <option value={kpi.id}>{kpi.name} ({kpi.unit})</option>
          {/each}
        {/if}
      </select>
    </div>
    
    <div class="form-group">
      <label for="title">Widget Title <span class="required">*</span></label>
      <input 
        type="text" 
        id="title" 
        bind:value={formData.title} 
        required
        disabled={isLoading}
        placeholder="Enter widget title"
      />
    </div>
    
    <div class="form-row">
      <div class="form-group">
        <label for="widget_type">Widget Type <span class="required">*</span></label>
        <select 
          id="widget_type" 
          bind:value={formData.widget_type}
          required
          disabled={isLoading}
        >
          {#each widgetTypes as type}
            <option value={type.value}>{type.label}</option>
          {/each}
        </select>
      </div>
      
      <div class="form-group">
        <label for="width">Widget Size <span class="required">*</span></label>
        <select 
          id="width" 
          bind:value={formData.width}
          required
          disabled={isLoading}
        >
          {#each sizeOptions as option}
            <option value={option.value}>{option.label}</option>
          {/each}
        </select>
      </div>
    </div>
    
    {#if formData.widget_type !== 'card'}
      <div class="form-group">
        <label>Chart Settings</label>
        <div class="chart-settings-placeholder">
          <p>Additional chart settings would be available here based on the selected chart type.</p>
        </div>
      </div>
    {/if}
    
    <div class="form-actions">
      <button 
        type="button" 
        class="btn btn-secondary" 
        on:click={() => goto('/user/dashboard')}
        disabled={isLoading}
      >
        Cancel
      </button>
      
      <button 
        type="submit" 
        class="btn btn-primary" 
        disabled={isLoading || !formData.kpi_id || !formData.title}
      >
        {#if isLoading}
          Creating...
        {:else}
          Add Widget
        {/if}
      </button>
    </div>
  </form>
</div>

<style>
  .form-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
  }
  
  .form-header {
    margin-bottom: 2rem;
  }
  
  h1 {
    margin: 0;
    font-size: 1.75rem;
    color: #333;
  }
  
  .error-message {
    background-color: #fff0f0;
    color: #e53935;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
  }
  
  .widget-form {
    background-color: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .form-group {
    margin-bottom: 1.5rem;
  }
  
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
  }
  
  label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
  }
  
  .required {
    color: #e53935;
  }
  
  input[type="text"],
  select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
  }
  
  input[type="text"]:focus,
  select:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
  }
  
  .chart-settings-placeholder {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    border: 1px dashed #ddd;
  }
  
  .chart-settings-placeholder p {
    color: #6c757d;
    margin: 0;
    text-align: center;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
  }
  
  .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  .btn-primary {
    background-color: #4a90e2;
    color: white;
    border: none;
  }
  
  .btn-primary:hover:not(:disabled) {
    background-color: #3a7bc8;
  }
  
  .btn-secondary {
    background-color: white;
    color: #333;
    border: 1px solid #ddd;
  }
  
  .btn-secondary:hover:not(:disabled) {
    background-color: #f8f9fa;
  }
  
  @media (max-width: 768px) {
    .form-row {
      grid-template-columns: 1fr;
      gap: 0;
    }
  }
</style> 