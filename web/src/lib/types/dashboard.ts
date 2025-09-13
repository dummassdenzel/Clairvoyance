// Dashboard Types
export interface Dashboard {
  id: number;
  name: string;
  description?: string;
  layout: DashboardWidget[];
  user_id: number;
  created_at?: string;
  updated_at?: string;
}

export interface DashboardWidget {
  id: number;
  x: number;
  y: number;
  w: number;
  h: number;
  title: string;
  type: 'line' | 'bar' | 'number' | 'pie' | 'table';
  kpi_id: number;
}

export interface DashboardAccess {
  dashboard_id: number;
  user_id: number;
  user?: User;
}

export interface DashboardReport {
  dashboard: Dashboard;
  kpis: Array<{
    kpi: Kpi;
    entries: KpiEntry[];
    aggregated_data?: KpiAggregation[];
  }>;
}

// Import types that are used in this file
import type { User } from './user';
import type { Kpi, KpiEntry, KpiAggregation } from '$lib/types/kpi';
