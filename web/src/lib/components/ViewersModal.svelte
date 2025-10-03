<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { user } from '$lib/stores/auth';
  import * as api from '$lib/services/api';
  import ShareModal from './ShareModal.svelte';

  export let isOpen = false;
  export let users: any[] = [];
  export let dashboardId: string;

  let newUserEmail = '';
  let newUserPermission = 'viewer';
  let isSubmitting = false;
  let submissionError: string | null = null;
  let removingUserId: string | null = null;
  let editingUserId: string | null = null;
  let editingPermission: string = '';

  // State for the ShareModal
  let isShareModalOpen = false;
  let generatedShareLink = '';
  let isGeneratingLink = false;

  const dispatch = createEventDispatcher();

  // Reactive variables
  $: currentUserId = $user?.id;
  $: isOwner = currentUserId ? users.some(u => u.id === currentUserId && u.permission_level === 'owner') : false;

  // Helper function to check if a user is the current user
  function isCurrentUser(userId: string): boolean {
    return currentUserId === parseInt(userId);
  }

  function closeModal() {
    isOpen = false;
    dispatch('close');
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && isOpen) {
      closeModal();
    }
  }

  async function handleShare() {
    isGeneratingLink = true;
    try {
      const result = await api.generateShareLink(dashboardId);
      if (result.data && result.data.token) {
        // Check if we're in the browser environment
        const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';
        generatedShareLink = `${baseUrl}/dashboards/share/${result.data.token}`;
        isShareModalOpen = true;
      } else {
        console.error("Failed to generate share link:", result);
        alert('Could not generate a share link. Please try again.');
      }
    } catch (e) {
      console.error("Error generating share link:", e);
      alert('An error occurred while generating the share link.');
    } finally {
      isGeneratingLink = false;
    }
  }

  async function handleRemoveUser(userId: string) {
    removingUserId = userId;
    try {
      await api.removeViewer(parseInt(dashboardId), parseInt(userId));
      dispatch('update');
    } catch (e) {
      console.error('Failed to remove user', e);
    } finally {
      removingUserId = null;
    }
  }

  async function handleAddUser() {
    if (!newUserEmail || !newUserPermission) return;
    
    isSubmitting = true;
    submissionError = null;
    
    try {
      // First, find the user by email
      const userResponse = await api.findUserByEmail(newUserEmail);
      
      if (!userResponse.success || !userResponse.data) {
        submissionError = 'User not found with that email address';
        return;
      }
      
      const userId = userResponse.data.id;
      
      // Now assign the user to the dashboard
      await api.assignViewer(parseInt(dashboardId), userId, newUserPermission);
      dispatch('update');
      newUserEmail = '';
      newUserPermission = 'viewer';
    } catch (e) {
      submissionError = e instanceof Error ? e.message : 'Failed to add user';
    } finally {
      isSubmitting = false;
    }
  }

  function startEditingPermission(user: any) {
    editingUserId = user.id;
    editingPermission = user.permission_level || 'viewer';
  }

  function cancelEditing() {
    editingUserId = null;
    editingPermission = '';
  }

  async function updatePermission() {
    if (!editingUserId || !editingPermission) return;
    
    isSubmitting = true;
    submissionError = null;
    
    try {
      await api.assignViewer(parseInt(dashboardId), parseInt(editingUserId), editingPermission);
      dispatch('update');
      editingUserId = null;
      editingPermission = '';
    } catch (e) {
      submissionError = e instanceof Error ? e.message : 'Failed to update permission';
    } finally {
      isSubmitting = false;
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if isOpen}
  <div
    class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-50 z-40 flex items-center justify-center"
  >
    <div
      class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-semibold">Manage Access</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <div class="p-4">
        <h3 class="text-lg font-medium mb-2">Current Users</h3>
        {#if users.length > 0}
          <ul class="divide-y divide-gray-200">
            {#each users as user}
              <li class="py-3 flex items-center justify-between">
                <div class="flex flex-col flex-grow">
                  <span class="text-sm text-gray-800">{user.email}</span>
                  {#if editingUserId === user.id}
                    <!-- Edit permission mode -->
                    <div class="flex items-center space-x-2 mt-1">
                      <select
                        bind:value={editingPermission}
                        class="text-xs px-2 py-1 border border-gray-300 rounded"
                      >
                        <option value="viewer">Viewer (Read-only)</option>
                        <option value="editor">Editor (Can edit)</option>
                      </select>
                      <button
                        on:click={updatePermission}
                        disabled={isSubmitting}
                        class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 disabled:opacity-50"
                      >
                        {isSubmitting ? 'Saving...' : 'Save'}
                      </button>
                      <button
                        on:click={cancelEditing}
                        disabled={isSubmitting}
                        class="text-xs bg-gray-500 text-white px-2 py-1 rounded hover:bg-gray-600 disabled:opacity-50"
                      >
                        Cancel
                      </button>
                    </div>
                  {:else}
                    <!-- Display mode -->
                    <div class="flex items-center space-x-2 mt-1">
                      <span class="text-xs text-gray-500 capitalize">{user.permission_level || 'viewer'}</span>
                      {#if isOwner && !isCurrentUser(user.id)}
                        <button
                          on:click={() => startEditingPermission(user)}
                          class="text-xs text-blue-600 hover:text-blue-800 font-semibold py-1 px-2 rounded border border-blue-300 hover:border-blue-500 transition"
                        >
                          Edit
                        </button>
                      {/if}
                    </div>
                  {/if}
                </div>
                {#if !isCurrentUser(user.id)}
                  <button 
                    on:click={() => handleRemoveUser(user.id)} 
                    disabled={removingUserId === user.id}
                    class="text-xs text-red-600 hover:text-red-800 font-semibold py-1 px-2 rounded border border-red-300 hover:border-red-500 transition disabled:opacity-50">
                    {removingUserId === user.id ? 'Removing...' : 'Remove'}
                  </button>
                {:else}
                  <span class="text-xs text-gray-400 italic">You</span>
                {/if}
              </li>
            {/each}
          </ul>
        {:else}
          <p class="text-sm text-gray-500">This dashboard has not been shared.</p>
        {/if}

        <!-- Add new user form -->
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
          <h4 class="text-sm font-medium mb-2">Add User Access</h4>
          <div class="space-y-2">
            <input
              type="email"
              bind:value={newUserEmail}
              placeholder="User email"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            />
            <select
              bind:value={newUserPermission}
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="viewer">Viewer (Read-only)</option>
              <option value="editor">Editor (Can edit)</option>
            </select>
            <button
              on:click={handleAddUser}
              disabled={isSubmitting || !newUserEmail}
              class="w-full bg-blue-600 text-white px-3 py-2 rounded-md text-sm hover:bg-blue-700 disabled:opacity-50"
            >
              {isSubmitting ? 'Adding...' : 'Add User'}
            </button>
            {#if submissionError}
              <p class="text-xs text-red-600">{submissionError}</p>
            {/if}
          </div>
        </div>
      </div>

      <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end">
        <button 
          on:click={handleShare}
          disabled={isGeneratingLink}
          class="bg-blue-600 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-700 disabled:opacity-50"
        >
          {isGeneratingLink ? 'Generating...' : 'Share via Link'}
        </button>
      </div>
    </div>
  </div>
{/if}

<ShareModal 
  bind:show={isShareModalOpen}
  shareLink={generatedShareLink}
  on:close={() => isShareModalOpen = false}
/>
