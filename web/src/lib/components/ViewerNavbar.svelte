<script lang="ts">
  import { logout } from '$lib/stores/auth';
  import { goto } from '$app/navigation';

  async function handleLogout() {
    await logout();
    goto('/auth/login');
  }
</script>

<nav class="bg-gray-800 text-white shadow-md">
  <div class="container mx-auto px-6 py-3 flex justify-between items-center">
    <a href="/viewer" class="font-bold text-xl">Clairvoyance <span class="font-normal text-base text-gray-400">- Viewer</span></a>
    <div class="flex items-center space-x-4">
      <a href="/viewer" class="px-3 py-2 rounded hover:bg-gray-700 transition">Dashboards</a>
      <button on:click={handleLogout} class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">
        Logout
      </button>
    </div>
  </div>
</nav>
