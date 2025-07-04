import { browser } from '$app/environment';

// The base URL for the API.
// All requests go through the main index.php router via .htaccess rewrites.
const API_BASE = 'http://localhost/clairvoyance-v3/api';

async function request(method: string, path: string, data?: any) {
  const opts: RequestInit = { method, credentials: 'include' };
  if (data) {
    opts.headers = { 'Content-Type': 'application/json' };
    opts.body = JSON.stringify(data);
  }

  const res = await fetch(`${API_BASE}${path}`, opts);
  if (res.ok) {
    // For DELETE requests with no body, res.json() will fail.
    if (method === 'DELETE' && res.status === 204) {
      return { status: 'success' };
    }
    return res.json();
  }

  // Handle non-ok responses
  const errorBody = await res.json().catch(() => ({ message: 'An unknown error occurred' }));
  throw new Error(errorBody.message || 'API request failed');
}

async function get(path: string) {
  return await request('GET', path);
}

async function post(path: string, data: any) {
  return await request('POST', path, data);
}

async function put(path: string, data: any) {
  return await request('PUT', path, data);
}

async function del(path: string) {
  return await request('DELETE', path);
}

// --- Auth Service ---
// Note: The auth routes are not fully RESTful yet and use query parameters.
// This service matches the current backend implementation.

export async function login(data: { email: string; password: string }) {
  return await post('/auth/login', data);
}

export async function logout() {
  return await post('/auth/logout', {});
}

export async function getCurrentUser() {
  try {
    return await get('/auth/me');
  } catch (e) {
    // If the request fails (e.g., 401 Unauthorized), return null.
    return null;
  }
}

export async function register(data: { email: string; password: string; role?: string }) {
  return await post('/auth/register', data);
}

// --- User Service ---

export async function getUsers() {
  return await get('/users');
}

// --- Dashboard Service ---

export async function getDashboards() {
  return await get('/dashboards');
}

export async function deleteDashboard(id: string) {
  return await del(`/dashboards/${id}`);
}

export async function getDashboard(id: string) {
  return await get(`/dashboards/${id}`);
}

export async function createDashboard(data: { name:string; description?: string; layout?: any[] }) {
  return await post('/dashboards', data);
}

export async function updateDashboard(id: string, data: { name?: string; description?: string; layout?: any[] }) {
  return await put(`/dashboards/${id}`, data);
}

export async function assignViewer(dashboard_id: string, user_id: string) {
  return await post(`/dashboards/${dashboard_id}/viewers`, { user_id });
}

export async function generateShareLink(dashboard_id: string) {
  return await post(`/dashboards/${dashboard_id}/share`, {});
}

export async function redeemShareLink(token: string) {
  return await post(`/dashboards/share/${token}`, {});
}

export async function removeViewer(dashboardId: string, viewerId: string) {
  return await del(`dashboards/${dashboardId}/viewers/${viewerId}`);
}

export async function addViewer(dashboardId: string, email: string) {
  return await post(`dashboards/${dashboardId}/viewers`, { email });
}

// --- KPI Service ---

export async function getKpis() {
  // This function has custom error handling that we want to preserve for the modal.
  try {
    return await get('/kpis');
  } catch (error: any) {
    return { status: 'error', message: error.message || 'A network error occurred while fetching KPIs.' };
  }
}

export async function getKpiById(id: number) {
  return await get(`/kpis/${id}`);
}

export async function createKpi(data: { name: string; target: string; rag_red: string; rag_amber: string }) {
  return await post('/kpis', data);
}

export async function deleteKpi(id: number) {
  return await del(`/kpis/${id}`);
}

export async function updateKpi(data: { id: number; name: string; target: string; rag_red: string; rag_amber: string }) {
  return await put(`/kpis/${data.id}`, data);
}

// --- KPI Entry Service ---

export async function getAggregateKpiValue(kpiId: number, aggregationType: string, startDate?: string, endDate?: string) {
  const params = new URLSearchParams();
  params.set('type', aggregationType);
  if (startDate) params.set('start_date', startDate);
  if (endDate) params.set('end_date', endDate);

  const queryString = params.toString();
  const url = `/kpis/${kpiId}/aggregate?${queryString}`;

  return await request('GET', url);
}

export async function getKpiEntries(kpiId: number, startDate?: string, endDate?: string) {
  const params = new URLSearchParams();
  if (startDate) params.set('start_date', startDate);
  if (endDate) params.set('end_date', endDate);

  const queryString = params.toString();
  const url = `/kpis/${kpiId}/entries${queryString ? `?${queryString}` : ''}`;

  return await request('GET', url);
}

export async function uploadKpiCsv(kpi_id: number, file: File) {
  const formData = new FormData();
  formData.append('kpi_id', String(kpi_id));
  formData.append('file', file);

  const res = await fetch(`${API_BASE}/kpi_entries/upload`, {
    method: 'POST',
    credentials: 'include',
    body: formData
  });
  return await res.json();
} 