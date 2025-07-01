import { browser } from '$app/environment';

const API_BASE = 'http://localhost/clairvoyance-v3/api';

export async function login(data: { email: string; password: string }) {
  const res = await fetch(`${API_BASE}/auth.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data)
  });
  return await res.json();
}

export async function logout() {
  const res = await fetch(`${API_BASE}/auth.php?action=logout`, {
    method: 'POST',
    credentials: 'include'
  });
  return await res.json();
}

export async function register(data: { email: string; password: string; role: string }) {
  const res = await fetch(`${API_BASE}/users.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data)
  });
  return await res.json();
}

export async function getCurrentUser() {
  // Optionally, implement a /me endpoint or infer from session
  // For now, return null (to be implemented if needed)
  return null;
} 