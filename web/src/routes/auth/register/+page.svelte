<script lang="ts">
  import { goto } from '$app/navigation';
  
  let username = '';
  let email = '';
  let password = '';
  let confirmPassword = '';
  let isLoading = false;
  let errorMessage = '';
  
  $: passwordsMatch = password === confirmPassword;
</script>

<div class="bg-white p-8 rounded-lg shadow-md max-w-md mx-auto my-8">
  <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Create Account</h2>
  
  {#if errorMessage}
    <div class="bg-red-50 text-red-600 p-3 rounded-md mb-4 text-sm">
      {errorMessage}
    </div>
  {/if}
  
  <form class="space-y-6">
    <div>
      <label for="username" class="block mb-2 text-sm font-medium text-gray-700">Username</label>
      <input 
        type="text" 
        id="username" 
        bind:value={username} 
        required 
        disabled={isLoading}
        placeholder="Choose a username"
        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
      />
    </div>
    
    <div>
      <label for="email" class="block mb-2 text-sm font-medium text-gray-700">Email</label>
      <input 
        type="email" 
        id="email" 
        bind:value={email} 
        required 
        disabled={isLoading}
        placeholder="Enter your email"
        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
      />
    </div>
    
    <div>
      <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Password</label>
      <input 
        type="password" 
        id="password" 
        bind:value={password} 
        required 
        disabled={isLoading}
        placeholder="Choose a password"
        minlength="8"
        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
      />
    </div>
    
    <div>
      <label for="confirmPassword" class="block mb-2 text-sm font-medium text-gray-700">Confirm Password</label>
      <input 
        type="password" 
        id="confirmPassword" 
        bind:value={confirmPassword} 
        required 
        disabled={isLoading}
        placeholder="Confirm your password"
        class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed {(!passwordsMatch && confirmPassword !== '') ? 'border-red-500' : 'border-gray-300'}"
      />
      {#if !passwordsMatch && confirmPassword !== ''}
        <small class="block mt-1 text-xs text-red-600">Passwords do not match</small>
      {/if}
    </div>
    
    <button 
      type="submit" 
      class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-blue-300 disabled:cursor-not-allowed"
      disabled={isLoading || !passwordsMatch || password === ''}
    >
      {#if isLoading}
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processing...
      {:else}
        Register
      {/if}
    </button>
  </form>
</div>