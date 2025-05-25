<script lang="ts">
  import { onMount } from 'svelte';
  import { createDashboard } from '$lib/services/dashboard';
  import { authStore } from '$lib/services/auth';
  import { goto } from '$app/navigation';
  
  let isLoading = false;
  let error: string | null = null;
  let formData = {
    name: '',
    description: '',
    is_default: false
  };
  
  // Check if user is authenticated
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  }
  
  async function handleSubmit() {
    isLoading = true;
    error = null;
    
    try {
      const result = await createDashboard(formData);
      
      if (result.success) {
        // Navigate to the dashboard page
        goto('/user/dashboard');
      } else {
        error = result.message || 'Failed to create dashboard';
      }
    } catch (err) {
      console.error('Error creating dashboard:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="form-page">
  <header class="form-header">
    <h1>Create New Dashboard</h1>
  </header>
  
  {#if error}
    <div class="error-message">
      {error}
    </div>
  {/if}
  
  <form on:submit|preventDefault={handleSubmit} class="dashboard-form">
    <div class="form-group">
      <label for="name">Dashboard Name <span class="required">*</span></label>
      <input 
        type="text" 
        id="name" 
        bind:value={formData.name} 
        required
        disabled={isLoading}
        placeholder="Enter dashboard name"
      />
    </div>
    
    <div class="form-group">
      <label for="description">Description</label>
      <textarea 
        id="description" 
        bind:value={formData.description}
        disabled={isLoading}
        placeholder="Enter dashboard description (optional)"
        rows="4"
      ></textarea>
    </div>
    
    <div class="form-group checkbox-group">
      <label class="checkbox-label">
        <input 
          type="checkbox" 
          bind:checked={formData.is_default}
          disabled={isLoading}
        />
        <span>Set as default dashboard</span>
      </label>
    </div>
    
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
        disabled={isLoading || !formData.name}
      >
        {#if isLoading}
          Creating...
        {:else}
          Create Dashboard
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
  
  .dashboard-form {
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
  
  input[type="text"],
  textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
  }
  
  input[type="text"]:focus,
  textarea:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
  }
  
  .checkbox-group {
    display: flex;
    align-items: center;
  }
  
  .checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
  }
  
  .checkbox-label input {
    margin-right: 0.5rem;
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
</style> 