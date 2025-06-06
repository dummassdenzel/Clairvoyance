<script lang="ts">
  import { onMount } from 'svelte';
  import { authStore } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import RegisterForm from '$lib/components/auth/RegisterForm.svelte';
  
  // If already authenticated, redirect to dashboard
  onMount(() => {
    if ($authStore.isAuthenticated) {
      // Consider redirecting to a role-specific dashboard if applicable
      // e.g., based on $authStore.user?.role
      goto('/user/dashboard'); // Defaulting to /user/dashboard as per previous patterns
    }
  });
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
  <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-lg">
    <div class="text-center mb-8">
      <h1 class="mb-2 text-2xl font-semibold text-gray-800">Create Your Account</h1>
      <p class="text-gray-600">Join Clairvoyance to start tracking your KPIs</p>
    </div>
    
    <RegisterForm />
    
    <div class="text-center mt-8 text-sm text-gray-600">
      <p>Already have an account? <a href="/login" class="text-blue-600 hover:underline">Sign in</a></p>
    </div>
  </div>
</div>