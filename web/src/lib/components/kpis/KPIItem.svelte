<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import type { KPI } from '$lib/stores/kpi';
  
  export let kpi: KPI;
  export let showActions = true;
  
  const dispatch = createEventDispatcher<{
    edit: { kpi: KPI };
    delete: { kpi: KPI };
    view: { kpi: KPI };
  }>();
  
  function handleEdit() {
    dispatch('edit', { kpi });
  }
  
  function handleDelete() {
    dispatch('delete', { kpi });
  }
  
  function handleView() {
    dispatch('view', { kpi });
  }
  
  // Calculate progress percentage
  $: progressPercentage = kpi.target && kpi.target > 0 && kpi.current_value !== undefined
    ? Math.min(Math.round((kpi.current_value / kpi.target) * 100), 100)
    : 0;
    
  // Determine color based on percentage
  $: progressColor = 
    progressPercentage >= 90 ? '#28a745' : // Green for Tailwind: bg-[#28a745]
    progressPercentage >= 60 ? '#ffc107' : // Yellow for Tailwind: bg-[#ffc107]
    '#dc3545'; // Red for Tailwind: bg-[#dc3545]
</script>

<div class="bg-white rounded-lg shadow-md p-4 mb-4 flex flex-col md:flex-row justify-between items-start">
  <div class="flex-grow w-full md:w-auto md:mb-0 mb-4">
    <h3 class="mb-2 text-lg font-semibold text-gray-800">{kpi.name}</h3>
    <div class="flex gap-4 mb-4 text-sm">
      <span class="text-gray-600 bg-gray-100 px-2 py-1 rounded">{kpi.category_name || 'Uncategorized'}</span>
      <span class="text-gray-600">Unit: {kpi.unit}</span>
    </div>
    
    <div class="mt-2">
      <div class="flex justify-between mb-1">
        <span class="text-lg font-semibold text-gray-700">{kpi.current_value || '0'}</span>
        <span class="text-sm text-gray-600">Target: {kpi.target}</span>
      </div>
      
      <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden mb-1">
        <div 
          class="h-full transition-all duration-300 ease-in-out bg-[{progressColor}]"
          style="width: {progressPercentage}%;"
        ></div>
      </div>
      
      <div class="text-right text-xs text-gray-600">
        {progressPercentage}%
      </div>
    </div>
  </div>
  
  {#if showActions}
    <div class="flex gap-2 md:mt-0 mt-4 w-full md:w-auto justify-end md:justify-start">
      <button class="p-1 rounded text-gray-500 hover:text-gray-700 hover:bg-gray-200 transition-colors duration-200 flex items-center justify-center" on:click={handleView} title="View details">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
      </button>
      
      <button class="p-1 rounded text-gray-500 hover:text-gray-700 hover:bg-gray-200 transition-colors duration-200 flex items-center justify-center" on:click={handleEdit} title="Edit KPI">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
      </button>
      
      <button class="p-1 rounded text-gray-500 hover:text-red-600 hover:bg-red-100 transition-colors duration-200 flex items-center justify-center" on:click={handleDelete} title="Delete KPI">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
      </button>
    </div>
  {/if}
</div>