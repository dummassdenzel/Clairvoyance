<script lang="ts">
  import { user } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  let name = '';
  let widgets = '[\n  { "type": "bar", "kpi_id": 1, "title": "Example Bar Chart" }\n]';
  let loading = false;
  let error: string | null = null;

  $: isEditor = $user?.role === 'editor';

  async function handleSubmit(event: Event) {
    event.preventDefault();
    error = null;
    loading = true;
    let widgetsJson;
    try {
      widgetsJson = JSON.parse(widgets);
    } catch (e) {
      error = 'Widgets must be valid JSON.';
      loading = false;
      return;
    }
    const res = await fetch('/api/routes/dashboards.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ name, widgets: widgetsJson })
    });
    const data = await res.json();
    loading = false;
    if (data.id) {
      goto('/dashboards');
    } else {
      error = data.error || 'Failed to create dashboard.';
    }
  }
</script>

{#if !$user}
  <div class="max-w-lg mx-auto mt-12 text-center text-gray-600">Please log in as an editor to create dashboards.</div>
{:else if !isEditor}
  <div class="max-w-lg mx-auto mt-12 text-center text-gray-600">Only editors can create dashboards.</div>
{:else}
  <div class="max-w-lg mx-auto mt-8 p-4">
    <h1 class="text-2xl font-bold mb-4">Create Dashboard</h1>
    <form class="space-y-4" on:submit={handleSubmit}>
      <input class="input input-bordered w-full" type="text" placeholder="Dashboard Name" bind:value={name} required />
      <label class="block text-sm font-medium text-gray-700">Widgets (JSON)</label>
      <textarea class="input input-bordered w-full h-32" bind:value={widgets} required></textarea>
      {#if error}
        <div class="text-red-500 text-sm">{error}</div>
      {/if}
      <button class="btn btn-primary w-full" type="submit" disabled={loading}>{loading ? 'Creating...' : 'Create Dashboard'}</button>
    </form>
  </div>
{/if}

<style>
  .input { @apply border rounded px-3 py-2; }
  .btn { @apply rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50; }
</style> 