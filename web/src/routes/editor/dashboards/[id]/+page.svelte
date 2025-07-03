<script lang="ts">
  import { page } from '$app/stores';
  import { user } from '$lib/stores/auth';
  import { onMount } from 'svelte';
  import { writable, type Writable } from 'svelte/store';
  import * as api from '$lib/services/api';
  import DashboardWidget from '$lib/components/DashboardWidget.svelte';
  
  import ViewersModal from '$lib/components/ViewersModal.svelte';
  import UploadKpiModal from '$lib/components/UploadKpiModal.svelte';
  import Grid from 'svelte-grid';
  import gridHelp from 'svelte-grid/build/helper/index.mjs';
  import { getContext } from 'svelte';

  type Item = {
    id: string;
    x: number;
    y: number;
    w: number;
    h: number;
  } & Record<string, any>;

  const dashboard = writable<any>(null);
  const loading = writable(true);
  const error = writable<string | null>(null);

  let isViewersModalOpen = false;
  let isUploadModalOpen = false;
  let editMode = false;
  

  function handleCancel() {
    editMode = false;
    fetchDashboard();
  }

  

  

  const cols: any = [[0, 12]];

  async function handleSave() {
    const layoutToSave = items.map(dataItem => {
      const layout = dataItem[cols[0][1]];
      const { id, title, type, kpi_id, backgroundColor, borderColor, startDate, endDate } = dataItem;
      return {
        id: Number(id),
        x: layout.x,
        y: layout.y,
        w: layout.w,
        h: layout.h,
        title,
        type,
        kpi_id,
        backgroundColor,
        borderColor,
        startDate,
        endDate
      };
    });

    try {
      await api.updateDashboard(dashboardId, { layout: layoutToSave });
      editMode = false;
      await fetchDashboard();
    } catch (e) {
      console.error('Failed to save layout:', e);
      alert('Failed to save layout. See console for details.');
    }
  }

  $: dashboardId = $page.params.id;
  $: isEditor = $user?.role === 'editor';

  async function fetchDashboard() {
    loading.set(true);
    error.set(null);
    try {
      const data = await api.getDashboard(dashboardId);
      if (data.error || !data.data || !data.data.dashboard) {
        error.set(data.error || 'Invalid dashboard data received.');
        dashboard.set(null);
      } else {
        dashboard.set(data.data.dashboard);
      }
    } catch (e) {
      error.set('Failed to load dashboard');
      dashboard.set(null);
    }
    loading.set(false);
  }

  onMount(() => {
    fetchDashboard();
  });

  $: kpisForSelect = widgetsArr
    .filter(w => w.kpi_id)
    .map(w => ({ id: w.kpi_id, title: w.title || `KPI ${w.kpi_id}` }))
    .filter((kpi, index, self) => self.findIndex(k => k.id === kpi.id) === index);
  $: widgetsArr = (() => {
    const layoutData = $dashboard?.layout || $dashboard?.widgets;
    if (typeof layoutData === 'string') {
      try {
        const parsed = JSON.parse(layoutData);
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        console.error("Failed to parse widgets JSON:", e);
        return [];
      }
    }
    if (Array.isArray(layoutData)) {
      return layoutData;
    }
    return [];
  })();

  let items: any[] = [];
  $: if (widgetsArr) {
    items = widgetsArr.map((widget, i) => {
      const layout = {
        x: widget.x ?? (i % 4) * 3,
        y: widget.y ?? Math.floor(i / 4) * 5,
        w: widget.w ?? 3,
        h: widget.h ?? 5,
        fixed: !editMode,
        min: { w: 4, h: 10 },
        customDragger: true
      };

      return {
        [cols[0][1]]: gridHelp.item(layout),
        ...widget,
        id: String(widget.id ?? widget.position ?? i)
      };
    });
  }

  const { openWidgetSettings, savedWidget }: { openWidgetSettings: (widget: any) => void; savedWidget: Writable<any | null> } = getContext('dashboard-layout');

  $: if ($savedWidget) {
    items = items.map(item => (item.id === $savedWidget.id ? { ...item, ...$savedWidget } : item));
    $savedWidget = null; // Reset store after processing
  }
</script>

<style>
  :global(.svlt-grid-container) {
    background: #eee;
  }
  :global(.svlt-grid-shadow) {
    background: rgba(0, 0, 0, 0.2);
  }
  :global(.svlt-grid-resizer) {
    background: #fff;
    border: 2px solid #333;
  }
</style>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
  {#if $loading}
    <div class="text-center py-12">Loading...</div>
  {:else if $error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <strong class="font-bold">Error:</strong>
      <span class="block sm:inline">{$error}</span>
    </div>
  {:else if !$user}
    <div class="text-center py-12 text-gray-600">Please log in to view this dashboard.</div>
  {:else if !$dashboard}
    <div class="text-center py-12 text-gray-600">Dashboard not found or you do not have access.</div>
  {:else}
    <!-- Page Header -->
    <div class="mb-6">
      <div class="mb-2 text-sm text-gray-500">
        <a href="/editor/dashboards" class="hover:underline">Dashboards</a>
        <span class="mx-2">/</span>
        <span class="font-medium text-gray-700">{$dashboard.name}</span>
      </div>
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{$dashboard.name}</h1>
          {#if $dashboard.description}
            <p class="mt-2 text-sm text-gray-600">{$dashboard.description}</p>
          {/if}
        </div>

        {#if isEditor}
          <div class="flex items-center space-x-2">
            <button on:click={() => isViewersModalOpen = true} class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
              Manage Viewers
            </button>
            <button on:click={() => isUploadModalOpen = true} class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
              Upload Data
            </button>
            {#if editMode}
              <button on:click={handleCancel} class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Cancel
              </button>
              <button on:click={handleSave} class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                Save Layout
              </button>
            {:else}
              <button on:click={() => editMode = true} class="px-4 py-2 text-sm font-medium text-white bg-gray-800 border border-transparent rounded-md hover:bg-gray-900">
                Edit Layout
              </button>
            {/if}
          </div>
        {/if}
      </div>
    </div>

    <div class="mt-8">
      {#if widgetsArr.length > 0}
        <div class="svelte-grid-container -mx-2">
          <Grid {cols} bind:items={items} rowHeight={40} let:item let:dataItem let:movePointerDown>
            <div class="h-full w-full p-2">
              <DashboardWidget widget={dataItem} {editMode} {movePointerDown} />
            </div>
          </Grid>
        </div>
      {:else}
        <div class="text-center py-12 text-gray-500 bg-gray-50 rounded-lg">
          <h3 class="text-lg font-medium">No widgets yet</h3>
          <p class="mt-1 text-sm">This dashboard doesn't have any widgets configured.</p>
        </div>
      {/if}
    </div>
  {/if}
</div>

{#if $dashboard}
  <ViewersModal 
    bind:isOpen={isViewersModalOpen} 
    dashboardId={$dashboard.id}
    viewers={$dashboard.viewers || []}
    on:update={fetchDashboard}
  />
{/if}

{#if $dashboard}
  <UploadKpiModal
    bind:isOpen={isUploadModalOpen}
    kpis={kpisForSelect}
    on:update={fetchDashboard}
  />
{/if} 