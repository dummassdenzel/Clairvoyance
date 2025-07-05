<script lang="ts">
  import { authError, register } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  let email = '';
  let password = '';
  let role = 'viewer';
  let loading = false;

  async function handleSubmit(event: Event) {
    event.preventDefault();
    loading = true;
    const ok = await register(email, password, role);
    loading = false;
    if (ok) goto('/auth/login');
  }
</script>

<div class="min-h-screen bg-gray-100 flex flex-col justify-center items-center p-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-gray-800">Clairvoyance</h1>
      <p class="text-gray-500">Create your account to get started.</p>
    </div>

    <form class="bg-white p-8 rounded-lg shadow-lg space-y-6" on:submit={handleSubmit}>
      
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
      
      <div>
        <label for="role" class="text-sm font-medium text-gray-700 sr-only">Role</label>
        <select 
          id="role" 
          class="block w-full px-4 py-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
          bind:value={role} 
          required
        >
          <option value="viewer">Register as a Viewer</option>
          <option value="editor">Register as an Editor</option>
        </select>
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
        {loading ? 'Creating account...' : 'Create Account'}
      </button>

      <div class="text-sm text-center text-gray-600">
        Already have an account?
        <a href="/auth/login" class="font-medium text-blue-600 hover:text-blue-500">
          Sign in
        </a>
      </div>
    </form>
  </div>
</div>