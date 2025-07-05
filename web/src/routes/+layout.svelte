<script lang="ts">
  import '../app.css';
  import { user, authLoaded } from '$lib/stores/auth';
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import EditorNavbar from '$lib/components/EditorNavbar.svelte';
  import ViewerNavbar from '$lib/components/ViewerNavbar.svelte';
    import AdminNavbar from '$lib/components/AdminNavbar.svelte';

  let showNavbar = false;
  let userRole: 'editor' | 'viewer' | 'admin' | null = null;

  // Routes where the navbar should be hidden entirely
  const noNavRoutes = ['/auth/login', '/auth/register'];

  // This will reactively update whenever the page or user store changes
  $: {
    showNavbar = $authLoaded && !!$user && !noNavRoutes.includes($page.route.id || '');
    const role = $user?.role;
    if (role === 'editor' || role === 'viewer' || role === 'admin') {
      userRole = role;
    } else {
      userRole = null;
    }
  }

  onMount(() => {
    // If auth is loaded and there's no user, redirect to login, unless on an auth page
    if ($authLoaded && !$user && !noNavRoutes.includes($page.route.id || '')) {
      goto('/auth/login');
    }
  });

  // When the user store changes (e.g., after login), this will re-evaluate
  user.subscribe(currentUser => {
    if ($authLoaded && !currentUser && !noNavRoutes.includes($page.route.id || '')) {
      goto('/auth/login');
    }
  });
</script>

<div class="flex min-h-screen bg-gray-50">
  {#if showNavbar}
    <div class="fixed h-full">
      {#if userRole === 'editor'}
        <EditorNavbar />
      {:else if userRole === 'viewer'}
        <ViewerNavbar />
      {:else if userRole === 'admin'}
        <AdminNavbar />
      {/if}
    </div>
  {/if}

  <main class="flex-1 transition-all duration-300" class:ml-64={showNavbar}>
    <div class="p-4 sm:p-6 lg:p-8">
        <slot />
    </div>
  </main>
</div>
