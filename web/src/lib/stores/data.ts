/**
 * Data service for handling import and export operations
 */
import { get } from 'svelte/store';
import { token } from './auth';
import { API_URL } from '../services/api';

// Types
export interface ImportConfig {
  file: File;
  type: 'kpi_measurements' | 'dashboard_structure' | 'system_settings'; // Aligned with backend API
  kpi_id?: number; // Required for type 'kpi_measurements'
}

export interface ExportParams {
  entity: 'kpis' | 'dashboard' | 'measurements' | 'system_settings'; // Aligned with backend API
  format: 'csv' | 'xlsx' | 'json' | 'pdf';
  entity_id?: number; // e.g., dashboard_id for 'dashboard', kpi_id for 'measurements' (if exporting single KPI's measurements)
  kpi_ids?: number[]; // For exporting multiple specific KPIs or their measurements
  date_from?: string; // For time-bound exports, e.g., measurements
  date_to?: string;   // For time-bound exports, e.g., measurements
}



/**
 * Import data from a file
 */
export async function importData(config: ImportConfig) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }

    const formData = new FormData();
    formData.append('file', config.file);
    formData.append('type', config.type);
    if (config.type === 'kpi_measurements' && config.kpi_id) {
      formData.append('kpi_id', config.kpi_id.toString());
    } else if (config.type === 'kpi_measurements' && !config.kpi_id) {
      return { success: false, message: 'kpi_id is required for importing kpi_measurements' };
    }

    const response = await fetch(`${API_URL}/import`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${currentToken}`,
        // 'Content-Type' is automatically set by the browser for FormData
      },
      body: formData,
    });

    const responseData = await response.json(); // Backend sends {success: boolean, data: {}, error: {}}
    if (!response.ok || !responseData.success) {
      const errorMessage = responseData.error?.message || responseData.message || 'Import failed';
      throw new Error(errorMessage);
    }

    // On success, backend returns { success: true, data: { message: string, summary: {} } }
    return { success: true, data: responseData.data }; 

  } catch (error) {
    console.error('Error importing data:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Unknown error during import' 
    };
  }
}

/**
 * Export data
 * This function will trigger a browser download.
 */
export async function exportData(params: ExportParams) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }

    const query = new URLSearchParams({
      entity: params.entity,
      format: params.format,
    });
    if (params.entity_id) {
      // The API documentation uses 'id' for dashboard_id in GET /export
      query.append('id', params.entity_id.toString());
    }
    if (params.kpi_ids && params.kpi_ids.length > 0) {
      params.kpi_ids.forEach(id => query.append('kpi_ids[]', id.toString()));
    }
    if (params.date_from) {
      query.append('date_from', params.date_from);
    }
    if (params.date_to) {
      query.append('date_to', params.date_to);
    }

    const response = await fetch(`${API_URL}/export?${query.toString()}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${currentToken}`,
      },
    });

    if (!response.ok) {
      let errorMessage = `Failed to export data: ${response.statusText}`;
      try {
        const errorData = await response.json(); // Expects {success: false, error: {message: ...}}
        errorMessage = errorData.error?.message || errorData.message || errorMessage;
      } catch (e) {
        // Not a JSON error response
      }
      throw new Error(errorMessage);
    }

    const blob = await response.blob();
    const contentDisposition = response.headers.get('content-disposition');
    let filename = `${params.entity}_export.${params.format}`; // Default filename
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i);
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '');
      }
    }

    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);

    return { success: true };

  } catch (error) {
    console.error('Error exporting data:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Unknown error during export' 
    };
  }
}
