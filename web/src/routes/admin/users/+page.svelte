<script lang="ts">
  import { onMount } from 'svelte';
  import { fetchUsers, deleteUser, type UserProfile as User } from '$lib/stores/user'; // aliased UserProfile to User
  import { authStore } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  
  let isLoading = true;
  let error: string | null = null;
  let searchQuery = '';
  let users: User[] = [];
  let successMessage: string | null = null;
  
  // Check if user is authenticated and is an admin
  $: if (!$authStore.isAuthenticated) {
    goto('/login');
  } else if ($authStore.user && $authStore.user.role !== 'admin') {
    goto('/dashboard'); // Redirect non-admin users
  }
  
  // Get success message from URL if present
  $: if ($page.url.searchParams.has('success')) {
    successMessage = $page.url.searchParams.get('success');
    // Clear the success message after 5 seconds
    setTimeout(() => {
      successMessage = null;
    }, 5000);
  }
  
  // Filtered users based on search query
  $: filteredUsers = searchQuery && users 
    ? users.filter(user => 
        user.username.toLowerCase().includes(searchQuery.toLowerCase()) ||
        user.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
        user.role.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : users;
  
  onMount(async () => {
    await loadUsers();
  });
  
  async function loadUsers() {
    isLoading = true;
    error = null;
    
    try {
      const result = await fetchUsers();
      
      if (result.success) {
        users = result.users || []; // Reverted to use result.users
      } else {
        error = result.message || 'Failed to load users';
      }
    } catch (err) {
      console.error('Error loading users:', err);
      error = err instanceof Error ? err.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
  
  function handleCreate() {
    goto('/admin/users/create');
  }
  
  function handleEdit(userId: number) {
    goto(`/admin/users/edit/${userId}`);
  }
  
  async function handleDelete(user: User) {
    if (!confirm(`Are you sure you want to delete the user "${user.username}"?`)) {
      return;
    }
    
    try {
      const result = await deleteUser(user.id);
      
      if (result.success) {
        // Refresh the user list
        await loadUsers();
        successMessage = result.message || 'User deleted successfully';
        
        // Clear the success message after 5 seconds
        setTimeout(() => {
          successMessage = null;
        }, 5000);
      } else {
        alert(result.message || 'Failed to delete user');
      }
    } catch (error) {
      console.error('Error deleting user:', error);
      alert('Failed to delete user');
    }
  }
  
  // Role badge color
  function getRoleBadgeColor(role: string): string {
    switch (role) {
      case 'admin':
        return 'badge-admin';
      case 'editor':
        return 'badge-editor'; // Updated 'manager' to 'editor'
      case 'viewer':
        return 'badge-viewer'; // Added 'viewer'
      default:
        return 'badge-user'; // Fallback for any other role (e.g. 'user' if it exists or default)
    }
  }
</script>

<div class="admin-page">
  <header class="admin-header">
    <h1>User Management</h1>
    
    <div class="admin-controls">
      <div class="search-container">
        <input 
          type="text" 
          placeholder="Search users..." 
          bind:value={searchQuery}
          class="search-input"
        />
      </div>
      
      <button class="btn btn-primary" on:click={handleCreate}>
        Create New User
      </button>
    </div>
  </header>
  
  {#if successMessage}
    <div class="success-alert">
      {successMessage}
      <button class="close-btn" on:click={() => successMessage = null}>×</button>
    </div>
  {/if}
  
  {#if isLoading}
    <div class="loading-state">
      <p>Loading users...</p>
    </div>
  {:else if error}
    <div class="error-state">
      <p>{error}</p>
      <button class="btn" on:click={loadUsers}>Retry</button>
    </div>
  {:else if filteredUsers.length === 0}
    <div class="empty-state">
      {#if searchQuery}
        <h2>No users found matching "{searchQuery}"</h2>
        <p>Try a different search term or clear the search</p>
        <button class="btn" on:click={() => searchQuery = ''}>
          Clear Search
        </button>
      {:else}
        <h2>No users found</h2>
        <p>Create your first user to get started</p>
        <button class="btn btn-primary" on:click={handleCreate}>
          Create User
        </button>
      {/if}
    </div>
  {:else}
    <div class="users-table-container">
      <table class="users-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {#each filteredUsers as user (user.id)}
            <tr>
              <td>{user.username}</td>
              <td>{user.email}</td>
              <td>
                <span class="role-badge {getRoleBadgeColor(user.role)}">
                  {user.role}
                </span>
              </td>
              <td>{new Date(user.created_at).toLocaleDateString()}</td>
              <td class="actions-cell">
                <button 
                  class="btn-icon" 
                  on:click={() => handleEdit(user.id)}
                  title="Edit user"
                  aria-label="Edit user"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>
                
                <button 
                  class="btn-icon btn-delete" 
                  on:click={() => handleDelete(user)}
                  title="Delete user"
                  aria-label="Delete user"
                  disabled={user.id === $authStore.user?.id} 
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  {/if}
</div>

<style>
  .admin-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
  }
  
  .admin-header {
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
  
  .admin-controls {
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
  
  .success-alert {
    background-color: #d4edda;
    color: #155724;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
    position: relative;
  }
  
  .close-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #155724;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
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
  
  .users-table-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .users-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
  }
  
  th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
  }
  
  .role-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }
  
  .badge-admin {
    background-color: #dc3545;
    color: white;
  }
  
  .badge-manager {
    background-color: #fd7e14;
    color: white;
  }
  
  .badge-user {
    background-color: #6c757d;
    color: white;
  }
  
  .actions-cell {
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
  
  .btn-icon:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .btn-delete:hover:not(:disabled) {
    color: #dc3545;
    background-color: #ffebee;
  }
  
  @media (max-width: 768px) {
    .admin-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .admin-controls {
      width: 100%;
    }
    
    .search-input {
      width: 100%;
    }
    
    .users-table-container {
      overflow-x: auto;
    }
  }
</style> 