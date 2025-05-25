<script lang="ts">
  import { onMount } from 'svelte';
  import { fetchKPIs, kpis, deleteKPI, type KPI } from '$lib/services/kpi';
  import KPIItem from '$lib/components/kpis/KPIItem.svelte';
  import { authStore } from '$lib/services/auth';
  import { goto } from '$app/navigation';
  
  let isLoading = true;
  let error: string | null = null;
  let searchQuery = '';
  
  // Check if user is authenticated
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  }
  
  // Filtered KPIs based on search query
  $: filteredKPIs = searchQuery 
    ? $kpis.filter(kpi => 
        kpi.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        (kpi.category_name && kpi.category_name.toLowerCase().includes(searchQuery.toLowerCase()))
      )
    : $kpis;
  
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
      }
    } catch (err) {
      console.error('Error loading KPIs:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
  
  function handleCreate() {
    goto('/user/kpis/create');
  }
  
  function handleEdit(event: CustomEvent<{ kpi: KPI }>) {
    goto(`/user/kpis/edit/${event.detail.kpi.id}`);
  }
  
  function handleView(event: CustomEvent<{ kpi: KPI }>) {
    goto(`/user/kpis/${event.detail.kpi.id}`);
  }
  
  async function handleDelete(event: CustomEvent<{ kpi: KPI }>) {
    const kpi = event.detail.kpi;
    
    if (!confirm(`Are you sure you want to delete the KPI "${kpi.name}"?`)) {
      return;
    }
    
    try {
      const result = await deleteKPI(kpi.id);
      
      if (result.success) {
        // The store is already updated in the service
      } else {
        alert(result.message || 'Failed to delete KPI');
      }
    } catch (error) {
      console.error('Error deleting KPI:', error);
      alert('Failed to delete KPI');
    }
  }
</script>

<div class="kpi-page">
  <header class="kpi-header">
    <h1>Key Performance Indicators</h1>
    
    <div class="kpi-controls">
      <div class="search-container">
        <input 
          type="text" 
          placeholder="Search KPIs..." 
          bind:value={searchQuery}
          class="search-input"
        />
      </div>
      
      <button class="btn btn-primary" on:click={handleCreate}>
        Create New KPI
      </button>
    </div>
  </header>
  
  {#if isLoading}
    <div class="loading-state">
      <p>Loading KPIs...</p>
    </div>
  {:else if error}
    <div class="error-state">
      <p>{error}</p>
      <button class="btn" on:click={loadKPIs}>Retry</button>
    </div>
  {:else if filteredKPIs.length === 0}
    <div class="empty-state">
      {#if searchQuery}
        <h2>No KPIs found matching "{searchQuery}"</h2>
        <p>Try a different search term or clear the search</p>
        <button class="btn" on:click={() => searchQuery = ''}>
          Clear Search
        </button>
      {:else}
        <h2>No KPIs found</h2>
        <p>Create your first KPI to start tracking your performance</p>
        <button class="btn btn-primary" on:click={handleCreate}>
          Create KPI
        </button>
      {/if}
    </div>
  {:else}
    <div class="kpi-list">
      {#each filteredKPIs as kpi (kpi.id)}
        <KPIItem 
          {kpi} 
          on:edit={handleEdit} 
          on:delete={handleDelete}
          on:view={handleView}
        />
      {/each}
    </div>
  {/if}
</div>

<style>
  .kpi-page {
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
  
  h1 {
    margin: 0;
    font-size: 1.75rem;
    color: #333;
  }
  
  .kpi-controls {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
  }
  
  .search-container {
    position: relative;
  }
  
  .search-input {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: 1px solid #ddd;
    min-width: 250px;
    font-size: 0.9rem;
  }
  
  .search-input:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
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
    margin-top: 1rem;
  }
  
  .error-state {
    color: #dc3545;
  }
  
  .empty-state h2 {
    margin-top: 0;
    color: #333;
  }
  
  .empty-state p {
    color: #6c757d;
    margin-bottom: 1.5rem;
  }
  
  .kpi-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  @media (max-width: 768px) {
    .kpi-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .kpi-controls {
      width: 100%;
    }
    
    .search-input {
      width: 100%;
    }
  }
</style> 