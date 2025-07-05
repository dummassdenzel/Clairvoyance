<script lang="ts">
  import { onMount } from 'svelte';
  import { getAdminUsers, updateUserRole, deleteUser } from '$lib/services/api';
  import { user as currentUser } from '$lib/stores/auth';

  interface User {
    id: number;
    email: string;
    role: 'viewer' | 'editor' | 'admin';
  }

  let users: User[] = [];
  let isLoading = true;
  let error: string | null = null;
  let successMessage: string | null = null;

  $: filteredUsers = users.filter(user => user.id !== $currentUser?.id);

  onMount(async () => {
    await fetchUsers();
  });

  async function fetchUsers() {
    isLoading = true;
    clearMessages();
    try {
      const response = await getAdminUsers();
      if (response.status === 'success' && response.data?.users) {
        users = response.data.users;
      } else {
        throw new Error(response.message || 'Failed to fetch users.');
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      isLoading = false;
    }
  }

  async function handleUpdateRole(id: number, newRole: string) {
    clearMessages();
    try {
      const response = await updateUserRole(id, newRole);
      if (response.status === 'success') {
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
        if (response.status === 'success') {
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
</script>

<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">Admin - User Management</h1>

  {#if isLoading}
    <p>Loading users...</p>
  {:else if error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
      <strong class="font-bold">Error:</strong>
      <span class="block sm:inline">{error}</span>
    </div>
  {/if}

  {#if successMessage}
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
      <strong class="font-bold">Success:</strong>
      <span class="block sm:inline">{successMessage}</span>
    </div>
  {/if}

  <div class="bg-white shadow-md rounded-lg overflow-hidden">
    <table class="min-w-full leading-normal">
      <thead>
        <tr>
          <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User Email</th>
          <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
          <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody>
        {#each filteredUsers as user (user.id)}
          <tr>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <p class="text-gray-900 whitespace-no-wrap">{user.email}</p>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <select 
                class="border border-gray-300 rounded px-2 py-1"
                value={user.role}
                on:change={(e) => handleUpdateRole(user.id, e.currentTarget.value)}
                disabled={user.id === $currentUser?.id}>
                <option value="viewer">Viewer</option>
                <option value="editor">Editor</option>
                <option value="admin">Admin</option>
              </select>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <button 
                class="text-red-600 hover:text-red-900 disabled:text-gray-400 disabled:cursor-not-allowed"
                on:click={() => handleDeleteUser(user.id, user.email)}
                disabled={user.id === $currentUser?.id}>
                Delete
              </button>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>
