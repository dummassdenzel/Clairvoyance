<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';
  import * as api from '$lib/services/api';
  import type { Dashboard, ApiResponse } from '$lib/types';

  export let show: boolean = false;
  export let dashboard: Dashboard | null = null;

  let name = '';
  let description = '';
  let loading = false;
  let error: string | null = null;

  const dispatch = createEventDispatcher();

  // When the dashboard prop changes, update the form fields
  $: if (dashboard && show) {
    name = dashboard.name;
    description = dashboard.description || '';
  }

  function closeModal() {
    show = false;
    error = null;
    loading = false;
  }

  async function handleSubmit() {
    if (!name.trim() || !dashboard) return;

    loading = true;
    error = null;

    try {
      const response: ApiResponse<{ dashboard: Dashboard }> = await api.updateDashboard(dashboard.id, {
        name: name.trim(),
        description: description.trim()
      });

      if (response.success) {
        dispatch('success', response.data?.dashboard);
        closeModal();
      } else {
        error = response.message || 'Failed to update dashboard.';
      }
    } catch (e) {
      error = e instanceof Error ? e.message : 'An unexpected error occurred.';
    } finally {
      loading = false;
    }
  }
</script>

{#if show}
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-75 transition-opacity z-40" on:click={closeModal} aria-hidden="true"></div>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full" on:click|stopPropagation>
      <form on:submit|preventDefault={handleSubmit}>
        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Dashboard</h3>
          <div class="mt-4 space-y-4">
            <div>
              <label for="edit-dashboard-name" class="block text-sm font-medium text-gray-700">Name</label>
              <input 
                type="text" 
                id="edit-dashboard-name" 
                bind:value={name} 
                required 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
            </div>
            <div>
              <label for="edit-dashboard-description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea 
                id="edit-dashboard-description" 
                bind:value={description} 
                rows="4" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              ></textarea>
            </div>
          </div>
          {#if error}
            <p class="mt-4 text-sm text-red-600">{error}</p>
          {/if}
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button 
            type="submit" 
            disabled={loading} 
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-900 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
          >
            {loading ? 'Saving...' : 'Save Changes'}
          </button>
          <button 
            type="button" 
            on:click={closeModal} 
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
{/if}
