import { browser } from '$app/environment';
import type { 
  ApiResponse, 
  User, 
  Dashboard, 
  Kpi, 
  KpiEntry, 
  ShareToken, 
  DashboardAccess,
  DashboardReport,
  KpiAggregation,
  LoginForm,
  RegisterForm,
  CreateDashboardForm,
  CreateKpiForm,
  CreateKpiEntryForm
} from '$lib/types';

// The base URL for the API
const API_BASE = 'http://localhost/clairvoyance-v3/api';

// Generic request function with proper error handling
async function request<T = any>(method: string, path: string, data?: any): Promise<ApiResponse<T>> {
  const opts: RequestInit = { 
    method, 
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
    }
  };
  
  if (data) {
    opts.body = JSON.stringify(data);
  }

  try {
    const res = await fetch(`${API_BASE}${path}`, opts);
    
    // Handle empty responses (like DELETE 204)
    if (res.status === 204) {
      return { success: true, message: 'Operation completed successfully' };
    }

    const responseData = await res.json();
    
    if (res.ok) {
      return responseData;
    } else {
      // Handle API errors
      throw new Error(responseData.message || responseData.error || `HTTP ${res.status}: ${res.statusText}`);
    }
  } catch (error) {
    if (error instanceof Error) {
      throw error;
    }
    throw new Error('Network error occurred');
  }
}

// HTTP method helpers
async function get<T = any>(path: string): Promise<ApiResponse<T>> {
  return await request<T>('GET', path);
}

async function post<T = any>(path: string, data?: any): Promise<ApiResponse<T>> {
  return await request<T>('POST', path, data);
}

async function put<T = any>(path: string, data?: any): Promise<ApiResponse<T>> {
  return await request<T>('PUT', path, data);
}

async function del<T = any>(path: string): Promise<ApiResponse<T>> {
  return await request<T>('DELETE', path);
}

// --- Authentication Service ---
export async function login(data: LoginForm): Promise<ApiResponse<{ user: User }>> {
  return await post<{ user: User }>('/auth/login', data);
}

export async function logout(): Promise<ApiResponse> {
  return await post('/auth/logout');
}

export async function getCurrentUser(): Promise<ApiResponse<{ user: User }> | null> {
  try {
    return await get<{ user: User }>('/auth/me');
  } catch (error) {
    return null;
  }
}

export async function register(data: RegisterForm): Promise<ApiResponse<{ user: User }>> {
  return await post<{ user: User }>('/auth/register', data);
}

// --- Admin Service ---
export async function getAdminUsers(): Promise<ApiResponse<{ users: User[] }>> {
  return await get<{ users: User[] }>('/admin/users');
}

export async function createUser(data: RegisterForm): Promise<ApiResponse<{ user: User }>> {
  return await post<{ user: User }>('/admin/users', data);
}

export async function updateUserRole(id: number, role: string): Promise<ApiResponse<{ user: User }>> {
  return await put<{ user: User }>(`/admin/users/${id}`, { role });
}

export async function deleteUser(id: number): Promise<ApiResponse> {
  return await del(`/admin/users/${id}`);
}

// --- Dashboard Service ---
export async function getDashboards(): Promise<ApiResponse<{ dashboards: Dashboard[] }>> {
  return await get<{ dashboards: Dashboard[] }>('/dashboards');
}

export async function getDashboard(id: number): Promise<ApiResponse<{ dashboard: Dashboard }>> {
  return await get<{ dashboard: Dashboard }>(`/dashboards/${id}`);
}

export async function createDashboard(data: CreateDashboardForm): Promise<ApiResponse<{ dashboard: Dashboard }>> {
  return await post<{ dashboard: Dashboard }>('/dashboards', data);
}

export async function updateDashboard(id: number, data: Partial<CreateDashboardForm>): Promise<ApiResponse<{ dashboard: Dashboard }>> {
  return await put<{ dashboard: Dashboard }>(`/dashboards/${id}`, data);
}

export async function deleteDashboard(id: number): Promise<ApiResponse> {
  return await del(`/dashboards/${id}`);
}

export async function assignViewer(dashboardId: number, userId: number, permissionLevel: string = 'viewer'): Promise<ApiResponse<{ access: DashboardAccess }>> {
  return await post<{ access: DashboardAccess }>(`/dashboards/${dashboardId}/viewers`, { 
    user_id: userId, 
    permission_level: permissionLevel 
  });
}

export async function removeViewer(dashboardId: number, userId: number): Promise<ApiResponse> {
  return await del(`/dashboards/${dashboardId}/viewers/${userId}`);
}

export async function generateShareToken(dashboardId: number, expiresAt: string): Promise<ApiResponse<{ token: ShareToken }>> {
  return await post<{ token: ShareToken }>(`/dashboards/${dashboardId}/share`, { expires_at: expiresAt });
}

export async function redeemShareToken(token: string): Promise<ApiResponse<{ dashboard_id: number }>> {
  return await post<{ dashboard_id: number }>(`/dashboards/share/${token}`);
}

