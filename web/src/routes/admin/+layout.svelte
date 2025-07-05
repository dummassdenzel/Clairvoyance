<script lang="ts">
  import AdminNavbar from '$lib/components/AdminNavbar.svelte';
</script>

<div class="flex h-screen bg-gray-100">
  <AdminNavbar />
  <main class="flex-1 flex flex-col">
    <div class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
      <slot />
    </div>
  </main>
</div>
