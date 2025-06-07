<!-- Viewer Layout -->
<script lang="ts">
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';
  import { authStore, logout } from '$lib/stores/auth';
  import type { AuthenticatedUser } from '$lib/stores/auth';
  import { onMount } from 'svelte';

  let user: AuthenticatedUser | null = null;
  let loading = true;
  let menuOpen = false;

  // Subscribe to auth store
  authStore.subscribe(state => {
    user = state.user;
    loading = false;
  });

  async function handleLogout() {
    try {
      await logout();
      goto('/auth/login');
    } catch (error) {
      console.error('Logout failed:', error);
    }
  }

  function toggleMenu() {
    menuOpen = !menuOpen;
  }

  function closeMenu(event: MouseEvent) {
    const target = event.target as HTMLElement;
    const button = document.getElementById('user-menu-button');
    const menu = document.getElementById('user-menu');
    
    if (button && menu && !button.contains(target) && !menu.contains(target)) {
      menuOpen = false;
    }
  }

  onMount(() => {
    document.addEventListener('click', closeMenu);
    return () => {
      document.removeEventListener('click', closeMenu);
    };
  });
</script>

<div class="min-h-screen bg-gray-100">
  <!-- Navigation -->
  <nav class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex">
          <div class="flex-shrink-0 flex items-center">
            <a href="/app/viewer" class="text-xl font-bold text-indigo-600">
              Clairvoyance
            </a>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
            <a
              href="/app/viewer/dashboard"
              class="{$page.url.pathname.startsWith('/app/viewer/dashboard') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
            >
              Dashboards
            </a>
            <a
              href="/app/viewer/reports"
              class="{$page.url.pathname.startsWith('/app/viewer/reports') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
            >
              Reports
            </a>
          </div>
        </div>
        <div class="hidden sm:ml-6 sm:flex sm:items-center">
          <!-- Profile dropdown -->
          <div class="ml-3 relative">
            <div>
              <button
                type="button"
                class="bg-white rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                id="user-menu-button"
                aria-expanded={menuOpen}
                aria-haspopup="true"
                on:click={toggleMenu}
              >
                <span class="sr-only">Open user menu</span>
                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                  <span class="text-indigo-800 font-medium">
                    {user?.username?.[0]?.toUpperCase() || '?'}
                  </span>
                </div>
              </button>
            </div>
            <!-- Dropdown menu -->
            <div
              class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
              role="menu"
              aria-orientation="vertical"
              aria-labelledby="user-menu-button"
              tabindex="-1"
              id="user-menu"
              class:hidden={!menuOpen}
            >
              <a
                href="#"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                role="menuitem"
                tabindex="-1"
                on:click|preventDefault={handleLogout}
              >
                Sign out
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Page Content -->
  <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <slot />
  </main>
</div> 