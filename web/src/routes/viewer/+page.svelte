<script lang="ts">
  import type { PageData } from './$types';

  export let data: PageData;

  const { dashboards } = data;
</script>

<div class="max-w-7xl mx-auto">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Shared Dashboards</h1>
    <p class="mt-1 text-sm text-gray-600">Dashboards that have been shared with you.</p>
  </div>

  {#if dashboards.length > 0}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each dashboards as dashboard (dashboard.id)}
        <a href="/viewer/{dashboard.id}" class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden block">
          <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 truncate">{dashboard.name}</h2>
            <p class="text-gray-600 mt-2 text-sm truncate">{dashboard.description || 'No description'}</p>
          </div>
          <div class="px-6 py-3 bg-gray-50 border-t">
            <span class="text-xs text-gray-500">View Dashboard &rarr;</span>
          </div>
        </a>
      {/each}
    </div>
  {:else}
    <div class="text-center py-12 px-4 sm:px-6 lg:px-8 bg-white rounded-lg shadow">
      <h2 class="text-xl font-medium text-gray-900">No dashboards shared yet</h2>
      <p class="mt-1 text-sm text-gray-500">When someone shares a dashboard with you, it will appear here.</p>
    </div>
  {/if}
</div>
