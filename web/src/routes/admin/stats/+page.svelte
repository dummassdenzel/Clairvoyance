<script lang="ts">
  import { onMount } from 'svelte';
  import { getAdminStats } from '$lib/services/api';

  interface SystemStats {
    users: {
      total: number;
      by_role: { admin: number; editor: number };
      new_this_week: number;
      new_this_month: number;
    };
    dashboards: {
      total: number;
      new_this_week: number;
      new_this_month: number;
      top_owners: Array<{ email: string; dashboard_count: number }>;
    };
    kpis: {
      total: number;
      new_this_week: number;
      new_this_month: number;
      total_entries: number;
      entries_this_week: number;
    };
    recent_activity: Array<{
      type: string;
      description: string;
      timestamp: string;
    }>;
  }

  let systemStats: SystemStats | null = null;
  let statsLoading = true;
  let error: string | null = null;

  onMount(async () => {
    await fetchSystemStats();
  });

  async function fetchSystemStats() {
    statsLoading = true;
    try {
      const response = await getAdminStats();
      if (response.success && response.data) {
        systemStats = response.data;
      }
    } catch (e: any) {
      error = e.message;
    } finally {
      statsLoading = false;
    }
  }

  function formatRelativeTime(dateString: string) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
    
    if (diffInHours < 1) return 'Just now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`;
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getActivityIconClass(activityType: string): string {
    switch (activityType) {
      case 'user_registered':
        return 'bg-green-500';
      case 'dashboard_created':
        return 'bg-blue-500';
      case 'kpi_created':
        return 'bg-purple-500';
      case 'kpi_entry_added':
        return 'bg-orange-500';
      default:
        return 'bg-gray-400';
    }
  }
</script>

<div class="min-h-screen bg-gray-50">
  <div class="container mx-auto p-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">System Statistics</h1>
      <p class="text-gray-600 mt-1">Overview of system performance and activity</p>
    </div>

    <!-- Error Message -->
    {#if error}
      <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
        <div class="flex">
          <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium">Error:</span>
          <span class="ml-1">{error}</span>
        </div>
      </div>
    {/if}

    <!-- System Statistics -->
    {#if statsLoading}
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {#each Array(4) as _}
          <div class="bg-white rounded-lg shadow p-6 animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
            <div class="h-8 bg-gray-200 rounded w-1/2"></div>
          </div>
        {/each}
      </div>
    {:else if systemStats}
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Users Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Users</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.users.total}</p>
            </div>
          </div>
          <div class="mt-4 flex justify-between text-sm">
            <span class="text-green-600">+{systemStats.users.new_this_week} this week</span>
            <span class="text-gray-500">{systemStats.users.by_role.admin} admin, {systemStats.users.by_role.editor} editor</span>
          </div>
        </div>

        <!-- Dashboards Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Dashboards</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.dashboards.total}</p>
            </div>
          </div>
          <div class="mt-4 flex justify-between text-sm">
            <span class="text-green-600">+{systemStats.dashboards.new_this_week} this week</span>
            <span class="text-gray-500">+{systemStats.dashboards.new_this_month} this month</span>
          </div>
        </div>

        <!-- KPIs Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total KPIs</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.kpis.total}</p>
            </div>
          </div>
          <div class="mt-4 flex justify-between text-sm">
            <span class="text-green-600">+{systemStats.kpis.new_this_week} this week</span>
            <span class="text-gray-500">{systemStats.kpis.total_entries} entries</span>
          </div>
        </div>

        <!-- Activity Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-2 bg-orange-100 rounded-lg">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Recent Activity</p>
              <p class="text-2xl font-bold text-gray-900">{systemStats.recent_activity.length}</p>
            </div>
          </div>
          <div class="mt-4 text-sm text-gray-500">
            <span>Last 7 days</span>
          </div>
        </div>
      </div>

      <!-- Recent Activity & Top Owners -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
          </div>
          <div class="p-6">
            {#if systemStats.recent_activity.length > 0}
              <div class="space-y-4">
                {#each systemStats.recent_activity.slice(0, 5) as activity}
                  <div class="flex items-start">
                    <div class="flex-shrink-0">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center {getActivityIconClass(activity.type)}">
                        {#if activity.type === 'user_registered'}
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                          </svg>
                        {:else if activity.type === 'dashboard_created'}
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                          </svg>
                        {:else if activity.type === 'kpi_created'}
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                          </svg>
                        {:else if activity.type === 'kpi_entry_added'}
                          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                          </svg>
                        {:else}
                          <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        {/if}
                      </div>
                    </div>
                    <div class="ml-3 flex-1">
                      <p class="text-sm text-gray-900">{activity.description}</p>
                      <p class="text-xs text-gray-500 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {formatRelativeTime(activity.timestamp)}
                      </p>
                    </div>
                  </div>
                {/each}
              </div>
            {:else}
              <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <p class="mt-2 text-gray-500 text-sm">No recent activity</p>
                <p class="text-gray-400 text-xs">Activity will appear here as users interact with the system</p>
              </div>
            {/if}
          </div>
        </div>

        <!-- Top Dashboard Owners -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Top Dashboard Owners</h3>
          </div>
          <div class="p-6">
            {#if systemStats.dashboards.top_owners.length > 0}
              <div class="space-y-4">
                {#each systemStats.dashboards.top_owners as owner}
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">{owner.email.charAt(0).toUpperCase()}</span>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">{owner.email}</p>
                      </div>
                    </div>
                    <span class="text-sm text-gray-500">{owner.dashboard_count} dashboards</span>
                  </div>
                {/each}
              </div>
            {:else}
              <p class="text-gray-500 text-sm">No dashboard owners found</p>
            {/if}
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>