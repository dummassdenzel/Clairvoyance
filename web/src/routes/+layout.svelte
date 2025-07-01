<script lang="ts">
  import { user, logout } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  let loggingOut = false;
  async function handleLogout() {
    loggingOut = true;
    await logout();
    loggingOut = false;
    goto('/auth/login');
  }
</script>

<nav class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between">
  <div class="flex items-center gap-4">
    <a href="/" class="font-bold text-lg text-blue-700">Clairvoyance</a>
    <a href="/dashboards" class="text-gray-700 hover:text-blue-600">Dashboards</a>
    <a href="/kpis" class="text-gray-700 hover:text-blue-600">KPIs</a>
  </div>
  <div class="flex items-center gap-4">
    {#if $user}
      <span class="text-sm text-gray-600">{$user.email} ({$user.role})</span>
      <button class="btn btn-primary px-3 py-1" on:click={handleLogout} disabled={loggingOut}>
        {loggingOut ? 'Logging out...' : 'Logout'}
      </button>
    {:else}
      <a href="/auth/login" class="btn btn-primary px-3 py-1">Login</a>
      <a href="/auth/register" class="btn btn-outline px-3 py-1">Register</a>
    {/if}
  </div>
</nav>

<slot />

<style>
  .btn { @apply rounded bg-blue-600 text-white font-semibold hover:bg-blue-700 transition disabled:opacity-50; }
  .btn-outline { @apply border border-blue-600 text-blue-600 bg-white hover:bg-blue-50; }
</style>
