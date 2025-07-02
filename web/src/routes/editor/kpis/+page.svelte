<script lang="ts">
  import { onMount } from 'svelte';
  import { user } from '$lib/stores/auth';
  import { writable } from 'svelte/store';
  import * as api from '$lib/services/api';

  const kpis = writable<any[]>([]);
  const loading = writable(true);
  const error = writable<string | null>(null);

  let name = '';
  let target = '';
  let rag_red = '';
  let rag_amber = '';
  let creating = false;
  let createError: string | null = null;

  $: isEditor = $user?.role === 'editor';

  async function fetchKpis() {
    loading.set(true);
    error.set(null);
    try {
      const data = await api.getKpis();
      kpis.set(data.kpis || []);
    } catch (e) {
      error.set('Failed to load KPIs');
    }
    loading.set(false);
  }

  async function handleCreate(event: Event) {
    event.preventDefault();
    createError = null;
    creating = true;
    const data = await api.createKpi({ name, target, rag_red, rag_amber });
    creating = false;
    if (data.id) {
      name = target = rag_red = rag_amber = '';
      fetchKpis();
    } else {
      createError = data.error || 'Failed to create KPI.';
    }
  }

  onMount(fetchKpis);
</script>

<div class="max-w-2xl mx-auto mt-8 p-4">
  <h1 class="text-2xl font-bold mb-4">KPIs</h1>
  {#if $loading}
    <div>Loading...</div>
  {:else if $error}
    <div class="text-red-500">{$error}</div>
  {:else if !$user}
    <div class="text-gray-600">Please log in to view KPIs.</div>
  {:else}
    <ul class="divide-y divide-gray-200 bg-white rounded shadow mb-6">
      {#each $kpis as kpi}
        <li class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <span class="font-medium">{kpi.name}</span>
            <span class="ml-2 text-xs text-gray-500">Target: {kpi.target}</span>
            <span class="ml-2 text-xs text-red-500">Red: {kpi.rag_red}</span>
            <span class="ml-2 text-xs text-yellow-500">Amber: {kpi.rag_amber}</span>
          </div>
        </li>
      {/each}
    </ul>
    {#if isEditor}
      <form class="space-y-2 bg-white rounded shadow p-4" on:submit={handleCreate}>
        <h2 class="font-semibold mb-2">Create KPI</h2>
        <input class="border rounded px-3 py-2 w-full" type="text" placeholder="KPI Name" bind:value={name} required />
        <input class="border rounded px-3 py-2 w-full" type="number" placeholder="Target" bind:value={target} required />
        <input class="border rounded px-3 py-2 w-full" type="number" placeholder="RAG Red" bind:value={rag_red} required />
        <input class="border rounded px-3 py-2 w-full" type="number" placeholder="RAG Amber" bind:value={rag_amber} required />
        {#if createError}
          <div class="text-red-500 text-sm">{createError}</div>
        {/if}
        <button class="rounded bg-blue-600 text-white font-semibold px-4 py-2 hover:bg-blue-700 transition disabled:opacity-50 w-full" type="submit" disabled={creating}>{creating ? 'Creating...' : 'Create KPI'}</button>
      </form>
    {/if}
  {/if}
</div> 