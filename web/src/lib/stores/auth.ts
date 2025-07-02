import { writable } from 'svelte/store';
import * as api from '$lib/services/api';

export const user = writable<{ id: number; email: string; role: string } | null>(null);
export const authError = writable<string | null>(null);
export const authLoaded = writable<boolean>(false);

// On load, check if user is logged in
if (typeof window !== 'undefined') {
  api.getCurrentUser().then(response => {
    if (response && response.status === 'success' && response.data && response.data.user) {
      user.set(response.data.user);
    } else {
      user.set(null);
    }
  }).finally(() => {
    authLoaded.set(true);
  });
}

export async function login(email: string, password:string) {
  const response = await api.login({ email, password });
  if (response && response.status === 'success' && response.data && response.data.user) {
    user.set(response.data.user);
    authError.set(null);
    return true;
  } else {
    user.set(null);
    authError.set(response.message || 'Login failed');
    return false;
  }
}

export async function logout() {
  await api.logout();
  user.set(null);
}

export async function register(email: string, password: string, role: string) {
  const res = await api.register({ email, password, role });
  if (res.status === 'success') {
    authError.set(null);
    return true;
  } else {
    authError.set(res.message || 'Registration failed');
    return false;
  }
} 