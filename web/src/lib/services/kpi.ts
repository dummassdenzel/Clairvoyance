/**
 * KPI service for handling KPI-related operations
 */
import * as api from './api';
import { writable, derived } from 'svelte/store';
import { token } from './auth';

// Types
export interface KPI {
  id: number;
  name: string;
  category_id: number;
  category_name?: string;
  unit: string;
  target: number;
  current_value?: number;
  created_at: string;
  updated_at?: string;
}

export interface Measurement {
  id: number;
  kpi_id: number;
  value: number;
  date: string;
  created_at: string;
}

// KPI store
const kpiStore = writable<KPI[]>([]);
export const kpis = derived(kpiStore, $kpis => $kpis);

let tokenValue: string | null = null;
token.subscribe(value => {
  tokenValue = value;
});

/**
 * Fetch all KPIs
 */
export async function fetchKPIs() {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.get('kpis', tokenValue);
    if (response.status === 'success' && response.data) {
      kpiStore.set(response.data);
      return { success: true };
    }

    return { success: false, message: 'Failed to fetch KPIs' };
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
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.get(`kpis/${id}`, tokenValue);
    if (response.status === 'success' && response.data) {
      return { success: true, kpi: response.data };
    }

    return { success: false, message: 'Failed to fetch KPI' };
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
export async function createKPI(data: Omit<KPI, 'id' | 'user_id' | 'created_at'>) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.post('kpis', data, tokenValue);
    if (response.status === 'success' && response.data) {
      // Update the store with the new KPI
      kpiStore.update(kpis => [...kpis, response.data]);
      return { success: true, kpi: response.data };
    }

    return { success: false, message: 'Failed to create KPI' };
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
export async function updateKPI(id: number, data: Partial<KPI>) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.put(`kpis/${id}`, data, tokenValue);
    if (response.status === 'success') {
      // Update the store with the modified KPI
      kpiStore.update(kpis => kpis.map(kpi => 
        kpi.id === id ? { ...kpi, ...data } : kpi
      ));
      return { success: true };
    }

    return { success: false, message: 'Failed to update KPI' };
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
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.del(`kpis/${id}`, tokenValue);
    if (response.status === 'success') {
      // Remove the KPI from the store
      kpiStore.update(kpis => kpis.filter(kpi => kpi.id !== id));
      return { success: true };
    }

    return { success: false, message: 'Failed to delete KPI' };
  } catch (error) {
    console.error(`Error deleting KPI ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete KPI' 
    };
  }
} 