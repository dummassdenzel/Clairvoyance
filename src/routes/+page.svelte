<script>
    import Sidebar from "../components/Sidebar.svelte";
    import Header from "../components/Header.svelte";
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
        <!-- Replace with your actual page content -->
        <h1 class="text-2xl font-bold mb-4">Welcome to Clairvoyance</h1>
        <p class="text-gray-600">This is where your dashboard content goes.</p>
      </main>
    </div>
  </div>
