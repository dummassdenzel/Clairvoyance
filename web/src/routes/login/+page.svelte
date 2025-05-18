<script lang="ts">
  import { onMount } from 'svelte';
  import { authStore } from '$lib/services/auth';
  import { goto } from '$app/navigation';
  import LoginForm from '$lib/components/auth/LoginForm.svelte';
  
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
      <h1>Welcome to Clairvoyance</h1>
      <p>Sign in to access your dashboards and KPIs</p>
    </div>
    
    <LoginForm />
    
    <div class="auth-footer">
      <p>Don't have an account? <a href="/register">Register</a></p>
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