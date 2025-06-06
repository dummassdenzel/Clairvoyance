<script lang="ts">
  import { register } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  
  let username = '';
  let email = '';
  let password = '';
  let confirmPassword = '';
  let isLoading = false;
  let errorMessage = '';
  
  $: passwordsMatch = password === confirmPassword;
  
  async function handleSubmit() {
    errorMessage = '';
    
    if (!passwordsMatch) {
      errorMessage = 'Passwords do not match';
      return;
    }
    
    isLoading = true;
    
    try {
      const result = await register(username, email, password);
      if (result.success) {
        goto('/dashboard');
      } else {
        errorMessage = result.message || 'Registration failed. Please try again.';
      }
    } catch (error) {
      console.error('Registration error:', error);
      errorMessage = error instanceof Error ? error.message : 'An unexpected error occurred';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="register-form">
  <h2>Create Account</h2>
  
  {#if errorMessage}
    <div class="error-message">
      {errorMessage}
    </div>
  {/if}
  
  <form on:submit|preventDefault={handleSubmit}>
    <div class="form-group">
      <label for="username">Username</label>
      <input 
        type="text" 
        id="username" 
        bind:value={username} 
        required 
        disabled={isLoading}
        placeholder="Choose a username"
      />
    </div>
    
    <div class="form-group">
      <label for="email">Email</label>
      <input 
        type="email" 
        id="email" 
        bind:value={email} 
        required 
        disabled={isLoading}
        placeholder="Enter your email"
      />
    </div>
    
    <div class="form-group">
      <label for="password">Password</label>
      <input 
        type="password" 
        id="password" 
        bind:value={password} 
        required 
        disabled={isLoading}
        placeholder="Choose a password"
        minlength="8"
      />
    </div>
    
    <div class="form-group">
      <label for="confirmPassword">Confirm Password</label>
      <input 
        type="password" 
        id="confirmPassword" 
        bind:value={confirmPassword} 
        required 
        disabled={isLoading}
        placeholder="Confirm your password"
        class:error={!passwordsMatch && confirmPassword}
      />
      {#if !passwordsMatch && confirmPassword}
        <small class="password-mismatch">Passwords do not match</small>
      {/if}
    </div>
    
    <button type="submit" class="btn-primary" disabled={isLoading || !passwordsMatch}>
      {#if isLoading}
        Loading...
      {:else}
        Register
      {/if}
    </button>
  </form>
</div>

<style>
  .register-form {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    margin: 2rem auto;
  }
  
  h2 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #333;
  }
  
  .form-group {
    margin-bottom: 1rem;
  }
  
  label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #555;
  }
  
  input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
  }
  
  input:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
  }
  
  input.error {
    border-color: #e53935;
  }
  
  .btn-primary {
    width: 100%;
    padding: 0.75rem;
    background-color: #4a90e2;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-primary:hover {
    background-color: #3a7bc8;
  }
  
  .btn-primary:disabled {
    background-color: #96bff0;
    cursor: not-allowed;
  }
  
  .error-message {
    background-color: #fff0f0;
    color: #e53935;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
  }
  
  .password-mismatch {
    color: #e53935;
    font-size: 0.8rem;
    margin-top: 0.25rem;
    display: block;
  }
</style> 