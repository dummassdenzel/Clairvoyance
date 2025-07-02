<script lang="ts">
  import { user } from '$lib/stores/auth';
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';

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
    const data = await api.createDashboard({ name, widgets: widgetsJson });
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
      <input class="border rounded px-3 py-2 w-full" type="text" placeholder="Dashboard Name" bind:value={name} required />
      <label class="block text-sm font-medium text-gray-700">Widgets (JSON)</label>
      <textarea class="border rounded px-3 py-2 w-full h-32" bind:value={widgets} required></textarea>
      {#if error}
        <div class="text-red-500 text-sm">{error}</div>
      {/if}
      <button class="rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50 w-full" type="submit" disabled={loading}>{loading ? 'Creating...' : 'Create Dashboard'}</button>
    </form>
  </div>
{/if} 