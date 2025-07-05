<script lang="ts">
  import { authError, login, user } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';

  let email = '';
  let password = '';
  let loading = false;

  async function handleSubmit() {
    loading = true;
    const ok = await login(email, password);
    loading = false;

    if (ok && $user) {
      const redirectUrl = $page.url.searchParams.get('redirect');
      if (redirectUrl) {
        goto(redirectUrl);
        return;
      }

      // Role-based redirect
      if ($user.role === 'editor' || $user.role === 'admin') {
        goto('/editor/dashboards');
      } else if ($user.role === 'viewer') {
        goto('/viewer/dashboards');
      } else {
        // Fallback for any other roles or just in case
        goto('/viewer/dashboards');
      }
    }
  }
</script>

<div class="min-h-screen bg-gray-100 flex flex-col justify-center items-center p-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-gray-800">Clairvoyance</h1>
      <p class="text-gray-500">Welcome back! Please sign in to your account.</p>
    </div>

    <form class="bg-white p-8 rounded-lg shadow-lg space-y-6" on:submit|preventDefault={handleSubmit}>
      
      <div>
        <label for="email" class="text-sm font-medium text-gray-700 sr-only">Email</label>
        <input 
          id="email" 
          class="block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
          type="email" 
          placeholder="Email" 
          bind:value={email} 
          required 
        />
      </div>

      <div>
        <label for="password" class="text-sm font-medium text-gray-700 sr-only">Password</label>
        <input 
          id="password" 
          class="block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
          type="password" 
          placeholder="Password" 
          bind:value={password} 
          required 
        />
      </div>

      {#if $authError}
        <div class="flex items-center text-red-600 text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>{$authError}</span>
        </div>
      {/if}

      <button 
        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed" 
        type="submit" 
        disabled={loading}
      >
        {loading ? 'Signing in...' : 'Sign In'}
      </button>

      <div class="text-sm text-center text-gray-600">
        Don't have an account?
        <a href="/auth/register" class="font-medium text-blue-600 hover:text-blue-500">
          Register here
        </a>
      </div>
    </form>
  </div>
</div> 