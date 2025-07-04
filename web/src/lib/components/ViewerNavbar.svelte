<script lang="ts">
  import { logout } from '$lib/stores/auth';
  import { goto } from '$app/navigation';

  async function handleLogout() {
    await logout();
    goto('/auth/login');
  }
</script>

<aside class="w-64 h-screen bg-white shadow-md flex flex-col" aria-label="Sidebar">
  <div class="p-4 border-b">
    <a href="/viewer" class="text-2xl font-bold text-gray-800">Clairvoyance</a>
    <span class="text-sm text-gray-500">Viewer</span>
  </div>

  <nav class="flex-1 px-2 py-4 space-y-2">
    <a href="/viewer" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <span class="ml-3">Dashboards</span>
    </a>
  </nav>

  <div class="p-4 border-t">
    <button on:click={handleLogout} class="w-full text-left flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <span class="ml-3">Logout</span>
    </button>
  </div>
</aside>
