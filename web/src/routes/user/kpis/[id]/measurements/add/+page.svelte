<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { fetchKPI, type KPI, addKpiMeasurement, type KpiMeasurementInput } from '$lib/stores/kpi';
  import { authStore } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  
  // Get KPI ID from URL params
  const kpiId = parseInt($page.params.id);
  
  let isLoading = true;
  let error: string | null = null;
  let kpi: KPI | null = null;
  let formData = {
    value: '',
    date: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD format
    notes: ''
  };
  
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
        kpi = result.kpi ?? null;
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
  
  async function handleSubmit() {
    if (!formData.value || !formData.date) {
      error = "Value and Date are required.";
      return;
    }
    if (isNaN(parseFloat(formData.value))) {
      error = "Value must be a number.";
      return;
    }

    isLoading = true;
    error = null; // Clear previous errors

    const measurementData: KpiMeasurementInput = {
      value: parseFloat(formData.value),
      date: formData.date,
      notes: formData.notes || undefined
    };

    try {
      const result = await addKpiMeasurement(kpiId, measurementData);

      if (result.success) {
        // Optionally: show success message (e.g., alert or toast)
        // alert('Measurement added successfully!');
        goto(`/user/kpis/${kpiId}`);
      } else {
        error = result.message || 'Failed to add measurement.';
      }
    } catch (err) {
      console.error('Error adding measurement:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred during submission.';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="form-page">
  <header class="form-header">
    <div class="header-left">
      <a href="/user/kpis/{kpiId}" class="back-link">
        &larr; Back to KPI
      </a>
      <h1>Add Measurement</h1>
    </div>
  </header>
  
  {#if isLoading && !kpi}
    <div class="loading-state">
      <p>Loading KPI details...</p>
    </div>
  {:else if error}
    <div class="error-message">
      {error}
    </div>
  {:else if kpi}
    <div class="kpi-info-header">
      <h2>{kpi.name}</h2>
      <div class="kpi-meta">
        <span class="category">{kpi.category_name || 'Uncategorized'}</span>
        <span class="unit">Unit: {kpi.unit}</span>
      </div>
    </div>
    
    <form on:submit|preventDefault={handleSubmit} class="measurement-form">
      <div class="form-group">
        <label for="value">Value <span class="required">*</span></label>
        <div class="input-with-unit">
          <input 
            type="number" 
            id="value" 
            bind:value={formData.value} 
            required
            disabled={isLoading}
            step="any"
            placeholder="Enter measurement value"
          />
          <span class="unit-indicator">{kpi.unit}</span>
        </div>
      </div>
      
      <div class="form-group">
        <label for="date">Date <span class="required">*</span></label>
        <input 
          type="date" 
          id="date" 
          bind:value={formData.date} 
          required
          disabled={isLoading}
          max={new Date().toISOString().split('T')[0]}
        />
      </div>
      
      <div class="form-group">
        <label for="notes">Notes</label>
        <textarea 
          id="notes" 
          bind:value={formData.notes}
          disabled={isLoading}
          placeholder="Optional notes about this measurement"
          rows="4"
        ></textarea>
      </div>
      
      <div class="form-actions">
        <button 
          type="button" 
          class="btn btn-secondary" 
          on:click={() => goto(`/user/kpis/${kpiId}`)}
          disabled={isLoading}
        >
          Cancel
        </button>
        
        <button 
          type="submit" 
          class="btn btn-primary" 
          disabled={isLoading || !formData.value || !formData.date}
        >
          {#if isLoading}
            Saving...
          {:else}
            Save Measurement
          {/if}
        </button>
      </div>
    </form>
  {/if}
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
  
  .loading-state {
    text-align: center;
    padding: 3rem;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .error-message {
    background-color: #fff0f0;
    color: #e53935;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
  }
  
  .kpi-info-header {
    margin-bottom: 2rem;
  }
  
  h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    color: #333;
  }
  
  .kpi-meta {
    display: flex;
    gap: 1rem;
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
  
  .measurement-form {
    background-color: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .form-group {
    margin-bottom: 1.5rem;
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
  
  input[type="number"],
  input[type="date"],
  textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
  }
  
  input[type="number"]:focus,
  input[type="date"]:focus,
  textarea:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
  }
  
  .input-with-unit {
    position: relative;
  }
  
  .unit-indicator {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
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
    border: none;
  }
  
  .btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  .btn-primary {
    background-color: #4a90e2;
    color: white;
  }
  
  .btn-primary:hover:not(:disabled) {
    background-color: #3a7bc8;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-secondary:hover:not(:disabled) {
    background-color: #5a6268;
  }
</style> 