<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let shareLink = '';
  export let show = false;

  const dispatch = createEventDispatcher();

  let copied = false;

  function copyToClipboard() {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
      navigator.clipboard.writeText(shareLink).then(() => {
        copied = true;
        setTimeout(() => (copied = false), 2000); // Reset after 2 seconds
      });
    }
  }

  function closeModal() {
    dispatch('close');
  }

  // Close modal on "Escape" key press
  function handleKeydown(event: KeyboardEvent) {
    if (show && event.key === 'Escape') {
      closeModal();
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <div 
    class="fixed inset-0 bg-[rgba(0,0,0,0.5)] flex items-center justify-center z-50"
    role="dialog" 
    aria-modal="true"
  >
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md" role="document">
      <h2 class="text-xl font-bold mb-4">Share Dashboard</h2>
      <p class="text-gray-600 mb-4">Anyone with this link can view this dashboard. The link will expire in 7 days.</p>
      
      <div class="flex items-center space-x-2 bg-gray-100 p-2 rounded border">
        <input type="text" readonly value={shareLink} class="flex-grow bg-transparent outline-none text-gray-800" />
        <button on:click={copyToClipboard} class="px-4 py-2 rounded text-white font-semibold transition"
          class:bg-green-500={copied}
          class:hover:bg-green-600={copied}
          class:bg-blue-600={!copied}
          class:hover:bg-blue-700={!copied}
        >
          {copied ? 'Copied!' : 'Copy'}
        </button>
      </div>

      <div class="mt-6 text-right">
        <button on:click={closeModal} class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
          Close
        </button>
      </div>
    </div>
  </div>
{/if}
