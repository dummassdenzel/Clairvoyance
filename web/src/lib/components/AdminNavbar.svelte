<script lang="ts">
  import { user, logout } from '$lib/stores/auth';
  import { goto } from '$app/navigation';

  async function handleLogout() {
    await logout();
    goto('/auth/login');
  }
</script>

<aside class="w-64 h-screen bg-white shadow-md flex flex-col" aria-label="Sidebar">
  <div class="px-3 py-5 border-b">
    <div class="mb-5 text-center">
      <a href="/admin" class="flex items-center justify-center text-2xl font-bold text-gray-800">
        <img src="/clairvoyance-logo.png" alt="Clairvoyance Logo" class="h-8" />
        <span>Clairvoyance</span>
      </a>
      <span class="text-sm text-gray-500">Admin</span>
    </div>
  </div>

  <nav class="flex-grow px-2 py-4 space-y-2">
    <a href="/admin" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-6-6h6a6 6 0 00-6 6"></path></svg>
      <span class="ml-3">User Management</span>
    </a>
    <a href="/editor/dashboards" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
      <span class="ml-3">Dashboards</span>
    </a>
  </nav>

  {#if $user && $user.email}
    <div class="flex items-center ml-3 px-4 mb-3">
      <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-sm font-medium text-white">
        {$user.email.charAt(0).toUpperCase()}
      </div>
      <span class="ml-3 block truncate text-sm text-gray-800" title={$user.email}>
        {$user.email}
      </span>
    </div>
  {/if}
  <div class="p-4 border-t mt-auto">
    <button on:click={handleLogout} class="w-full text-left flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
      <svg class="w-6 h-6 text-gray-500 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
      <span class="ml-3">Logout</span>
    </button>
  </div>
</aside>
