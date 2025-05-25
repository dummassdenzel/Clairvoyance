<script lang="ts">
  import { onMount } from 'svelte';
  import { createKPI } from '$lib/services/kpi';
  import { authStore } from '$lib/services/auth';
  import { goto } from '$app/navigation';
  
  let isLoading = false;
  let error: string | null = null;
  let categories: { id: number; name: string }[] = [];
  let formData = {
    name: '',
    category_id: '',
    unit: '',
    target: '',
    current_value: ''
  };
  
  // Check if user is authenticated
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  }
  
  onMount(async () => {
    await loadCategories();
  });
  
  async function loadCategories() {
    try {
      // This would be replaced with an actual API call to fetch categories
      categories = [
        { id: 1, name: 'Financial' },
        { id: 2, name: 'Customer' },
        { id: 3, name: 'Internal Process' },
        { id: 4, name: 'Learning & Growth' }
      ];
      
      // Set default category if available
      if (categories.length > 0) {
        formData.category_id = categories[0].id.toString();
      }
    } catch (err) {
      console.error('Error loading categories:', err);
    }
  }
  
  async function handleSubmit() {
    isLoading = true;
    error = null;
    
    try {
      // Convert string values to numbers
      const kpiData = {
        name: formData.name,
        category_id: parseInt(formData.category_id),
        unit: formData.unit,
        target: parseFloat(formData.target),
        current_value: formData.current_value ? parseFloat(formData.current_value) : undefined
      };
      
      const result = await createKPI(kpiData);
      
      if (result.success) {
        // Navigate to the KPIs page
        goto('/user/kpis');
      } else {
        error = result.message || 'Failed to create KPI';
      }
    } catch (err) {
      console.error('Error creating KPI:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="form-page">
  <header class="form-header">
    <h1>Create New KPI</h1>
  </header>
  
  {#if error}
    <div class="error-message">
      {error}
    </div>
  {/if}
  
  <form on:submit|preventDefault={handleSubmit} class="kpi-form">
    <div class="form-group">
      <label for="name">KPI Name <span class="required">*</span></label>
      <input 
        type="text" 
        id="name" 
        bind:value={formData.name} 
        required
        disabled={isLoading}
        placeholder="Enter KPI name"
      />
    </div>
    
    <div class="form-group">
      <label for="category">Category <span class="required">*</span></label>
      <select 
        id="category" 
        bind:value={formData.category_id}
        required
        disabled={isLoading}
      >
        {#each categories as category}
          <option value={category.id}>{category.name}</option>
        {/each}
      </select>
    </div>
    
    <div class="form-group">
      <label for="unit">Unit <span class="required">*</span></label>
      <input 
        type="text" 
        id="unit" 
        bind:value={formData.unit} 
        required
        disabled={isLoading}
        placeholder="e.g., $, %, hours, etc."
      />
    </div>
    
    <div class="form-row">
      <div class="form-group">
        <label for="target">Target <span class="required">*</span></label>
        <input 
          type="number" 
          id="target" 
          bind:value={formData.target}
          required
          disabled={isLoading}
          step="any"
          placeholder="Target value"
        />
      </div>
      
      <div class="form-group">
        <label for="current_value">Current Value</label>
        <input 
          type="number" 
          id="current_value" 
          bind:value={formData.current_value}
          disabled={isLoading}
          step="any"
          placeholder="Current value (optional)"
        />
      </div>
    </div>
    
    <div class="form-actions">
      <button 
        type="button" 
        class="btn btn-secondary" 
        on:click={() => goto('/user/kpis')}
        disabled={isLoading}
      >
        Cancel
      </button>
      
      <button 
        type="submit" 
        class="btn btn-primary" 
        disabled={isLoading || !formData.name || !formData.unit || !formData.target}
      >
        {#if isLoading}
          Creating...
        {:else}
          Create KPI
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
  
  .kpi-form {
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
  input[type="number"],
  select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
  }
  
  input[type="text"]:focus,
  input[type="number"]:focus,
  select:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
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