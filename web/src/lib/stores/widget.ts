/**
 * Widget service for handling widget-related operations
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';
import { token } from './auth';
import type { Widget } from './dashboard';

// Input types
export interface WidgetCreateInput {
  dashboard_id: number;
  kpi_id: number;
  title: string;
  widget_type: 'line' | 'bar' | 'pie' | 'donut' | 'card';
  position_x?: number;
  position_y?: number;
  width?: number;
  height?: number;
  settings?: Record<string, any>;
}

export interface WidgetUpdateInput {
  kpi_id?: number;
  title?: string;
  widget_type?: 'line' | 'bar' | 'pie' | 'donut' | 'card';
  position_x?: number;
  position_y?: number;
  width?: number;
  height?: number;
  settings?: Record<string, any>;
  dashboard_id?: number; // If allowing moving widget to another dashboard, backend must support this field for PUT /widgets/{id}
}

// Current widget being edited
const currentWidgetStore = writable<Widget | null>(null);
export const currentWidget = derived(currentWidgetStore, $widget => $widget);

/**
 * Create a new widget
 */
export async function createWidget(data: WidgetCreateInput) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    // dashboard_id is part of the URL, but the API might also expect it in the body for POST /widgets.
    // The API spec for POST /widgets includes dashboard_id in the body.
    const widgetPayload = data;

    const newWidget = await api.post('widgets', widgetPayload, currentToken) as Widget;
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
export async function updateWidget(widgetId: number, data: WidgetUpdateInput) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const updatedWidget = await api.put(`widgets/${widgetId}`, data, currentToken) as Widget;
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
export async function deleteWidget(widgetId: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    await api.del(`widgets/${widgetId}`, currentToken);
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
/**
 * Fetch a single widget by ID
 */
export async function fetchWidgetById(widgetId: number): Promise<Widget | null> {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available for fetchWidgetById');
      // Return null or throw error, depends on desired error handling for components
      return null; 
    }

    const widgetData = await api.get(`widgets/${widgetId}`, currentToken) as Widget;
    currentWidgetStore.set(widgetData); // Optionally update current widget if it's the one being fetched
    return widgetData;

  } catch (error) {
    console.error(`Error fetching widget ${widgetId}:`, error);
    // Return null or throw, ensure components handle this
    return null; 
  }
}

/**
 * Fetch all widgets accessible by the user
 * Note: The backend endpoint GET /widgets should implement logic to return widgets
 * based on user's permissions (e.g., widgets from their own dashboards or shared ones).
 */
export async function fetchAllWidgetsForUser(): Promise<Widget[]> {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available for fetchAllWidgetsForUser');
      return [];
    }

    // Assuming GET /widgets returns an array of widgets the user can access.
    // This might require a specific backend implementation for GET /widgets.
    const widgets = await api.get('widgets', currentToken) as Widget[];
    // This function doesn't directly update a global store of 'all widgets'
    // as that might be very large. It returns the data for specific use cases.
    return widgets;

  } catch (error) {
    console.error('Error fetching all widgets for user:', error);
    return [];
  }
}

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