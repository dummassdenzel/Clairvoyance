<script>
    import { createEventDispatcher } from 'svelte';
    const dispatch = createEventDispatcher();

    let kpiName = '';
    let unit = '';
    let target = '';
  
    function handleSubmit() {
        console.log({ kpiName, unit, target });
        // Optionally close after submit
        dispatch('close');
    }
  
    function closeModal() {
        dispatch('close');
    }
</script>


<!-- Modal Container -->
<div class="bg-white rounded-xl shadow-xl px-6 py-3 w-[600px] outline-1 outline-gray-200">
    <h1 class="text-lg font-semibold mb-4 text-blue-950">New KPI</h1>
  
    <!-- Form Fields -->
    <form class="space-y-4" on:submit|preventDefault={handleSubmit}>
      <!-- Name Field -->
      <div>
        <label for="kpiName" class="block text-xs font-medium text-gray-700 mb-1">
          Name:
        </label>
        <input
          id="kpiName"
          name="kpiName"
          type="text"
          placeholder="Enter KPI name"
          bind:value={kpiName}
          class="w-full border text-sm text-black border-gray-300 rounded-xl px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
  
      <!-- Unit and Target -->
      <div class="flex items-center space-x-4">
        <!-- Unit of Measurement -->
        <div class="flex-1">
          <label for="unit" class="block text-xs font-medium text-gray-700 mb-1">
            Unit of measurement:
          </label>
          <select
            id="unit"
            name="unit"
            bind:value={unit}
            class="w-full border text-sm text-black border-gray-300 rounded-xl px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="INR">INR</option>
          </select>
        </div>
  
        <!-- Target -->
        <div class="flex-1">
          <label for="target" class="block text-xs font-medium text-gray-700 mb-1">
            Target:
          </label>
          <input
            id="target"
            name="target"
            type="number"
            bind:value={target}
            class="w-full border text-xs text-black border-gray-300 rounded-xl px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
  
      <!-- Buttons -->
      <div class="flex justify-end space-x-4 pt-4">
        <button
          type="submit"
          class="bg-blue-900 text-sm text-white font-semibold px-6 py-1 rounded hover:bg-blue-950"
        >
          Create
        </button>
        <button
          type="button"
          on:click={closeModal}
          class="bg-gray-500 text-sm text-white font-semibold px-6 py-1 rounded hover:bg-gray-600"
        >
          Cancel
        </button>
      </div>
    </form>
  </div>
  
  