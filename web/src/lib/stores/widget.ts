/**
 * Widget service for handling widget-related operations
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';
import { token } from './auth';
import type { Widget } from './dashboard';

// Current widget being edited
const currentWidgetStore = writable<Widget | null>(null);
export const currentWidget = derived(currentWidgetStore, $widget => $widget);

/**
 * Create a new widget
 */
export async function createWidget(dashboardId: number, data: Pick<Widget, 'kpi_id' | 'title' | 'widget_type' | 'position_x' | 'position_y' | 'width' | 'height' | 'settings'>) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    // dashboard_id is part of the URL, but the API might also expect it in the body for POST /widgets.
    // The API spec for POST /widgets includes dashboard_id in the body.
    const widgetPayload = {
      ...data,
      dashboard_id: dashboardId // Ensure dashboard_id is in the payload if required by the specific controller action
    };

    const newWidget = await api.post(`dashboards/${dashboardId}/widgets`, widgetPayload, currentToken) as Widget;
    return { success: true, widget: newWidget };

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
export async function updateWidget(dashboardId: number, widgetId: number, data: Partial<Pick<Widget, 'kpi_id' | 'title' | 'widget_type' | 'position_x' | 'position_y' | 'width' | 'height' | 'settings'>>) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const updatedWidget = await api.put(`dashboards/${dashboardId}/widgets/${widgetId}`, data, currentToken) as Widget;
    // Update currentWidget if necessary
    currentWidgetStore.update(current => 
      current && current.id === widgetId ? updatedWidget : current
    );
    
    return { success: true, widget: updatedWidget };

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
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    await api.del(`dashboards/${dashboardId}/widgets/${widgetId}`, currentToken);
    // Clear currentWidget if necessary
    currentWidgetStore.update(current => 
      current && current.id === widgetId ? null : current
    );
    
    return { success: true };

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
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const queryParams = new URLSearchParams(params).toString();
    const url = `dashboards/${dashboardId}/widgets/${widgetId}/data${queryParams ? `?${queryParams}` : ''}`;
    
    const widgetChartData = await api.get(url, currentToken);
    return { success: true, data: widgetChartData };

  } catch (error) {
    console.error(`Error fetching data for widget ${widgetId}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch widget data' 
    };
  }
}