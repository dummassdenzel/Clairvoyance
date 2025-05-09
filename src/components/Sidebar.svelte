<script>
    import { Folder, Clock, FileDashed, TrashSimple } from 'phosphor-svelte';
    let activeItem = "Create API";
  
    const menuItems = [
      { name: "All KPI's", icon: Folder },
      { name: "Recent files", icon: Clock },
      { name: "Draft", icon: FileDashed },
      { name: "Trash", icon: TrashSimple }
    ];
  </script>
  
  <aside class="w-65 h-screen bg-white text-white flex flex-col py-3 border-r-[1px] border-gray-300">
    <div class="px-6 mt-0.1 mb-8">
        <img src="logo.png" alt="Clairvoyance Logo" class="w-50 h-7" />
    </div>

    <div class="px-5">
      <button class="bg-blue-900 hover:bg-blue-950 text-white font-semibold py-1.5 px-15 rounded-full inline-flex items-center">
        <i class="ph ph-plus px-1"></i> 
        Create KPI
      </button>
    </div>

    <div class="px-7">
      <hr class="w-[200px] border-t-[1px] border-gray-300 my-6"/>
    </div>
    
    
    <nav class="flex-grow">
      {#each menuItems as item}
        <button 
          class={`flex items-center gap-3 w-full px-6 py-3 text-left transition-colors duration-200
                  ${activeItem === item.name 
                    ? 'bg-white/10 text-black border-l-4 border-blue-800' 
                    : 'text-blue-950 hover:bg-blue-200 hover:text-black'}`}
          on:click={() => activeItem = item.name}
        >
          <svelte:component this={item.icon} class="w-5 h-5" />
          <span>{item.name}</span>
        </button>
      {/each}
    </nav>

  </aside>
  