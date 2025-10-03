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
</script>

<div class="min-h-screen bg-gray-50">
  <div class="container mx-auto p-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
      <p class="text-gray-600 mt-1">System overview and administration</p>
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

    <!-- Quick Stats Overview -->
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
    {/if}

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- User Management -->
      <a href="/admin/users" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg class="w-8 h-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 ml-3">User Management</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Manage user accounts, roles, and permissions. Create new users and view user details.</p>
        <div class="flex items-center text-blue-600 text-sm font-medium">
          <span>Manage Users</span>
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </div>
      </a>

      <!-- System Statistics -->
      <a href="/admin/stats" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-green-100 rounded-lg">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 ml-3">System Statistics</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">View detailed system statistics, recent activity, and performance metrics.</p>
        <div class="flex items-center text-green-600 text-sm font-medium">
          <span>View Statistics</span>
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </div>
      </a>

      <!-- Dashboard Access -->
      <a href="/editor/dashboards" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-purple-100 rounded-lg">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 ml-3">Dashboard Access</h3>
        </div>
        <p class="text-gray-600 text-sm mb-4">Access the editor dashboard to view and manage dashboards with admin privileges.</p>
        <div class="flex items-center text-purple-600 text-sm font-medium">
          <span>Go to Dashboards</span>
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </div>
      </a>
    </div>

    <!-- Recent Activity Preview -->
    {#if systemStats && systemStats.recent_activity.length > 0}
      <div class="mt-8 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            {#each systemStats.recent_activity.slice(0, 3) as activity}
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-100">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-3 flex-1">
                  <p class="text-sm text-gray-900">{activity.description}</p>
                  <p class="text-xs text-gray-500">{new Date(activity.timestamp).toLocaleString()}</p>
                </div>
              </div>
            {/each}
          </div>
          <div class="mt-4 text-center">
            <a href="/admin/stats" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
              View all activity →
            </a>
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>
