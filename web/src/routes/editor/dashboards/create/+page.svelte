<script lang="ts">
  import { writable } from 'svelte/store';
  import { goto } from '$app/navigation';
  import * as api from '$lib/services/api';

  const name = writable('');
  const description = writable('');
  const error = writable<string | null>(null);
  const submitting = writable(false);

  async function handleSubmit() {
    if (!$name) {
      error.set('Dashboard name is required.');
      return;
    }

    submitting.set(true);
    error.set(null);

    try {
      const response = await api.createDashboard({ name: $name, description: $description });
      if (response.status === 'success') {
        goto('/editor/dashboards');
      } else {
        error.set(response.message || 'Failed to create dashboard.');
      }
    } catch (e) {
      error.set('An unexpected error occurred.');
    }

    submitting.set(false);
  }
</script>

<svelte:head>
  <title>Create Dashboard</title>
</svelte:head>

<div class="container mx-auto p-6">
  <h1 class="text-3xl font-bold mb-6">Create a New Dashboard</h1>

  <form on:submit|preventDefault={handleSubmit} class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow">
    {#if $error}
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">{$error}</span>
      </div>
    {/if}

    <div class="mb-4">
      <label for="name" class="block text-gray-700 font-bold mb-2">Dashboard Name</label>
      <input type="text" id="name" bind:value={$name} class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
    </div>

    <div class="mb-6">
      <label for="description" class="block text-gray-700 font-bold mb-2">Description (Optional)</label>
      <textarea id="description" bind:value={$description} class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="4"></textarea>
    </div>

    <div class="flex items-center justify-between">
      <button type="submit" disabled={$submitting} class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50">
        {$submitting ? 'Creating...' : 'Create Dashboard'}
      </button>
      <a href="/editor/dashboards" class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800">
        Cancel
      </a>
    </div>
  </form>
</div>