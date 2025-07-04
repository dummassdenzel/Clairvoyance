<script lang="ts">
  import { logout } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import CreateKpiModal from '$lib/components/CreateKpiModal.svelte';

  let showCreateKpiModal = false;

  async function handleLogout() {
    await logout();
    goto('/auth/login');
  }
</script>

<CreateKpiModal bind:show={showCreateKpiModal} />

<aside class="w-64 h-screen bg-white shadow-md flex flex-col" aria-label="Sidebar">
  <div class="p-5 border-b">
    <div class="mb-8 text-center">
    <a href="/editor/dashboards" class="text-2xl font-bold text-gray-800">Clairvoyance</a>
    <span class="text-sm text-gray-500">Editor</span>
  </div>
      <button
        on:click={() => (showCreateKpiModal = true)}
        class="w-full text-xs bg-blue-900 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full transition-colors shadow-md"
      >
        Create KPI
      </button>
  </div>


  <nav class="flex-grow px-2 py-4 space-y-2">
    <a href="/editor/dashboards" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <span class="ml-3">Dashboards</span>
    </a>
    <a href="/editor/kpis" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <span class="ml-3">KPIs</span>
    </a>
  </nav>

  <div class="p-4 border-t mt-auto">
    <button on:click={handleLogout} class="w-full text-left flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <span class="ml-3">Logout</span>
    </button>
  </div>
</aside>
