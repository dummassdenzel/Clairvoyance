// KPI Types
export interface Kpi {
  id: number;
  name: string;
  direction: 'higher_is_better' | 'lower_is_better';
  target: number;
  rag_red: number;
  rag_amber: number;
  format_prefix?: string;
  format_suffix?: string;
  user_id: number;
  created_at?: string;
  updated_at?: string;
}

export interface KpiEntry {
  id: number;
  kpi_id: number;
  date: string;
  value: number;
  created_at?: string;
  updated_at?: string;
}

export interface KpiAggregation {
  period: string;
  value: number;
  count: number;
}

export interface ShareToken {
  id: number;
  dashboard_id: number;
  token: string;
  expires_at: string;
  created_at?: string;
}
