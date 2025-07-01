import { writable } from 'svelte/store';
import * as api from '$lib/services/api';

export const user = writable<{ id: number; email: string; role: string } | null>(null);
export const authError = writable<string | null>(null);

export async function login(email: string, password: string) {
  const res = await api.login({ email, password });
  if (res.success) {
    user.set(res.user);
    authError.set(null);
    return true;
  } else {
    user.set(null);
    authError.set(res.error || 'Login failed');
    return false;
  }
}

export async function logout() {
  await api.logout();
  user.set(null);
}

export async function register(email: string, password: string, role: string) {
  const res = await api.register({ email, password, role });
  if (res.id) {
    authError.set(null);
    return true;
  } else {
    authError.set(res.error || 'Registration failed');
    return false;
  }
} 