<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import type { KPI } from '$lib/services/kpi';
  
  export let kpi: KPI;
  export let showActions = true;
  
  const dispatch = createEventDispatcher<{
    edit: { kpi: KPI };
    delete: { kpi: KPI };
    view: { kpi: KPI };
  }>();
  
  function handleEdit() {
    dispatch('edit', { kpi });
  }
  
  function handleDelete() {
    dispatch('delete', { kpi });
  }
  
  function handleView() {
    dispatch('view', { kpi });
  }
  
  // Calculate progress percentage
  $: progressPercentage = kpi.target && kpi.target > 0 && kpi.current_value !== undefined
    ? Math.min(Math.round((kpi.current_value / kpi.target) * 100), 100)
    : 0;
    
  // Determine color based on percentage
  $: progressColor = 
    progressPercentage >= 90 ? '#28a745' : // Green
    progressPercentage >= 60 ? '#ffc107' : // Yellow
    '#dc3545'; // Red
</script>

<div class="kpi-item">
  <div class="kpi-info">
    <h3>{kpi.name}</h3>
    <div class="kpi-meta">
      <span class="category">{kpi.category_name || 'Uncategorized'}</span>
      <span class="unit">{kpi.unit}</span>
    </div>
    
    <div class="kpi-progress">
      <div class="progress-values">
        <span class="current-value">{kpi.current_value || '0'}</span>
        <span class="target-value">Target: {kpi.target}</span>
      </div>
      
      <div class="progress-bar">
        <div 
          class="progress-fill" 
          style="width: {progressPercentage}%; background-color: {progressColor};"
        ></div>
      </div>
      
      <div class="progress-percentage">
        {progressPercentage}%
      </div>
    </div>
  </div>
  
  {#if showActions}
    <div class="kpi-actions">
      <button class="btn-icon" on:click={handleView} title="View details">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
      </button>
      
      <button class="btn-icon" on:click={handleEdit} title="Edit KPI">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
      </button>
      
      <button class="btn-icon btn-delete" on:click={handleDelete} title="Delete KPI">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
      </button>
    </div>
  {/if}
</div>

<style>
  .kpi-item {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  
  .kpi-info {
    flex-grow: 1;
  }
  
  h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: #333;
  }
  
  .kpi-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
  }
  
  .category {
    color: #6c757d;
    background-color: #f8f9fa;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
  }
  
  .unit {
    color: #6c757d;
  }
  
  .kpi-progress {
    margin-top: 0.5rem;
  }
  
  .progress-values {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
  }
  
  .current-value {
    font-weight: 600;
    font-size: 1.1rem;
  }
  
  .target-value {
    color: #6c757d;
    font-size: 0.875rem;
  }
  
  .progress-bar {
    width: 100%;
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.25rem;
  }
  
  .progress-fill {
    height: 100%;
    transition: width 0.3s ease;
  }
  
  .progress-percentage {
    text-align: right;
    font-size: 0.8rem;
    color: #6c757d;
  }
  
  .kpi-actions {
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
  
  .btn-delete:hover {
    color: #dc3545;
    background-color: #ffebee;
  }
  
  @media (max-width: 768px) {
    .kpi-item {
      flex-direction: column;
    }
    
    .kpi-actions {
      margin-top: 1rem;
      align-self: flex-end;
    }
  }
</style> 