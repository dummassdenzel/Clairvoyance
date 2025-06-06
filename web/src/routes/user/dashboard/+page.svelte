<script lang="ts">
  import { onMount } from 'svelte';
  import { fetchDashboards, dashboards, type Widget } from '$lib/stores/dashboard';
  import { deleteWidget } from '$lib/stores/widget';
  import DashboardWidget from '$lib/components/widgets/DashboardWidget.svelte';
  import { goto } from '$app/navigation';
  import { authStore } from '$lib/stores/auth';
  
  let isLoading = true;
  let error: string | null = null;
  let selectedDashboardId: number | null = null;
  
  // Check if user is authenticated
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  }
  
  onMount(async () => {
    await loadDashboards();
  });
  
  async function loadDashboards() {
    isLoading = true;
    error = null;
    
    try {
      const result = await fetchDashboards();
      
      if (!result.success) {
        error = result.message || 'Failed to load dashboards';
        return;
      }
      
      // If we have dashboards, select the default one or the first one
      if ($dashboards.length > 0) {
        const defaultDashboard = $dashboards.find(d => d.is_default);
        selectedDashboardId = defaultDashboard ? defaultDashboard.id : $dashboards[0].id;
      }
    } catch (err) {
      console.error('Error loading dashboards:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
  
  function handleWidgetEdit(widget: Widget) {
    // Navigate to widget edit page
    goto(`/dashboard/widgets/edit/${widget.id}`);
  }
  
  async function handleWidgetDelete(widget: Widget) {
    if (!confirm(`Are you sure you want to delete the widget "${widget.title}"?`)) {
      return;
    }
    
    try {
      if (selectedDashboardId) {
        const result = await deleteWidget(widget.id);
        
        if (result.success) {
          // Refresh the dashboard after deletion
          await loadDashboards();
        } else {
          alert(result.message || 'Failed to delete widget');
        }
      }
    } catch (error) {
      console.error('Error deleting widget:', error);
      alert('Failed to delete widget');
    }
  }
  
  // Handle dashboard selection change
  function handleDashboardChange(event: Event) {
    selectedDashboardId = Number((event.target as HTMLSelectElement).value);
  }
  
  // Navigate to create new dashboard page
  function handleCreateDashboard() {
    goto('/user/dashboard/create');
  }
  
  // Navigate to create new widget page
  function handleCreateWidget() {
    if (selectedDashboardId) {
      goto(`/user/dashboard/${selectedDashboardId}/widgets/create`);
    }
  }
</script>

<div class="dashboard-page">
  <header class="dashboard-header">
    <h1>Dashboard</h1>
    
    <div class="dashboard-controls">
      {#if $dashboards.length > 0}
        <select 
          value={selectedDashboardId} 
          on:change={handleDashboardChange}
          class="dashboard-select"
        >
          {#each $dashboards as dashboard}
            <option value={dashboard.id}>{dashboard.name}</option>
          {/each}
        </select>
      {/if}
      
      <button class="btn btn-primary" on:click={handleCreateDashboard}>
        New Dashboard
      </button>
      
      {#if selectedDashboardId}
        <button class="btn btn-primary" on:click={handleCreateWidget}>
          Add Widget
        </button>
      {/if}
    </div>
  </header>
  
  {#if isLoading}
    <div class="loading-state">
      <p>Loading dashboard...</p>
    </div>
  {:else if error}
    <div class="error-state">
      <p>{error}</p>
      <button class="btn" on:click={loadDashboards}>Retry</button>
    </div>
  {:else if $dashboards.length === 0}
    <div class="empty-state">
      <h2>No dashboards found</h2>
      <p>Create your first dashboard to start tracking your KPIs</p>
      <button class="btn btn-primary" on:click={handleCreateDashboard}>
        Create Dashboard
      </button>
    </div>
  {:else if selectedDashboardId}
    {@const selectedDashboard = $dashboards.find(d => d.id === selectedDashboardId)}
    
    {#if selectedDashboard}
      <div class="dashboard-content">
        <div class="dashboard-description">
          {#if selectedDashboard.description}
            <p>{selectedDashboard.description}</p>
          {/if}
        </div>
        
        {#if selectedDashboard.widgets && selectedDashboard.widgets.length > 0}
          <div class="dashboard-grid">
            {#each selectedDashboard.widgets as widget (widget.id)}
              <DashboardWidget 
                {widget} 
                dashboardId={selectedDashboardId}
                onEdit={handleWidgetEdit} 
                onDelete={handleWidgetDelete}
              />
            {/each}
          </div>
        {:else}
          <div class="empty-state">
            <h3>No widgets found</h3>
            <p>Add widgets to your dashboard to start tracking your KPIs</p>
            <button class="btn btn-primary" on:click={handleCreateWidget}>
              Add Widget
            </button>
          </div>
        {/if}
      </div>
    {/if}
  {/if}
</div>

<style>
  .dashboard-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
  }
  
  .dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  h1 {
    margin: 0;
    font-size: 1.75rem;
    color: #333;
  }
  
  .dashboard-controls {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
  }
  
  .dashboard-select {
    padding: 0.5rem;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: white;
    min-width: 200px;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    cursor: pointer;
    border: 1px solid #ddd;
    background-color: white;
    transition: background-color 0.2s;
  }
  
  .btn:hover {
    background-color: #f8f9fa;
  }
  
  .btn-primary {
    background-color: #4a90e2;
    color: white;
    border: none;
  }
  
  .btn-primary:hover {
    background-color: #3a7bc8;
  }
  
  .loading-state,
  .error-state,
  .empty-state {
    text-align: center;
    padding: 3rem;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .error-state {
    color: #dc3545;
  }
  
  .empty-state h2,
  .empty-state h3 {
    margin-top: 0;
    color: #333;
  }
  
  .empty-state p {
    color: #6c757d;
    margin-bottom: 1.5rem;
  }
  
  .dashboard-content {
    margin-top: 1rem;
  }
  
  .dashboard-description {
    margin-bottom: 1.5rem;
    color: #6c757d;
  }
  
  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-auto-rows: minmax(200px, auto);
    gap: 1rem;
  }
  
  @media (max-width: 1024px) {
    .dashboard-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 768px) {
    .dashboard-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .dashboard-controls {
      width: 100%;
    }
    
    .dashboard-select {
      flex-grow: 1;
    }
  }
  
  @media (max-width: 576px) {
    .dashboard-grid {
      grid-template-columns: 1fr;
    }
  }
</style> 