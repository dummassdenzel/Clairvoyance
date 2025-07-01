<script lang="ts">
  import { authError, login, user } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  let email = '';
  let password = '';
  let loading = false;

  async function handleSubmit(event: Event) {
    event.preventDefault();
    loading = true;
    const ok = await login(email, password);
    loading = false;
    if (ok) goto('/');
  }
</script>

<div class="flex min-h-screen items-center justify-center bg-gray-50">
  <form class="bg-white p-8 rounded shadow w-full max-w-sm space-y-4" on:submit={handleSubmit}>
    <h1 class="text-xl font-bold mb-2">Login</h1>
    <input class="border rounded px-3 py-2 w-full" type="email" placeholder="Email" bind:value={email} required />
    <input class="border rounded px-3 py-2 w-full" type="password" placeholder="Password" bind:value={password} required />
    {#if $authError}
      <div class="text-red-500 text-sm">{$authError}</div>
    {/if}
    <button class="bg-blue-600 text-white rounded px-4 py-2 font-semibold w-full hover:bg-blue-700 transition disabled:opacity-50" type="submit" disabled={loading}>{loading ? 'Logging in...' : 'Login'}</button>
    <div class="text-sm text-center mt-2">
      <a href="/auth/register" class="text-blue-600 hover:underline">Register</a>
    </div>
  </form>
</div> 