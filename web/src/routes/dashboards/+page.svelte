<script lang="ts">
  import { onMount } from 'svelte';
  import { user } from '$lib/stores/auth';
  import { writable, get } from 'svelte/store';
  import * as api from '$lib/services/api';

  const dashboards = writable<any[]>([]);
  const loading = writable(true);
  const error = writable<string | null>(null);

  async function fetchDashboards() {
    loading.set(true);
    error.set(null);
    try {
      const data = await api.getDashboards();
      console.log(data);
      dashboards.set(data.data.dashboards || []);
    } catch (e) {
      error.set('Failed to load dashboards');
    }
    loading.set(false);
  }

  onMount(fetchDashboards);

  $: isEditor = $user?.role === 'editor';
</script>

<div class="max-w-2xl mx-auto mt-8 p-4">
  <h1 class="text-2xl font-bold mb-4">Dashboards</h1>
  {#if $loading}
    <div>Loading...</div>
  {:else if $error}
    <div class="text-red-500">{$error}</div>
  {:else if !$user}
    <div class="text-gray-600">Please log in to view your dashboards.</div>
  {:else if $dashboards.length === 0}
    <div class="text-gray-600">No dashboards found.</div>
  {:else}
    <ul class="divide-y divide-gray-200 bg-white rounded shadow">
      {#each $dashboards as dash}
        <li class="p-4 flex items-center justify-between">
          <a class="font-medium text-blue-700 hover:underline" href={`/dashboards/${dash.id}`}>{dash.name}</a>
          <a class="text-blue-600 hover:underline text-sm" href={`/dashboards/${dash.id}`}>View</a>
        </li>
      {/each}
    </ul>
  {/if}

  {#if isEditor && $user}
    <div class="mt-6 text-right">
      <a href="/dashboards/create" class="rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50">+ Create Dashboard</a>
    </div>
  {/if}
</div> 