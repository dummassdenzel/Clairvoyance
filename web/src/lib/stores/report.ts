/**
 * Report service for handling report-related operations
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';
import { token } from './auth';
import { API_URL } from '../services/api'; // Assuming API_URL is exported from api.ts

// Types
export interface Report {
  id: number;
  user_id: number;
  dashboard_id: number;
  name: string;
  format: 'csv' | 'xlsx' | 'pdf' | 'json';
  file_path?: string; // Server-side path, download_url or specific endpoint is preferred for frontend
  file_name?: string; // From API spec
  file_size?: number; // From API spec, in bytes
  created_at: string;
  download_url?: string; // Useful if backend provides a direct download link
}

export interface ReportCreationData {
  dashboard_id: number;
  name: string;
  format: 'csv' | 'xlsx' | 'pdf' | 'json';
  time_range?: {
    start: string; // ISO date string
    end: string;   // ISO date string
  };
}

// Report store
const reportStore = writable<Report[]>([]);
export const reports = derived(reportStore, ($reports) => $reports);

/**
 * Fetch all reports for the current user
 */
export async function fetchReports() {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    const fetchedReports = await api.get('reports', currentToken) as Report[];
    reportStore.set(fetchedReports);
    return { success: true, data: fetchedReports };
  } catch (error) {
    console.error('Error fetching reports:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch reports' 
    };
  }
}

/**
 * Fetch a single report by ID
 */
export async function fetchReport(id: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    const reportData = await api.get(`reports/${id}`, currentToken) as Report;
    return { success: true, report: reportData };
  } catch (error) {
    console.error(`Error fetching report ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch report' 
    };
  }
}

/**
 * Create a new report
 */
export async function createReport(data: ReportCreationData) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    const newReport = await api.post('reports', data, currentToken) as Report;
    reportStore.update(reps => [...reps, newReport]); 
    return { success: true, report: newReport };
  } catch (error) {
    console.error('Error creating report:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to create report' 
    };
  }
}

/**
 * Download a report file
 * This function will trigger a browser download.
 */
export async function downloadReportFile(reportId: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }

    const response = await fetch(`${API_URL}/reports/${reportId}/download`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${currentToken}`,
        }
    });

    if (!response.ok) {
        // Try to parse error from backend if JSON, otherwise use status text
        let errorMessage = `Failed to download report: ${response.statusText}`;
        try {
            const errorData = await response.json();
            errorMessage = errorData.error?.message || errorData.message || errorMessage;
        } catch (e) {
            // Not a JSON error response, stick with statusText
        }
        throw new Error(errorMessage);
    }

    const blob = await response.blob();
    const contentDisposition = response.headers.get('content-disposition');
    let filename = 'report_download'; // Default filename
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
    console.error(`Error downloading report ${reportId}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Unknown error during report download' 
    };
  }
}

/**
 * Delete a report
 */
export async function deleteReport(id: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    await api.del(`reports/${id}`, currentToken);
    reportStore.update(reps => reps.filter(rep => rep.id !== id));
    return { success: true };
  } catch (error) {
    console.error(`Error deleting report ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete report' 
    };
  }
}
