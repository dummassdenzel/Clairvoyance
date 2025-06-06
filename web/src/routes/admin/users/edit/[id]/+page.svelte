<script lang="ts">
  import { onMount } from 'svelte';
  import { authStore } from '$lib/stores/auth';
  import { fetchUser, updateUser, type UserProfile } from '$lib/stores/user';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';

  type UserRoleType = 'admin' | 'editor' | 'viewer';
  
  // Form data
  let username = '';
  let email = '';
  let password = '';
  let confirmPassword = '';
  let role: UserRoleType | '' = ''; // Initialize with '' for unloaded state, then UserRoleType
  let userId: number;
  
  // Form state
  let isLoading = true;
  let isSubmitting = false;
  let error: string | null = null;
  let formErrors: Record<string, string> = {};
  
  // Check if user is authenticated and is an admin
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  } else if ($authStore.user && $authStore.user.role !== 'admin') {
    goto('/dashboard'); // Redirect non-admin users
  }
  
  // Get user ID from URL
  $: userId = parseInt($page.params.id);
  
  onMount(async () => {
    await loadUser();
  });
  
  async function loadUser() {
    isLoading = true;
    error = null;
    
    try {
      const result = await fetchUser(userId);
      
      if (result.success && result.user) {
        username = result.user.username;
        email = result.user.email;
        role = result.user.role;
      } else {
        error = result.message || 'Failed to load user';
      }
    } catch (err) {
      console.error('Error loading user:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
  
  function validateForm(): boolean {
    formErrors = {};
    
    if (!username.trim()) {
      formErrors.username = 'Username is required';
    } else if (username.length < 3) {
      formErrors.username = 'Username must be at least 3 characters';
    }
    
    if (!email.trim()) {
      formErrors.email = 'Email is required';
    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
      formErrors.email = 'Email is invalid';
    }
    
    if (password || confirmPassword) {
      if (password.length < 6) {
        formErrors.password = 'Password must be at least 6 characters';
      }
      
      if (password !== confirmPassword) {
        formErrors.confirmPassword = 'Passwords do not match';
      }
    }
    
    if (!role) {
      formErrors.role = 'Role is required';
    }
    
    return Object.keys(formErrors).length === 0;
  }
  
  async function handleSubmit() {
    if (!validateForm()) {
      return;
    }
    
    isSubmitting = true;
    error = null;
    
    try {
      const userData = {
        username,
        email,
        role: role as UserRoleType, // Explicitly cast role
        // Only include password if it was changed
        ...(password ? { password } : {})
      };
      
      const result = await updateUser(userId, userData);
      
      if (result.success) {
        // Redirect to user list with success message
        goto('/admin/users?success=User updated successfully');
      } else {
        error = result.message || 'Failed to update user';
      }
    } catch (err) {
      console.error('Error updating user:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isSubmitting = false;
    }
  }
  
  function handleCancel() {
    goto('/admin/users');
  }
</script>

<div class="admin-page">
  <header class="admin-header">
    <h1>Edit User</h1>
  </header>
  
  <div class="form-container">
    {#if isLoading}
      <div class="loading-state">
        <p>Loading user data...</p>
      </div>
    {:else if error && !isSubmitting}
      <div class="error-state">
        <p>{error}</p>
        <button class="btn" on:click={loadUser}>Retry</button>
        <button class="btn" on:click={handleCancel}>Back to Users</button>
      </div>
    {:else}
      {#if error}
        <div class="error-alert">
          {error}
        </div>
      {/if}
      
      <form on:submit|preventDefault={handleSubmit}>
        <div class="form-group">
          <label for="username">Username <span class="required">*</span></label>
          <input 
            type="text" 
            id="username" 
            bind:value={username} 
            class="form-control" 
            disabled={isSubmitting}
            class:is-invalid={formErrors.username}
          />
          {#if formErrors.username}
            <div class="error-message">{formErrors.username}</div>
          {/if}
        </div>
        
        <div class="form-group">
          <label for="email">Email Address <span class="required">*</span></label>
          <input 
            type="email" 
            id="email" 
            bind:value={email} 
            class="form-control" 
            disabled={isSubmitting}
            class:is-invalid={formErrors.email}
          />
          {#if formErrors.email}
            <div class="error-message">{formErrors.email}</div>
          {/if}
        </div>
        
        <div class="form-section">
          <h3>Change Password</h3>
          <p class="form-hint">Leave blank to keep current password</p>
          
          <div class="form-group">
            <label for="password">New Password</label>
            <input 
              type="password" 
              id="password" 
              bind:value={password} 
              class="form-control" 
              disabled={isSubmitting}
              class:is-invalid={formErrors.password}
              placeholder="Enter new password"
            />
            {#if formErrors.password}
              <div class="error-message">{formErrors.password}</div>
            {/if}
          </div>
          
          <div class="form-group">
            <label for="confirmPassword">Confirm New Password</label>
            <input 
              type="password" 
              id="confirmPassword" 
              bind:value={confirmPassword} 
              class="form-control" 
              disabled={isSubmitting}
              class:is-invalid={formErrors.confirmPassword}
              placeholder="Confirm new password"
            />
            {#if formErrors.confirmPassword}
              <div class="error-message">{formErrors.confirmPassword}</div>
            {/if}
          </div>
        </div>
        
        <div class="form-group">
          <label for="role">User Role <span class="required">*</span></label>
          <select 
            id="role" 
            bind:value={role} 
            class="form-control" 
            disabled={isSubmitting}
            class:is-invalid={formErrors.role}
          >
            <option value="viewer">Viewer</option>
            <option value="editor">Editor</option>
            <option value="admin">Administrator</option>
          </select>
          {#if formErrors.role}
            <div class="error-message">{formErrors.role}</div>
          {/if}
          
          {#if userId === $authStore.user?.id}
            <div class="warning-message">
              You cannot change your own role
            </div>
          {/if}
          
          <div class="role-description">
            {#if role === 'admin'}
              <p><strong>Administrator:</strong> Complete system management, user management, critical data governance. Can create, edit, delete dashboards, KPIs, users, categories. Can import/export data and manage data retention.</p>
            {:else if role === 'editor'}
              <p><strong>Editor:</strong> Content creation and curation. Can create and edit dashboards, KPIs, widgets. Can import data and generate reports. Cannot delete critical data or manage users.</p>
            {:else if role === 'viewer'}
              <p><strong>Viewer:</strong> Monitor performance metrics, access assigned dashboards. Can view dashboards and create personal dashboards. Can export data and generate standard reports. Cannot create or modify shared content.</p>
            {:else}
              <p>Select a role to see its description.</p> 
            {/if}
          </div>
        </div>
        
        <div class="form-actions">
          <button 
            type="button" 
            class="btn btn-secondary" 
            on:click={handleCancel} 
            disabled={isSubmitting}
          >
            Cancel
          </button>
          
          <button 
            type="submit" 
            class="btn btn-primary" 
            disabled={isSubmitting}
          >
            {isSubmitting ? 'Saving Changes...' : 'Save Changes'}
          </button>
        </div>
      </form>
    {/if}
  </div>
</div>

<style>
  .admin-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
  }
  
  .admin-header {
    margin-bottom: 2rem;
  }
  
  h1 {
    margin: 0;
    font-size: 1.75rem;
    color: #333;
  }
  
  h3 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
    color: #333;
  }
  
  .form-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 2rem;
  }
  
  .loading-state,
  .error-state {
    text-align: center;
    padding: 2rem;
  }
  
  .error-state {
    color: #dc3545;
  }
  
  .error-alert {
    background-color: #f8d7da;
    color: #721c24;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
  }
  
  .form-section {
    margin-bottom: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
  }
  
  .form-hint {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0;
    margin-bottom: 1rem;
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
    color: #dc3545;
  }
  
  .form-control {
    width: 100%;
    padding: 0.75rem;
    font-size: 1rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }
  
  .form-control:focus {
    border-color: #4a90e2;
    box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
    outline: 0;
  }
  
  .form-control.is-invalid {
    border-color: #dc3545;
  }
  
  .form-control:disabled {
    background-color: #e9ecef;
    opacity: 1;
  }
  
  .error-message {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }
  
  .warning-message {
    color: #856404;
    background-color: #fff3cd;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    margin-top: 0.5rem;
  }
  
  .role-description {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 4px;
    font-size: 0.9rem;
  }
  
  .role-description p {
    margin: 0;
    color: #495057;
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
    cursor: pointer;
    border: none;
    transition: background-color 0.2s;
  }
  
  .btn:disabled {
    opacity: 0.65;
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
  
  @media (max-width: 768px) {
    .form-actions {
      flex-direction: column-reverse;
    }
    
    .btn {
      width: 100%;
    }
  }
</style> 