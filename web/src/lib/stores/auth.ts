import { writable } from 'svelte/store';
import * as api from '$lib/services/api';
import type { User, ApiResponse } from '$lib/types';

export const user = writable<User | null>(null);
export const authError = writable<string | null>(null);
export const authLoaded = writable<boolean>(false);

// On load, check if user is logged in
if (typeof window !== 'undefined') {
  api.getCurrentUser().then((response: ApiResponse<{ user: User }> | null) => {
    if (response?.success && response.data?.user) {
      user.set(response.data.user);
    } else {
      user.set(null);
    }
  }).catch(() => {
    user.set(null);
  }).finally(() => {
    authLoaded.set(true);
  });
}

export async function login(email: string, password: string): Promise<boolean> {
  try {
    const response = await api.login({ email, password });
    
    if (response.success && response.data?.user) {
      user.set(response.data.user);
      authError.set(null);
      return true;
    } else {
      user.set(null);
      authError.set(response.message || 'Login failed');
      return false;
    }
  } catch (error) {
    user.set(null);
    authError.set(error instanceof Error ? error.message : 'Login failed');
    return false;
  }
}

export async function logout(): Promise<void> {
  try {
    await api.logout();
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    user.set(null);
    authError.set(null);
  }
}

export async function register(email: string, password: string, role: string): Promise<boolean> {
  try {
    const response = await api.register({ email, password, role: role as 'admin' | 'editor' | 'viewer' });
    
    if (response.success) {
      authError.set(null);
      return true;
    } else {
      authError.set(response.message || 'Registration failed');
      return false;
    }
  } catch (error) {
    authError.set(error instanceof Error ? error.message : 'Registration failed');
    return false;
  }
} 