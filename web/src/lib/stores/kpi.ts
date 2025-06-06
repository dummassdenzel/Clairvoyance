/**
 * KPI service for handling KPI-related operations
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';
import { token } from './auth';

// Types
export interface KPI {
  id: number;
  name: string;
  description?: string; // Added from API spec
  category_id: number;
  category_name?: string;
  unit: string;
  target: number;
  current_value?: number;
  user_id: number; // Added from API spec
  username?: string; // Added from API spec
  created_at: string;
  updated_at?: string;
}

export interface Measurement {
  id: number;
  kpi_id: number;
  value: string | number; // Value from DB can be string or number
  timestamp: string;      // Changed from 'date'
  created_at: string;
  // notes?: string; // 'notes' is not returned by GET /kpis/{kpi_id}/measurements
}

export interface KpiMeasurementInput {
  value: number;
  date: string; // Expecting YYYY-MM-DD
  notes?: string;
}

// KPI store
const kpiStore = writable<KPI[]>([]);
export const kpis = derived(kpiStore, $kpis => $kpis);

/**
 * Fetch all KPIs
 */
export async function fetchKPIs() {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const fetchedKPIs = await api.get('kpis', currentToken) as KPI[];
    kpiStore.set(fetchedKPIs);
    return { success: true, data: fetchedKPIs };

  } catch (error) {
    console.error('Error fetching KPIs:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch KPIs' 
    };
  }
}

/**
 * Fetch a single KPI by ID
 */
export async function fetchKPI(id: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const kpiData = await api.get(`kpis/${id}`, currentToken) as KPI;
    return { success: true, kpi: kpiData };

  } catch (error) {
    console.error(`Error fetching KPI ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch KPI' 
    };
  }
}

/**
 * Create a new KPI
 */
export async function createKPI(data: Pick<KPI, 'name' | 'description' | 'category_id' | 'unit' | 'target'>) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const newKPI = await api.post('kpis', data, currentToken) as KPI;
    // Update the store with the new KPI
    kpiStore.update(kpis => [...kpis, newKPI]);
    return { success: true, kpi: newKPI };

  } catch (error) {
    console.error('Error creating KPI:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to create KPI' 
    };
  }
}

/**
 * Update an existing KPI
 */
export async function updateKPI(id: number, data: Partial<Pick<KPI, 'name' | 'description' | 'category_id' | 'unit' | 'target'>>) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const updatedKPI = await api.put(`kpis/${id}`, data, currentToken) as KPI;
    // Update the store with the modified KPI
    kpiStore.update(kpis => kpis.map(kpi => 
      kpi.id === id ? updatedKPI : kpi
    ));
    return { success: true, kpi: updatedKPI };

  } catch (error) {
    console.error(`Error updating KPI ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to update KPI' 
    };
  }
}

/**
 * Delete a KPI
 */
export async function deleteKPI(id: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    await api.del(`kpis/${id}`, currentToken);
    // Remove the KPI from the store
    kpiStore.update(kpis => kpis.filter(kpi => kpi.id !== id));
    return { success: true };

  } catch (error) {
    console.error(`Error deleting KPI ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete KPI' 
    };
  }
}

/**
 * Add a measurement to a KPI
 */
export async function addKpiMeasurement(kpiId: number, measurementInput: KpiMeasurementInput): Promise<{ success: true; data: Measurement; message: string; } | { success: false; data: null; message: string; }> {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available for adding KPI measurement');
      return { success: false, data: null, message: 'Authentication required' };
    }

    const newMeasurement = await api.post(`kpis/${kpiId}/measurements`, measurementInput, currentToken) as Measurement;

    return { success: true, data: newMeasurement, message: 'Measurement added successfully' };

  } catch (error) {
    console.error(`Error adding KPI measurement for KPI ${kpiId}:`, error);
    const errorMessage = (error instanceof Error && error.message) ? error.message : 'Failed to add KPI measurement due to an unexpected server error.';
    return { 
      success: false, 
      data: null,
      message: errorMessage
    };
  }
}

/**
 * Fetch all measurements for a specific KPI
 */
export async function fetchKpiMeasurements(kpiId: string | number): Promise<{ success: true; data: Measurement[]; } | { success: false; message: string; data: null; }> {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      console.error('No token available for fetching KPI measurements');
      return { success: false, message: 'Authentication required', data: null };
    }

    const measurements = await api.get(`/kpis/${kpiId}/measurements`, currentToken) as Measurement[];
    return { success: true, data: measurements };

  } catch (error) {
    console.error(`Error fetching KPI measurements for KPI ${kpiId}:`, error);
    const errorMessage = (error instanceof Error && error.message) ? error.message : 'Failed to fetch KPI measurements.';
    return { 
      success: false, 
      message: errorMessage,
      data: null
    };
  }
}