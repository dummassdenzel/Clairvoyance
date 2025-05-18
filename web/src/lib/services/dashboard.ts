/**
 * Dashboard service for handling dashboard-related operations
 */
import * as api from './api';
import { writable, derived } from 'svelte/store';
import { token } from './auth';

// Types
export interface Dashboard {
  id: number;
  name: string;
  description?: string;
  is_default: boolean;
  user_id: number;
  username?: string;
  created_at: string;
  widgets?: Widget[];
}

export interface Widget {
  id: number;
  dashboard_id: number;
  kpi_id: number;
  kpi_name?: string;
  kpi_unit?: string;
  title: string;
  widget_type: 'line' | 'bar' | 'pie' | 'donut' | 'card';
  position_x: number;
  position_y: number;
  width: number;
  height: number;
  settings: any;
  created_at: string;
}

// Dashboard store
const dashboardStore = writable<Dashboard[]>([]);
export const dashboards = derived(dashboardStore, $dashboards => $dashboards);

// Current dashboard store
const currentDashboardStore = writable<Dashboard | null>(null);
export const currentDashboard = derived(currentDashboardStore, $dashboard => $dashboard);

let tokenValue: string | null = null;
token.subscribe(value => {
  tokenValue = value;
});

/**
 * Fetch all dashboards
 */
export async function fetchDashboards() {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.get('dashboards', tokenValue);
    if (response.status === 'success' && response.data) {
      dashboardStore.set(response.data);
      return { success: true };
    }

    return { success: false, message: 'Failed to fetch dashboards' };
  } catch (error) {
    console.error('Error fetching dashboards:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch dashboards' 
    };
  }
}

/**
 * Fetch a single dashboard by ID
 */
export async function fetchDashboard(id: number) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.get(`dashboards/${id}`, tokenValue);
    if (response.status === 'success' && response.data) {
      currentDashboardStore.set(response.data);
      return { success: true, dashboard: response.data };
    }

    return { success: false, message: 'Failed to fetch dashboard' };
  } catch (error) {
    console.error(`Error fetching dashboard ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch dashboard' 
    };
  }
}

/**
 * Create a new dashboard
 */
export async function createDashboard(data: Pick<Dashboard, 'name' | 'description' | 'is_default'>) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.post('dashboards', data, tokenValue);
    if (response.status === 'success' && response.data) {
      // Update the store with the new dashboard
      dashboardStore.update(dashboards => [...dashboards, response.data]);
      return { success: true, dashboard: response.data };
    }

    return { success: false, message: 'Failed to create dashboard' };
  } catch (error) {
    console.error('Error creating dashboard:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to create dashboard' 
    };
  }
}

/**
 * Update an existing dashboard
 */
export async function updateDashboard(id: number, data: Partial<Dashboard>) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.put(`dashboards/${id}`, data, tokenValue);
    if (response.status === 'success') {
      // Update the store with the modified dashboard
      dashboardStore.update(dashboards => dashboards.map(dashboard => 
        dashboard.id === id ? { ...dashboard, ...data } : dashboard
      ));
      
      // Update currentDashboard if necessary
      currentDashboardStore.update(dashboard => 
        dashboard && dashboard.id === id ? { ...dashboard, ...data } : dashboard
      );
      
      return { success: true };
    }

    return { success: false, message: 'Failed to update dashboard' };
  } catch (error) {
    console.error(`Error updating dashboard ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to update dashboard' 
    };
  }
}

/**
 * Delete a dashboard
 */
export async function deleteDashboard(id: number) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.del(`dashboards/${id}`, tokenValue);
    if (response.status === 'success') {
      // Remove the dashboard from the store
      dashboardStore.update(dashboards => dashboards.filter(dashboard => dashboard.id !== id));
      
      // Clear currentDashboard if necessary
      currentDashboardStore.update(dashboard => 
        dashboard && dashboard.id === id ? null : dashboard
      );
      
      return { success: true };
    }

    return { success: false, message: 'Failed to delete dashboard' };
  } catch (error) {
    console.error(`Error deleting dashboard ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete dashboard' 
    };
  }
} 