<script lang="ts">
  import { onMount } from 'svelte';
  import { authStore } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import RegisterForm from '$lib/components/auth/RegisterForm.svelte';
  
  // If already authenticated, redirect to dashboard
  onMount(() => {
    if ($authStore.isAuthenticated) {
      goto('/dashboard');
    }
  });
</script>

<div class="auth-page">
  <div class="auth-container">
    <div class="auth-header">
      <h1>Create Your Account</h1>
      <p>Join Clairvoyance to start tracking your KPIs</p>
    </div>
    
    <RegisterForm />
    
    <div class="auth-footer">
      <p>Already have an account? <a href="/login">Sign in</a></p>
    </div>
  </div>
</div>

<style>
  .auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    padding: 1rem;
  }
  
  .auth-container {
    width: 100%;
    max-width: 500px;
  }
  
  .auth-header {
    text-align: center;
    margin-bottom: 2rem;
  }
  
  .auth-header h1 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1.75rem;
  }
  
  .auth-header p {
    color: #6c757d;
    margin: 0;
  }
  
  .auth-footer {
    text-align: center;
    margin-top: 2rem;
    color: #6c757d;
  }
  
  .auth-footer a {
    color: #4a90e2;
    text-decoration: none;
  }
  
  .auth-footer a:hover {
    text-decoration: underline;
  }
</style> 