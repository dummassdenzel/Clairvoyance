<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { fetchKPI, deleteKPI } from '$lib/services/kpi';
  import { authStore } from '$lib/services/auth';
  import { goto } from '$app/navigation';
  
  // Get KPI ID from URL params
  const kpiId = parseInt($page.params.id);
  
  let isLoading = true;
  let error: string | null = null;
  let kpi: any = null;
  let measurements: any[] = [];
  
  // Check if user is authenticated
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  }
  
  onMount(async () => {
    await loadKPI();
  });
  
  async function loadKPI() {
    isLoading = true;
    error = null;
    
    try {
      const result = await fetchKPI(kpiId);
      
      if (result.success) {
        kpi = result.kpi;
        measurements = result.kpi.measurements || [];
      } else {
        error = result.message || 'Failed to load KPI';
      }
    } catch (err) {
      console.error('Error loading KPI:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
  
  function handleEdit() {
    goto(`/user/kpis/edit/${kpiId}`);
  }
  
  async function handleDelete() {
    if (!confirm(`Are you sure you want to delete the KPI "${kpi.name}"?`)) {
      return;
    }
    
    try {
      const result = await deleteKPI(kpiId);
      
      if (result.success) {
        goto('/user/kpis');
      } else {
        alert(result.message || 'Failed to delete KPI');
      }
    } catch (error) {
      console.error('Error deleting KPI:', error);
      alert('Failed to delete KPI');
    }
  }
  
  function handleAddMeasurement() {
    goto(`/user/kpis/${kpiId}/measurements/add`);
  }
  
  // Calculate progress percentage
  $: progressPercentage = kpi?.target && kpi.target > 0 && kpi.current_value !== undefined
    ? Math.min(Math.round((kpi.current_value / kpi.target) * 100), 100)
    : 0;
    
  // Determine color based on percentage
  $: progressColor = 
    progressPercentage >= 90 ? '#28a745' : // Green
    progressPercentage >= 60 ? '#ffc107' : // Yellow
    '#dc3545'; // Red
</script>

<div class="kpi-detail-page">
  <header class="kpi-header">
    <div class="header-left">
      <a href="/user/kpis" class="back-link">
        &larr; Back to KPIs
      </a>
      <h1>KPI Details</h1>
    </div>
    
    {#if !isLoading && !error && kpi}
      <div class="header-actions">
        <button class="btn btn-secondary" on:click={handleEdit}>
          Edit KPI
        </button>
        <button class="btn btn-danger" on:click={handleDelete}>
          Delete KPI
        </button>
      </div>
    {/if}
  </header>
  
  {#if isLoading}
    <div class="loading-state">
      <p>Loading KPI details...</p>
    </div>
  {:else if error}
    <div class="error-state">
      <p>{error}</p>
      <button class="btn" on:click={loadKPI}>Retry</button>
    </div>
  {:else if kpi}
    <div class="kpi-detail-content">
      <div class="kpi-overview">
        <div class="kpi-info">
          <h2>{kpi.name}</h2>
          <div class="kpi-meta">
            <span class="category">{kpi.category_name || 'Uncategorized'}</span>
            <span class="unit">Unit: {kpi.unit}</span>
          </div>
          
          <div class="kpi-progress">
            <div class="progress-header">
              <div class="current-value">{kpi.current_value || '0'}</div>
              <div class="target-value">Target: {kpi.target}</div>
            </div>
            
            <div class="progress-bar">
              <div 
                class="progress-fill" 
                style="width: {progressPercentage}%; background-color: {progressColor};"
              ></div>
            </div>
            
            <div class="progress-percentage">
              {progressPercentage}% of target
            </div>
          </div>
        </div>
        
        <div class="kpi-actions">
          <button class="btn btn-primary" on:click={handleAddMeasurement}>
            Add Measurement
          </button>
        </div>
      </div>
      
      <div class="kpi-chart">
        <h3>Performance History</h3>
        <div class="chart-placeholder">
          <p>Chart visualization would be displayed here</p>
          <p>Showing historical measurements over time</p>
        </div>
      </div>
      
      <div class="kpi-measurements">
        <div class="measurements-header">
          <h3>Measurements</h3>
        </div>
        
        {#if measurements.length === 0}
          <div class="empty-measurements">
            <p>No measurements recorded yet.</p>
            <button class="btn btn-primary" on:click={handleAddMeasurement}>
              Add First Measurement
            </button>
          </div>
        {:else}
          <div class="measurements-table">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Value</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                {#each measurements as measurement}
                  <tr>
                    <td>{new Date(measurement.date).toLocaleDateString()}</td>
                    <td>{measurement.value} {kpi.unit}</td>
                    <td>{measurement.notes || '-'}</td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>

<style>
  .kpi-detail-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1.5rem;
  }
  
  .kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .header-left {
    display: flex;
    flex-direction: column;
  }
  
  .back-link {
    color: #4a90e2;
    text-decoration: none;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
  }
  
  .back-link:hover {
    text-decoration: underline;
  }
  
  h1 {
    margin: 0;
    font-size: 1.75rem;
    color: #333;
  }
  
  .header-actions {
    display: flex;
    gap: 0.75rem;
  }
  
  .loading-state,
  .error-state {
    text-align: center;
    padding: 3rem;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .error-state {
    color: #dc3545;
  }
  
  .kpi-detail-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  
  .kpi-overview {
    background-color: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .kpi-info {
    flex: 1;
    min-width: 300px;
  }
  
  h2 {
    margin: 0 0 0.75rem 0;
    font-size: 1.5rem;
    color: #333;
  }
  
  .kpi-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  
  .category {
    color: #6c757d;
    background-color: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
  }
  
  .unit {
    color: #6c757d;
    font-size: 0.875rem;
  }
  
  .kpi-progress {
    margin-top: 1rem;
    max-width: 400px;
  }
  
  .progress-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
  }
  
  .current-value {
    font-size: 1.5rem;
    font-weight: 600;
  }
  
  .target-value {
    color: #6c757d;
  }
  
  .progress-bar {
    height: 10px;
    background-color: #e9ecef;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 0.5rem;
  }
  
  .progress-fill {
    height: 100%;
    transition: width 0.3s ease;
  }
  
  .progress-percentage {
    text-align: right;
    font-size: 0.875rem;
    color: #6c757d;
  }
  
  .kpi-actions {
    display: flex;
    align-items: flex-start;
  }
  
  .kpi-chart {
    background-color: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    color: #333;
  }
  
  .chart-placeholder {
    background-color: #f8f9fa;
    height: 300px;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
  }
  
  .chart-placeholder p {
    margin: 0.25rem 0;
  }
  
  .kpi-measurements {
    background-color: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .measurements-header {
    margin-bottom: 1rem;
  }
  
  .empty-measurements {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
  }
  
  .measurements-table {
    width: 100%;
    overflow-x: auto;
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
  }
  
  th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
  }
  
  th {
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    cursor: pointer;
    border: none;
    transition: background-color 0.2s;
  }
  
  .btn-primary {
    background-color: #4a90e2;
    color: white;
  }
  
  .btn-primary:hover {
    background-color: #3a7bc8;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-secondary:hover {
    background-color: #5a6268;
  }
  
  .btn-danger {
    background-color: #dc3545;
    color: white;
  }
  
  .btn-danger:hover {
    background-color: #c82333;
  }
  
  @media (max-width: 768px) {
    .kpi-overview {
      flex-direction: column;
    }
    
    .kpi-actions {
      width: 100%;
      margin-top: 1rem;
    }
    
    .kpi-actions .btn {
      width: 100%;
    }
  }
</style> 