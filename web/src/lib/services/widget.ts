/**
 * Widget service for handling widget-related operations
 */
import * as api from './api';
import { writable, derived } from 'svelte/store';
import { token } from './auth';
import type { Widget } from './dashboard';

// Current widget being edited
const currentWidgetStore = writable<Widget | null>(null);
export const currentWidget = derived(currentWidgetStore, $widget => $widget);

let tokenValue: string | null = null;
token.subscribe(value => {
  tokenValue = value;
});

/**
 * Create a new widget
 */
export async function createWidget(dashboardId: number, data: Omit<Widget, 'id' | 'dashboard_id' | 'created_at'>) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const widgetData = {
      dashboard_id: dashboardId,
      ...data
    };

    const response = await api.post(`dashboards/${dashboardId}/widgets`, widgetData, tokenValue);
    if (response.status === 'success' && response.data) {
      return { success: true, widget: response.data };
    }

    return { success: false, message: 'Failed to create widget' };
  } catch (error) {
    console.error('Error creating widget:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to create widget' 
    };
  }
}

/**
 * Update an existing widget
 */
export async function updateWidget(dashboardId: number, widgetId: number, data: Partial<Widget>) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.put(`dashboards/${dashboardId}/widgets/${widgetId}`, data, tokenValue);
    if (response.status === 'success') {
      // Update currentWidget if necessary
      currentWidgetStore.update(widget => 
        widget && widget.id === widgetId ? { ...widget, ...data } : widget
      );
      
      return { success: true };
    }

    return { success: false, message: 'Failed to update widget' };
  } catch (error) {
    console.error(`Error updating widget ${widgetId}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to update widget' 
    };
  }
}

/**
 * Delete a widget
 */
export async function deleteWidget(dashboardId: number, widgetId: number) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.del(`dashboards/${dashboardId}/widgets/${widgetId}`, tokenValue);
    if (response.status === 'success') {
      // Clear currentWidget if necessary
      currentWidgetStore.update(widget => 
        widget && widget.id === widgetId ? null : widget
      );
      
      return { success: true };
    }

    return { success: false, message: 'Failed to delete widget' };
  } catch (error) {
    console.error(`Error deleting widget ${widgetId}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete widget' 
    };
  }
}

/**
 * Set the current widget for editing
 */
export function setCurrentWidget(widget: Widget | null) {
  currentWidgetStore.set(widget);
}

/**
 * Get widget data for visualization
 */
export async function getWidgetData(dashboardId: number, widgetId: number, params?: Record<string, string>) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const queryParams = new URLSearchParams(params).toString();
    const url = `dashboards/${dashboardId}/widgets/${widgetId}/data${queryParams ? `?${queryParams}` : ''}`;
    
    const response = await api.get(url, tokenValue);
    if (response.status === 'success' && response.data) {
      return { success: true, data: response.data };
    }

    return { success: false, message: 'Failed to fetch widget data' };
  } catch (error) {
    console.error(`Error fetching data for widget ${widgetId}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch widget data' 
    };
  }
} 