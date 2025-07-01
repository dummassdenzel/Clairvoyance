<script lang="ts">
  import { goto } from '$app/navigation';
  
  let username = '';
  let password = '';
  let isLoading = false;
  let errorMessage = '';
  
</script>

<div class="bg-white p-8 rounded-lg shadow-md max-w-md mx-auto my-8">
  <h2 class="text-center mb-6 text-gray-800 text-2xl font-semibold">Sign In</h2>
  
  {#if errorMessage}
    <div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm">
      {errorMessage}
    </div>
  {/if}
  
  <form >
    <div class="mb-4">
      <label for="username" class="block mb-2 font-medium text-gray-700">Username</label>
      <input 
        type="text" 
        id="username" 
        bind:value={username} 
        required 
        disabled={isLoading}
        placeholder="Enter your username"
        class="w-full px-3 py-3 border border-gray-300 rounded-md text-base focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:bg-gray-100 disabled:cursor-not-allowed"
      />
    </div>
    
    <div class="mb-4">
      <label for="password" class="block mb-2 font-medium text-gray-700">Password</label>
      <input 
        type="password" 
        id="password" 
        bind:value={password} 
        required 
        disabled={isLoading}
        placeholder="Enter your password"
        class="w-full px-3 py-3 border border-gray-300 rounded-md text-base focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:bg-gray-100 disabled:cursor-not-allowed"
      />
    </div>
    
    <button 
      type="submit" 
      disabled={isLoading}
      class="w-full px-3 py-3 bg-blue-500 text-white border-none rounded-md text-base font-medium cursor-pointer transition-colors duration-200 hover:bg-blue-600 disabled:bg-blue-300 disabled:cursor-not-allowed"
    >
      {#if isLoading}
        Loading...
      {:else}
        Sign In
      {/if}
    </button>
  </form>
</div> 