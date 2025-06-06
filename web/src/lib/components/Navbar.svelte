<script lang="ts">
  import { authStore, logout } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  
  let isMenuOpen = false;
  
  function toggleMenu() {
    isMenuOpen = !isMenuOpen;
  }
  
  async function handleLogout() {
    await logout();
    goto('/login'); // Updated redirect
  }
</script>

<nav class="bg-gray-800 text-white shadow-md">
  <div class="container mx-auto px-4">
    <div class="flex items-center justify-between h-16">
      <!-- Brand -->
      <div class="flex-shrink-0">
        <a href="/" class="text-2xl font-bold hover:text-gray-300">Clairvoyance</a>
      </div>

      <!-- Desktop Menu -->
      <div class="hidden md:flex md:items-center md:space-x-4">
        {#if $authStore.isAuthenticated}
          <a href="/user/dashboard" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Dashboard</a>
          <a href="/user/kpis" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">KPIs</a>
          {#if $authStore.user?.role === 'admin'}
            <a href="/admin/dashboard" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Admin</a>
          {/if}
          
          <div class="relative group">
            <button class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 focus:outline-none flex items-center">
              <span>Welcome, {$authStore.user?.username}</span>
              <svg class="ml-1 h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
            </button>
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
              <a href="/user/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
              <button on:click={handleLogout} class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:outline-none">Logout</button>
            </div>
          </div>
        {:else}
          <a href="/login" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Login</a>
          <a href="/register" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">Register</a>
        {/if}
      </div>

      <!-- Mobile Menu Button -->
      <div class="md:hidden flex items-center">
        <button on:click={toggleMenu} aria-label="Toggle menu" aria-expanded={isMenuOpen} aria-controls="mobile-menu-content" class="p-2 rounded-md hover:bg-gray-700 focus:outline-none focus:bg-gray-700">
          {#if isMenuOpen}
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          {:else}
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          {/if}
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu-content" class="md:hidden" class:block={isMenuOpen} class:hidden={!isMenuOpen}>
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
      {#if $authStore.isAuthenticated}
        <a href="/user/dashboard" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">Dashboard</a>
        <a href="/user/kpis" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">KPIs</a>
        {#if $authStore.user?.role === 'admin'}
          <a href="/admin/dashboard" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">Admin</a>
        {/if}
        <div class="border-t border-gray-700 my-2"></div>
        <div class="px-3 py-2">
          <p class="text-base font-medium">Welcome, {$authStore.user?.username}</p>
        </div>
        <a href="/user/profile" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">Settings</a>
        <button on:click={handleLogout} class="block w-full text-left px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 focus:outline-none">Logout</button>
      {:else}
        <a href="/login" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">Login</a>
        <a href="/register" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">Register</a>
      {/if}
    </div>
  </div>
</nav>