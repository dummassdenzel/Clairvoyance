<script lang="ts">
  import { authStore } from '$lib/services/auth';
  import { goto } from '$app/navigation';
</script>

<div class="home-page">
  <div class="hero-section">
    <div class="hero-content">
      <h1>Clairvoyance</h1>
      <h2>KPI Management & Visualization Platform</h2>
      <p>Track, analyze, and visualize your key performance indicators with ease</p>
      
      {#if $authStore.isAuthenticated}
        <div class="cta-buttons">
          <button class="btn btn-primary" on:click={() => goto('/dashboard')}>
            Go to Dashboard
          </button>
          <button class="btn btn-secondary" on:click={() => goto('/kpis')}>
            Manage KPIs
          </button>
        </div>
      {:else}
        <div class="cta-buttons">
          <button class="btn btn-primary" on:click={() => goto('/login')}>
            Sign In
          </button>
          <button class="btn btn-secondary" on:click={() => goto('/register')}>
            Register
          </button>
        </div>
      {/if}
    </div>
  </div>
  
  <div class="features-section">
    <div class="container">
      <h2>Key Features</h2>
      
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">📊</div>
          <h3>Intuitive Dashboards</h3>
          <p>Create custom dashboards with drag-and-drop widgets to visualize your most important metrics</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📈</div>
          <h3>KPI Tracking</h3>
          <p>Define and track your key performance indicators with targets, measurements and trends</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">📱</div>
          <h3>Responsive Design</h3>
          <p>Access your dashboards from any device with our mobile-friendly interface</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">🔒</div>
          <h3>Secure Access</h3>
          <p>Role-based permissions ensure your data is only accessible to authorized users</p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .home-page {
    width: 100%;
  }
  
  .hero-section {
    background: linear-gradient(135deg, #3a7bd5, #3a6073);
    color: white;
    padding: 5rem 1rem;
    text-align: center;
    position: relative;
  }
  
  .hero-content {
    max-width: 800px;
    margin: 0 auto;
  }
  
  .hero-content h1 {
    font-size: 3rem;
    margin: 0;
    font-weight: 800;
  }
  
  .hero-content h2 {
    font-size: 1.5rem;
    margin: 0.5rem 0 1.5rem;
    font-weight: 600;
    opacity: 0.9;
  }
  
  .hero-content p {
    font-size: 1.2rem;
    margin-bottom: 2.5rem;
    opacity: 0.9;
  }
  
  .cta-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
  }
  
  .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
  }
  
  .btn-primary {
    background-color: #fff;
    color: #3a7bd5;
  }
  
  .btn-primary:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
  }
  
  .btn-secondary {
    background-color: transparent;
    border: 2px solid white;
    color: white;
  }
  
  .btn-secondary:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
  }
  
  .features-section {
    padding: 5rem 1rem;
    background-color: #f8f9fa;
  }
  
  .container {
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .features-section h2 {
    text-align: center;
    margin-bottom: 3rem;
    font-size: 2rem;
    color: #333;
  }
  
  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
  }
  
  .feature-card {
    background-color: white;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s, box-shadow 0.3s;
  }
  
  .feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
  }
  
  .feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }
  
  .feature-card h3 {
    margin: 0 0 1rem 0;
    color: #333;
  }
  
  .feature-card p {
    color: #6c757d;
    margin: 0;
    font-size: 0.95rem;
  }
  
  @media (max-width: 768px) {
    .hero-content h1 {
      font-size: 2.5rem;
    }
    
    .hero-content h2 {
      font-size: 1.25rem;
    }
    
    .hero-content p {
      font-size: 1rem;
    }
    
    .cta-buttons {
      flex-direction: column;
      align-items: center;
    }
    
    .btn {
      width: 100%;
      max-width: 300px;
    }
  }
</style>
