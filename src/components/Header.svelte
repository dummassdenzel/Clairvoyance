<script>
    import { Menu } from '@lucide/svelte';
    import { isSidebarMinimized } from "../Stores/sidebar";
</script>
  
  <header class="w-full h-14 bg-white border-b border-gray-300 flex items-center justify-between px-4 shadow-sm">
    <!-- Left section: Menu Icon -->
    <div class="flex items-center gap-3">
      <button on:click={() => isSidebarMinimized.update(v => !v)}>
        <Menu color="#1c398e" />
      </button>
    </div>
  
    <!-- Right section: Search + Profile -->
    <div class="flex items-center gap-6">
      
      <!-- Search Bar -->
      <div class="relative">
        <input 
          type="text" 
          placeholder="Search here..." 
          class="w-full py-1.5 pr-10 pl-4 bg-blue-100 bg-opacity-10 rounded-full text-blue-900 placeholder-gray-400 outline-gray-400 focus:outline-blue-300"
        />
        <i class="ph ph-magnifying-glass text-blue-800 text-lg absolute right-3 top-1/2 transform -translate-y-1/2"></i>
      </div>
  
      <!-- User Profile -->
      <div class="flex items-center gap-2">
        <div class="flex flex-col text-right leading-tight">
          <span class="font-medium">User Name</span>
          <span class="text-xs text-gray-400">Glue</span>
        </div>
        <div class="p-0.5 rounded-full border-2 border-blue-400 border-opacity-50">
          <img src="user.jpg" alt="User" class="w-9 h-9 rounded-full object-cover" />
        </div>
      </div>
  
    </div>
  </header>
  