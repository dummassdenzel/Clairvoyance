<script lang="ts">
  import { onMount } from 'svelte';
  import { getAdminUsers, updateUserRole, deleteUser, adminCreateUser, getAdminStats } from '$lib/services/api';
  import { user as currentUser } from '$lib/stores/auth';

  interface User {
    id: number;
    email: string;
    role: 'editor' | 'admin';
    created_at?: string;
  }

  interface SystemStats {
    users: {
      total: number;
      by_role: { admin: number; editor: number };
      new_this_week: number;
      new_this_month: number;
    };
    dashboards: {
      total: number;
      new_this_week: number;
      new_this_month: number;
      top_owners: Array<{ email: string; dashboard_count: number }>;
    };
    kpis: {
      total: number;
      new_this_week: number;
      new_this_month: number;
      total_entries: number;
      entries_this_week: number;
    };
    recent_activity: Array<{
      type: string;
      description: string;
      timestamp: string;
    }>;
  }

  let users: User[] = [];
  let systemStats: SystemStats | null = null;
  let isLoading = true;
  let statsLoading = true;
  let error: string | null = null;
  let successMessage: string | null = null;
  let showAddUserModal = false;
  let showUserDetailsModal = false;
  let selectedUser: User | null = null;
  let newUser = { email: '', password: '', role: 'editor' };
  let isSubmitting = false;
  
  // User table enhancements
  let searchTerm = '';
  let sortBy: 'email' | 'role' | 'created_at' = 'email';
  let sortOrder: 'asc' | 'desc' = 'asc';
  let currentPage = 1;
  let itemsPerPage = 10;

  $: filteredUsers = users
    .filter(user => user.id !== $currentUser?.id)
    .filter(user => user.email.toLowerCase().includes(searchTerm.toLowerCase()))
    .sort((a, b) => {
      let aVal: string | number = a[sortBy] || '';
      let bVal: string | number = b[sortBy] || '';
      
      if (sortBy === 'created_at') {
        aVal = a.created_at || '';
        bVal = b.created_at || '';
      }
      
      if (sortOrder === 'asc') {
        return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
      } else {
        return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
      }
    });

  $: totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
  $: paginatedUsers = filteredUsers.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  onMount(async () => {
    await Promise.all([fetchUsers(), fetchSystemStats()]);
  });

  async function fetchUsers() {
    isLoading = true;
    clearMessages();
    try {
      const response = await getAdminUsers();
      if (response.success && response.users) {
        users = response.users;
      } else {
        throw new Error(response.message || 'Failed to fetch users.');
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      isLoading = false;
    }
  }

  async function fetchSystemStats() {
    statsLoading = true;
    try {
      const response = await getAdminStats();
      if (response.success && response.data) {
        systemStats = response.data;
      }
    } catch (e: any) {
      console.error('Failed to fetch system stats:', e);
    } finally {
      statsLoading = false;
    }
  }

  async function handleUpdateRole(id: number, newRole: string) {
    clearMessages();
    try {
      const response = await updateUserRole(id, newRole);
      if (response.success) {
        successMessage = 'User role updated successfully.';
        await fetchUsers(); // Refresh the list
      } else {
        throw new Error(response.message || 'Failed to update role.');
      }
    } catch (e: any) {
      error = e.message;
    }
  }

  async function handleDeleteUser(id: number, email: string) {
    clearMessages();
    if (confirm(`Are you sure you want to delete the user: ${email}? This action cannot be undone.`)) {
      try {
        const response = await deleteUser(id);
        if (response.success) {
          successMessage = 'User deleted successfully.';
          await fetchUsers(); // Refresh the list
        } else {
          throw new Error(response.message || 'Failed to delete user.');
        }
      } catch (e: any) {
        error = e.message;
      }
    }
  }

    function clearMessages() {
    error = null;
    successMessage = null;
  }

  async function handleCreateUser() {
    isSubmitting = true;
    clearMessages();
    try {
      const response = await adminCreateUser(newUser);
      if (response.success) {
        successMessage = 'User created successfully.';
        showAddUserModal = false;
        newUser = { email: '', password: '', role: 'editor' };
        await Promise.all([fetchUsers(), fetchSystemStats()]);
      } else {
        throw new Error(response.message || 'Failed to create user.');
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      isSubmitting = false;
    }
  }

  function handleSort(column: 'email' | 'role' | 'created_at') {
    if (sortBy === column) {
      sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
    } else {
      sortBy = column;
      sortOrder = 'asc';
    }
    currentPage = 1; // Reset to first page when sorting
  }

  function handlePageChange(page: number) {
    currentPage = page;
  }

  function handleUserClick(user: User) {
    selectedUser = user;
    showUserDetailsModal = true;
  }

  function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function formatRelativeTime(dateString: string) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
    
    if (diffInHours < 1) return 'Just now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`;
    return formatDate(dateString);
  }
</script>

<div class="min-h-screen bg-gray-50">
  <div class="container mx-auto p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600 mt-1">System overview and user management</p>
      </div>
      <button 
        on:click={() => showAddUserModal = true} 
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add User
      </button>
    </div>

    <!-- Messages -->
    {#if error}
      <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
        <div class="flex">
          <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium">Error:</span>
          <span class="ml-1">{error}</span>
        </div>
      </div>
    {/if}

    {#if successMessage}
      <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
        <div class="flex">
          <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium">Success:</span>
          <span class="ml-1">{successMessage}</span>
        </div>
      </div>
    {/if}

    <!-- System Statistics -->
    {#if statsLoading}
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {#each Array(4) as _}
          <div class="bg-white rounded-lg shadow p-6 animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
            <div class="h-8 bg-gray-200 rounded w-1/2"></div>
          </div>
        {/each}
      </div>
    {:else if systemStats}
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Users Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Users</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.users.total}</p>
            </div>
          </div>
          <div class="mt-4 flex justify-between text-sm">
            <span class="text-green-600">+{systemStats.users.new_this_week} this week</span>
            <span class="text-gray-500">{systemStats.users.by_role.admin} admin, {systemStats.users.by_role.editor} editor</span>
          </div>
        </div>

        <!-- Dashboards Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Dashboards</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.dashboards.total}</p>
            </div>
          </div>
          <div class="mt-4 flex justify-between text-sm">
            <span class="text-green-600">+{systemStats.dashboards.new_this_week} this week</span>
            <span class="text-gray-500">+{systemStats.dashboards.new_this_month} this month</span>
          </div>
        </div>

        <!-- KPIs Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total KPIs</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.kpis.total}</p>
            </div>
          </div>
          <div class="mt-4 flex justify-between text-sm">
            <span class="text-green-600">+{systemStats.kpis.new_this_week} this week</span>
            <span class="text-gray-500">{systemStats.kpis.total_entries} entries</span>
          </div>
        </div>

        <!-- Activity Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-orange-100 rounded-lg">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Recent Activity</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.recent_activity.length}</p>
            </div>
          </div>
          <div class="mt-4 text-sm text-gray-500">
            <span>Last 7 days</span>
          </div>
        </div>
      </div>

      <!-- Recent Activity & Top Owners -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
          </div>
          <div class="p-6">
            {#if systemStats.recent_activity.length > 0}
              <div class="space-y-4">
                {#each systemStats.recent_activity.slice(0, 5) as activity}
                  <div class="flex items-start">
                    <div class="flex-shrink-0">
                      <div class="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm text-gray-900">{activity.description}</p>
                      <p class="text-xs text-gray-500">{formatRelativeTime(activity.timestamp)}</p>
                    </div>
                  </div>
                {/each}
              </div>
            {:else}
              <p class="text-gray-500 text-sm">No recent activity</p>
            {/if}
          </div>
        </div>

        <!-- Top Dashboard Owners -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Top Dashboard Owners</h3>
          </div>
          <div class="p-6">
            {#if systemStats.dashboards.top_owners.length > 0}
              <div class="space-y-4">
                {#each systemStats.dashboards.top_owners as owner}
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">{owner.email.charAt(0).toUpperCase()}</span>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">{owner.email}</p>
                      </div>
                    </div>
                    <span class="text-sm text-gray-500">{owner.dashboard_count} dashboards</span>
                  </div>
                {/each}
              </div>
            {:else}
              <p class="text-gray-500 text-sm">No dashboard owners found</p>
            {/if}
          </div>
        </div>
      </div>
    {/if}

    <!-- User Management Section -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900">User Management</h3>
          <div class="flex items-center space-x-4">
            <!-- Search -->
            <div class="relative">
              <input
                type="text"
                placeholder="Search users..."
                bind:value={searchTerm}
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <!-- Items per page -->
            <select bind:value={itemsPerPage} class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value={5}>5 per page</option>
              <option value={10}>10 per page</option>
              <option value={25}>25 per page</option>
              <option value={50}>50 per page</option>
            </select>
          </div>
        </div>
      </div>

      {#if isLoading}
        <div class="p-6 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading users...</p>
        </div>
      {:else}
        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th 
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  on:click={() => handleSort('email')}
                >
                  <div class="flex items-center">
                    Email
                    {#if sortBy === 'email'}
                      <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                      </svg>
                    {/if}
                  </div>
                </th>
                <th 
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  on:click={() => handleSort('role')}
                >
                  <div class="flex items-center">
                    Role
                    {#if sortBy === 'role'}
                      <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                      </svg>
                    {/if}
                  </div>
                </th>
                <th 
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  on:click={() => handleSort('created_at')}
                >
                  <div class="flex items-center">
                    Created
                    {#if sortBy === 'created_at'}
                      <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                      </svg>
                    {/if}
                  </div>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              {#each paginatedUsers as user (user.id)}
                <tr class="hover:bg-gray-50 cursor-pointer" on:click={() => handleUserClick(user)}>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">{user.email.charAt(0).toUpperCase()}</span>
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">{user.email}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {user.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'}">
                      {user.role}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {user.created_at ? formatDate(user.created_at) : 'N/A'}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center space-x-2">
                      <select 
                        class="text-sm border border-gray-300 rounded px-2 py-1 {user.id === $currentUser?.id ? 'bg-gray-100 cursor-not-allowed' : ''}"
                        value={user.role}
                        on:change={(e) => handleUpdateRole(user.id, e.currentTarget.value)}
                        disabled={user.id === $currentUser?.id}
                        on:click|stopPropagation
                      >
                        <option value="editor">Editor</option>
                        <option value="admin">Admin</option>
                      </select>
                      <button 
                        class="text-red-600 hover:text-red-900 disabled:text-gray-400 disabled:cursor-not-allowed"
                        on:click|stopPropagation={() => handleDeleteUser(user.id, user.email)}
                        disabled={user.id === $currentUser?.id}
                        aria-label="Delete user {user.email}"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        {#if totalPages > 1}
          <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                Showing {((currentPage - 1) * itemsPerPage) + 1} to {Math.min(currentPage * itemsPerPage, filteredUsers.length)} of {filteredUsers.length} results
              </div>
              <div class="flex items-center space-x-2">
                <button
                  on:click={() => handlePageChange(currentPage - 1)}
                  disabled={currentPage === 1}
                  class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                
                {#each Array(Math.min(5, totalPages)) as _, i}
                  {@const page = i + 1}
                  <button
                    on:click={() => handlePageChange(page)}
                    class="px-3 py-1 text-sm border rounded-md {page === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'}"
                  >
                    {page}
                  </button>
                {/each}
                
                <button
                  on:click={() => handlePageChange(currentPage + 1)}
                  disabled={currentPage === totalPages}
                  class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        {/if}
      {/if}
    </div>
  </div>
</div>

<!-- Add User Modal -->
{#if showAddUserModal}
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
  <div class="relative mx-auto p-6 border w-full max-w-md shadow-lg rounded-lg bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-medium text-gray-900">Add New User</h3>
      <button 
        on:click={() => showAddUserModal = false}
        class="text-gray-400 hover:text-gray-600"
        aria-label="Close modal"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
    
    <form on:submit|preventDefault={handleCreateUser}>
      <div class="space-y-4">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input 
            type="email" 
            id="email" 
            bind:value={newUser.email} 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
            required
            placeholder="user@example.com"
          >
        </div>
        
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input 
            type="password" 
            id="password" 
            bind:value={newUser.password} 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
            required
            placeholder="Enter password"
          >
        </div>
        
        <div>
          <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select 
            id="role" 
            bind:value={newUser.role} 
            class="w-full px-3 py-2 border border-gray-300 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="editor">Editor</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      
      <div class="flex justify-end gap-3 mt-6">
        <button 
          type="button" 
          on:click={() => showAddUserModal = false} 
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          disabled={isSubmitting}
        >
          Cancel
        </button>
        <button 
          type="submit" 
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-50"
          disabled={isSubmitting}
        >
          {isSubmitting ? 'Creating...' : 'Create User'}
        </button>
      </div>
    </form>
  </div>
</div>
{/if}

<!-- User Details Modal -->
{#if showUserDetailsModal && selectedUser}
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
  <div class="relative mx-auto p-6 border w-full max-w-lg shadow-lg rounded-lg bg-white">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-medium text-gray-900">User Details</h3>
      <button 
        on:click={() => showUserDetailsModal = false}
        class="text-gray-400 hover:text-gray-600"
        aria-label="Close modal"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
    
    <div class="space-y-6">
      <!-- User Avatar and Basic Info -->
      <div class="flex items-center space-x-4">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
          <span class="text-2xl font-medium text-gray-600">{selectedUser.email.charAt(0).toUpperCase()}</span>
        </div>
        <div>
          <h4 class="text-xl font-medium text-gray-900">{selectedUser.email}</h4>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {selectedUser.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'}">
            {selectedUser.role}
          </span>
        </div>
      </div>
      
      <!-- User Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <h5 class="text-sm font-medium text-gray-500 mb-1">User ID</h5>
          <p class="text-sm text-gray-900">{selectedUser.id}</p>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
          <h5 class="text-sm font-medium text-gray-500 mb-1">Role</h5>
          <p class="text-sm text-gray-900 capitalize">{selectedUser.role}</p>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
          <h5 class="text-sm font-medium text-gray-500 mb-1">Email</h5>
          <p class="text-sm text-gray-900">{selectedUser.email}</p>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
          <h5 class="text-sm font-medium text-gray-500 mb-1">Created</h5>
          <p class="text-sm text-gray-900">{selectedUser.created_at ? formatDate(selectedUser.created_at) : 'N/A'}</p>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
        <button 
          on:click={() => showUserDetailsModal = false}
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
        >
          Close
        </button>
        <button 
          on:click={() => {
            showUserDetailsModal = false;
            // You could add edit functionality here
          }}
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
        >
          Edit User
        </button>
      </div>
    </div>
  </div>
</div>
{/if}
