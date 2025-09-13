// Form Types
export interface LoginForm {
  email: string;
  password: string;
}

export interface RegisterForm {
  email: string;
  password: string;
  role: 'admin' | 'editor' | 'viewer';
}

export interface CreateDashboardForm {
  name: string;
  description?: string;
  layout?: DashboardWidget[];
}

export interface CreateKpiForm {
  name: string;
  direction: 'higher_is_better' | 'lower_is_better';
  target: number;
  rag_red: number;
  rag_amber: number;
  format_prefix?: string;
  format_suffix?: string;
}

export interface CreateKpiEntryForm {
  kpi_id: number;
  date: string;
  value: number;
}

// Import types that are used in this file
import type { DashboardWidget } from './dashboard';
