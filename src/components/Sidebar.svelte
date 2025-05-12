<script>
  import { Folder, Clock, FileDashed, TrashSimple } from 'phosphor-svelte';
  import { isSidebarMinimized } from "../Stores/sidebar";

  let activeItem = "Create API";
  const menuItems = [
    { name: "All KPI's", icon: Folder },
    { name: "Recent files", icon: Clock },
    { name: "Draft", icon: FileDashed },
    { name: "Trash", icon: TrashSimple }
  ];
</script>

<aside class={`transition-all duration-300 bg-white text-white flex flex-col py-3 border-r border-gray-300 
  ${$isSidebarMinimized ? 'w-20' : 'w-65'}`}>
  
  <!-- Logo -->
  <div class="flex items-center ${$isSidebarMinimized ? 'justify-center' : 'justify-start'} px-6 mb-8">
    <img src={$isSidebarMinimized ? '/logo2.png' : '/logo.png'} alt="Clairvoyance Logo" class={`${$isSidebarMinimized ? 'w-8 h-8' : 'w-50 h-7'}`} />
  </div>

  <!-- Create KPI Button -->
  <div class="px-6 mb-4">
    <button class={`bg-blue-900 hover:bg-blue-950 text-white font-semibold py-1.5 rounded-full inline-flex items-center 
      ${$isSidebarMinimized ? 'px-2 w-auto justify-center' : 'px-4 w-53 justify-center'}`}>
      <i class="ph ph-plus"></i>
      {#if !$isSidebarMinimized}
        <span class="ml-2">Create KPI</span>
      {/if}
    </button>
  </div>

  <hr class="border-t-[1px] border-gray-300 my-2 mx-4" />

  <!-- Navigation -->
  <nav class="flex-grow">
    {#each menuItems as item}
      <button
        class={`flex items-center w-full px-4 py-3 text-left transition-colors duration-200
        ${$isSidebarMinimized ? 'justify-center' : 'gap-3 justify-start'}
        ${activeItem === item.name 
          ? 'bg-white/10 text-black border-l-4 border-blue-800' 
          : 'text-blue-950 hover:bg-blue-200 hover:text-black'}`}
        on:click={() => activeItem = item.name}
      >
        <svelte:component this={item.icon} class="w-5 h-5" />
        {#if !$isSidebarMinimized}
          <span>{item.name}</span>
        {/if}
      </button>
    {/each}
  </nav>
</aside>
