<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let show = false;
  export let title = 'Confirm Action';
  export let message = 'Are you sure you want to proceed?';

  const dispatch = createEventDispatcher();

  function handleConfirm() {
    dispatch('confirm');
  }

  function handleClose() {
    dispatch('close');
  }

  function handleKeydown(event: KeyboardEvent) {
    if (show && event.key === 'Escape') {
      handleClose();
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if show}
  <!-- svelte-ignore a11y-click-events-have-key-events -->
  <div class="fixed inset-0 bg-[rgba(0,0,0,0.5)]  bg-opacity-75 transition-opacity z-50 flex items-center justify-center" on:click={handleClose} role="presentation">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm" on:click|stopPropagation role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
      <h3 id="modal-title" class="text-lg font-medium leading-6 text-gray-900">{title}</h3>
      <div class="mt-2">
        <p class="text-sm text-gray-500">{message}</p>
      </div>
      <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
        <button on:click={handleConfirm} type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm">
          Confirm
        </button>
        <button on:click={handleClose} type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
          Cancel
        </button>
      </div>
    </div>
  </div>
{/if}
