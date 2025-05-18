<script lang="ts">
  import { authStore, logout } from '$lib/services/auth';
  import { goto } from '$app/navigation';
  
  let isMenuOpen = false;
  
  function toggleMenu() {
    isMenuOpen = !isMenuOpen;
  }
  
  async function handleLogout() {
    await logout();
    goto('/');
  }
</script>

<nav class="navbar">
  <div class="navbar-brand">
    <a href="/" class="logo">Clairvoyance</a>
    <button class="menu-toggle" on:click={toggleMenu} aria-label="Toggle menu">
      <span class="hamburger"></span>
    </button>
  </div>
  
  <div class="navbar-menu" class:is-active={isMenuOpen}>
    {#if $authStore.isAuthenticated}
      <div class="navbar-start">
        <a href="/dashboard" class="navbar-item">Dashboard</a>
        <a href="/kpis" class="navbar-item">KPIs</a>
        {#if $authStore.user?.role === 'admin'}
          <a href="/admin" class="navbar-item">Admin</a>
        {/if}
      </div>
      
      <div class="navbar-end">
        <div class="user-info">
          <span>Welcome, {$authStore.user?.username}</span>
          <div class="dropdown">
            <button class="dropdown-trigger">Profile</button>
            <div class="dropdown-menu">
              <a href="/profile" class="dropdown-item">Settings</a>
              <button on:click={handleLogout} class="dropdown-item">Logout</button>
            </div>
          </div>
        </div>
      </div>
    {:else}
      <div class="navbar-end">
        <a href="/login" class="navbar-item">Login</a>
        <a href="/register" class="navbar-item">Register</a>
      </div>
    {/if}
  </div>
</nav>

<style>
  .navbar {
    background-color: #2c3e50;
    padding: 0.5rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
  }
  
  .navbar-brand {
    display: flex;
    align-items: center;
  }
  
  .logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
  }
  
  .menu-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
  }
  
  .hamburger {
    display: block;
    position: relative;
    width: 24px;
    height: 2px;
    background-color: white;
  }
  
  .hamburger::before,
  .hamburger::after {
    content: '';
    position: absolute;
    width: 24px;
    height: 2px;
    background-color: white;
    transition: transform 0.3s;
  }
  
  .hamburger::before {
    top: -6px;
  }
  
  .hamburger::after {
    bottom: -6px;
  }
  
  .navbar-menu {
    display: flex;
    justify-content: space-between;
    flex-grow: 1;
    align-items: center;
  }
  
  .navbar-start,
  .navbar-end {
    display: flex;
    align-items: center;
  }
  
  .navbar-item {
    color: #ecf0f1;
    text-decoration: none;
    padding: 0.5rem 1rem;
    margin: 0 0.25rem;
    border-radius: 4px;
    transition: background-color 0.2s;
  }
  
  .navbar-item:hover {
    background-color: #34495e;
    color: white;
  }
  
  .user-info {
    display: flex;
    align-items: center;
    position: relative;
  }
  
  .user-info span {
    margin-right: 1rem;
  }
  
  .dropdown {
    position: relative;
  }
  
  .dropdown-trigger {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    min-width: 150px;
    z-index: 10;
  }
  
  .dropdown:hover .dropdown-menu {
    display: block;
  }
  
  .dropdown-item {
    display: block;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    text-align: left;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
  }
  
  .dropdown-item:hover {
    background-color: #f8f9fa;
  }
  
  @media (max-width: 768px) {
    .navbar {
      flex-wrap: wrap;
    }
    
    .navbar-brand {
      width: 100%;
      justify-content: space-between;
    }
    
    .menu-toggle {
      display: block;
    }
    
    .navbar-menu {
      display: none;
      width: 100%;
      flex-direction: column;
      align-items: flex-start;
      padding: 1rem 0;
    }
    
    .navbar-menu.is-active {
      display: flex;
    }
    
    .navbar-start,
    .navbar-end {
      flex-direction: column;
      width: 100%;
    }
    
    .navbar-item {
      width: 100%;
      padding: 0.75rem 0;
    }
    
    .user-info {
      flex-direction: column;
      align-items: flex-start;
      width: 100%;
      padding: 0.5rem 0;
    }
    
    .user-info span {
      margin-bottom: 0.5rem;
    }
    
    .dropdown {
      width: 100%;
    }
    
    .dropdown-trigger {
      width: 100%;
      text-align: left;
    }
    
    .dropdown-menu {
      position: static;
      box-shadow: none;
      display: none;
    }
    
    .dropdown:hover .dropdown-menu {
      display: none;
    }
    
    .dropdown-trigger:focus + .dropdown-menu,
    .dropdown-trigger:active + .dropdown-menu {
      display: block;
    }
  }
</style> 