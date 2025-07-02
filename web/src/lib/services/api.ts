import { browser } from '$app/environment';

// The base URL for the API.
// All requests go through the main index.php router via .htaccess rewrites.
const API_BASE = 'http://localhost/clairvoyance-v3/api';

// --- Auth Service ---
// Note: The auth routes are not fully RESTful yet and use query parameters.
// This service matches the current backend implementation.

export async function login(data: { email: string; password: string }) {
  const res = await fetch(`${API_BASE}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data)
  });
  return await res.json();
}

export async function logout() {
  const res = await fetch(`${API_BASE}/auth/logout`, {
    method: 'POST',
    credentials: 'include'
  });
  return await res.json();
}

export async function getCurrentUser() {
  const res = await fetch(`${API_BASE}/auth/me`, {
    credentials: 'include'
  });
  if (res.ok) return await res.json();
  return null;
}

export async function register(data: { email: string; password: string; role?: string }) {
  const res = await fetch(`${API_BASE}/auth/register`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data)
  });
  return await res.json();
}

// --- User Service ---

export async function getUsers() {
  const res = await fetch(`${API_BASE}/users`, {
    credentials: 'include'
  });
  return await res.json();
}

// --- Dashboard Service ---

export async function getDashboards() {
  const res = await fetch(`${API_BASE}/dashboards`, {
    credentials: 'include'
  });
  return await res.json();
}

export async function getDashboard(id: string) {
  const res = await fetch(`${API_BASE}/dashboards/${id}`, {
    credentials: 'include'
  });
  return await res.json();
}

export async function createDashboard(data: { name: string; layout: any[] }) { // Note: 'widgets' was renamed to 'layout'
  const res = await fetch(`${API_BASE}/dashboards`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data)
  });
  return await res.json();
}

export async function assignViewer(dashboard_id: string, user_id: string) {
  const res = await fetch(`${API_BASE}/dashboards/${dashboard_id}/viewers`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ user_id }) // Only user_id is needed in the body
  });
  return await res.json();
}

export async function removeViewer(dashboard_id: string, user_id: string) {
  const res = await fetch(`${API_BASE}/dashboards/${dashboard_id}/viewers/${user_id}`, {
    method: 'DELETE',
    credentials: 'include'
  });
  return await res.json();
}

// --- KPI Service ---

export async function getKpis() {
  const res = await fetch(`${API_BASE}/kpis`, {
    credentials: 'include'
  });
  return await res.json();
}

export async function createKpi(data: { name: string; target: string; rag_red: string; rag_amber: string }) {
  const res = await fetch(`${API_BASE}/kpis`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data)
  });
  return await res.json();
}

// --- KPI Entry Service ---

export async function getKpiEntries(kpi_id: number) {
  const res = await fetch(`${API_BASE}/kpis/${kpi_id}/entries`, {
    credentials: 'include'
  });
  return await res.json();
}

export async function uploadKpiCsv(file: File) {
  const formData = new FormData();
  formData.append('file', file);
  const res = await fetch(`${API_BASE}/kpi_entries`, {
    method: 'POST',
    credentials: 'include',
    body: formData
  });
  return await res.json();
} 