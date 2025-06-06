/**
 * Dashboard service for handling dashboard-related operations
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';
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
  kpi_name?: string; // Joined from KPIs table
  kpi_unit?: string; // Joined from KPIs table
  title: string;
  widget_type: 'line' | 'bar' | 'pie' | 'donut' | 'card';
  position_x: number;
  position_y: number;
  width: number;
  height: number;
  settings: Record<string, any>; // JSON object for widget-specific settings
  user_id?: number; // Added by backend, reflects ownership or creator context
  created_at: string;
  updated_at?: string; // Added from API spec
}

// Dashboard store
const dashboardStore = writable<Dashboard[]>([]);
export const dashboards = derived(dashboardStore, $dashboards => $dashboards);

// Current dashboard store
const currentDashboardStore = writable<Dashboard | null>(null);
export const currentDashboard = derived(currentDashboardStore, $dashboard => $dashboard);

/**
 * Fetch all dashboards
 */
export async function fetchDashboards() {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const fetchedDashboards = await api.get('dashboards', currentToken) as Dashboard[];
    dashboardStore.set(fetchedDashboards);
    return { success: true, data: fetchedDashboards };

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
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const dashboardData = await api.get(`dashboards/${id}`, currentToken) as Dashboard;
    currentDashboardStore.set(dashboardData);
    return { success: true, dashboard: dashboardData };

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
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const newDashboard = await api.post('dashboards', data, currentToken) as Dashboard;
    // Update the store with the new dashboard
    dashboardStore.update(dashboards => [...dashboards, newDashboard]);
    return { success: true, dashboard: newDashboard };

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
export async function updateDashboard(id: number, data: Partial<{ name: string; description?: string; is_default: boolean; }>) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const updatedDashboard = await api.put(`dashboards/${id}`, data, currentToken) as Dashboard;
    // Update the store with the modified dashboard
    dashboardStore.update(dashboards => dashboards.map(dashboard => 
      dashboard.id === id ? updatedDashboard : dashboard
    ));
    
    // Update currentDashboard if necessary
    currentDashboardStore.update(current => 
      current && current.id === id ? updatedDashboard : current
    );
    
    return { success: true, dashboard: updatedDashboard };

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
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    await api.del(`dashboards/${id}`, currentToken);
    // Remove the dashboard from the store
    dashboardStore.update(dashboards => dashboards.filter(dashboard => dashboard.id !== id));
    
    // Clear currentDashboard if necessary
    currentDashboardStore.update(current => 
      current && current.id === id ? null : current
    );
    
    return { success: true };

  } catch (error) {
    console.error(`Error deleting dashboard ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete dashboard' 
    };
  }
}