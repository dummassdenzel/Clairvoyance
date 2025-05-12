<script>
    import Sidebar from "../components/Sidebar.svelte";
    import Header from "../components/Header.svelte";
    import KpiCard from "../components/KpiCard.svelte";
</script>

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar (left) -->
    <Sidebar />
  
    <!-- Main area: header on top, content below -->
    <div class="flex flex-col flex-1">
      
      <!-- Top Header -->
      <Header />
  
      <!-- Main content area -->
      <main class="flex-1 overflow-auto bg-white p-6">
        <KpiCard />
        
      </main>
    </div>
  </div>