export async function getDashboardReport(dashboardId: number): Promise<ApiResponse<{ report: DashboardReport }>> {
  return await get<{ report: DashboardReport }>(`/dashboards/${dashboardId}/report`);
}

// --- KPI Service ---
export async function getKpis(): Promise<ApiResponse<Kpi[]>> {
  return await get<Kpi[]>('/kpis');
}

export async function getKpi(id: number): Promise<ApiResponse<{ kpi: Kpi }>> {
  return await get<{ kpi: Kpi }>(`/kpis/${id}`);
}

export async function createKpi(data: CreateKpiForm): Promise<ApiResponse<{ kpi: Kpi }>> {
  return await post<{ kpi: Kpi }>('/kpis', data);
}

export async function updateKpi(id: number, data: Partial<CreateKpiForm>): Promise<ApiResponse<{ kpi: Kpi }>> {
  return await put<{ kpi: Kpi }>(`/kpis/${id}`, data);
}

export async function deleteKpi(id: number): Promise<ApiResponse> {
  return await del(`/kpis/${id}`);
}

export async function getKpiEntries(kpiId: number, startDate?: string, endDate?: string): Promise<ApiResponse<{ entries: KpiEntry[] }>> {
  const params = new URLSearchParams();
  if (startDate) params.set('start_date', startDate);
  if (endDate) params.set('end_date', endDate);
  
  const queryString = params.toString();
  const url = `/kpis/${kpiId}/entries${queryString ? `?${queryString}` : ''}`;
  
  return await get<{ entries: KpiEntry[] }>(url);
}

export async function getKpiAggregatedData(
  kpiId: number, 
  startDate?: string, 
  endDate?: string, 
  groupBy: 'day' | 'week' | 'month' | 'year' = 'month'
): Promise<ApiResponse<{ aggregated_data: KpiAggregation[] }>> {
  const params = new URLSearchParams();
  if (startDate) params.set('start_date', startDate);
  if (endDate) params.set('end_date', endDate);
  params.set('group_by', groupBy);
  
  const queryString = params.toString();
  const url = `/kpis/${kpiId}/aggregate?${queryString}`;
  
  return await get<{ aggregated_data: KpiAggregation[] }>(url);
}

// --- KPI Entry Service ---
export async function createKpiEntry(data: CreateKpiEntryForm): Promise<ApiResponse<{ entry: KpiEntry }>> {
  return await post<{ entry: KpiEntry }>('/kpi_entries', data);
}

export async function uploadKpiCsv(kpiId: number, file: File): Promise<ApiResponse<{ inserted: number; failed: number; errors: string[] }>> {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('kpi_id', kpiId.toString());

  const res = await fetch(`${API_BASE}/kpi_entries`, {
    method: 'POST',
    credentials: 'include',
    body: formData
  });

  if (!res.ok) {
    const errorData = await res.json().catch(() => ({ message: 'Upload failed' }));
    throw new Error(errorData.message || errorData.error || 'Upload failed');
  }

  return await res.json();
}

export async function updateKpiEntry(entryId: number, data: { date?: string; value?: number }): Promise<ApiResponse<void>> {
  return await put<void>(`/kpi_entries/${entryId}`, data);
}

export async function deleteKpiEntry(entryId: number): Promise<ApiResponse<void>> {
  return await del<void>(`/kpi_entries/${entryId}`);
}

// --- Dashboard access functions ---
export async function getDashboardUsers(dashboardId: number): Promise<ApiResponse<{ users: any[] }>> {
  return await get<{ users: any[] }>(`/dashboards/${dashboardId}/users`);
}

// --- User lookup functions ---
export async function findUserByEmail(email: string): Promise<ApiResponse<User>> {
  const response = await fetch(`${API_BASE}/users`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify({ email })
  });

  if (!response.ok) {
    const errorData = await response.json();
    throw new Error(errorData.error || 'Failed to find user');
  }

  return await response.json();
}

// --- Legacy compatibility functions (for gradual migration) ---
export async function getUsers(): Promise<ApiResponse<{ users: User[] }>> {
  return await getAdminUsers();
}

export async function getKpiById(id: number): Promise<ApiResponse<{ kpi: Kpi }>> {
  return await getKpi(id);
}

export async function getAggregateKpiValue(
  kpiId: number, 
  aggregationType: string, 
  startDate?: string, 
  endDate?: string
): Promise<ApiResponse<{ value: number }>> {
  const params = new URLSearchParams();
  params.set('type', aggregationType);
  if (startDate) params.set('start_date', startDate);
  if (endDate) params.set('end_date', endDate);

  const queryString = params.toString();
  const url = `/kpis/${kpiId}/aggregate?${queryString}`;

  return await get<{ value: number }>(url);
}

// Legacy function names for backward compatibility
export async function generateShareLink(dashboardId: string): Promise<ApiResponse<{ token: string }>> {
  const response = await generateShareToken(parseInt(dashboardId), '2024-12-31 23:59:59');
  if (response.success && response.data?.token) {
    return { 
      success: true, 
      data: { token: response.data.token.token } // Extract the string token
    };
  } else {
    return { 
      success: false, 
      error: response.message || 'Failed to generate share token' 
    };
  }
}