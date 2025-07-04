<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import * as api from '$lib/services/api';
  import ShareModal from './ShareModal.svelte';

  export let isOpen = false;
  export let viewers: any[] = [];
  export let dashboardId: string;

  let newViewerEmail = '';
  let isSubmitting = false;
  let submissionError: string | null = null;
  let removingViewerId: string | null = null;

  // State for the ShareModal
  let isShareModalOpen = false;
  let generatedShareLink = '';
  let isGeneratingLink = false;

  const dispatch = createEventDispatcher();

  function closeModal() {
    isOpen = false;
    dispatch('close');
  }

  async function handleShare() {
    isGeneratingLink = true;
    try {
      const result = await api.generateShareLink(dashboardId);
      if (result.data && result.data.token) {
        const baseUrl = window.location.origin;
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

  async function handleRemoveViewer(viewerId: string) {
    removingViewerId = viewerId;
    try {
      await api.removeViewer(dashboardId, viewerId);
      dispatch('update');
    } catch (e) {
      console.error('Failed to remove viewer', e);
    } finally {
      removingViewerId = null;
    }
  }
</script>

{#if isOpen}
  <div 
    class="fixed inset-0 bg-[rgba(0,0,0,0.5)] bg-opacity-50 z-40 flex items-center justify-center"
    on:click={closeModal}
    role="dialog"
    aria-modal="true"
  >
    <div 
      class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4"
      on:click|stopPropagation
    >
      <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-semibold">Manage Viewers</h2>
        <button on:click={closeModal} class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <div class="p-4">
        <h3 class="text-lg font-medium mb-2">Current Viewers</h3>
        {#if viewers.length > 0}
          <ul class="divide-y divide-gray-200">
            {#each viewers as viewer}
              <li class="py-3 flex items-center justify-between">
                <span class="text-sm text-gray-800">{viewer.email}</span>
                <button 
                  on:click={() => handleRemoveViewer(viewer.id)} 
                  disabled={removingViewerId === viewer.id}
                  class="text-xs text-red-600 hover:text-red-800 font-semibold py-1 px-2 rounded border border-red-300 hover:border-red-500 transition disabled:opacity-50">
                  {removingViewerId === viewer.id ? 'Removing...' : 'Remove'}
                </button>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="text-sm text-gray-500">This dashboard has not been shared.</p>
        {/if}
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
