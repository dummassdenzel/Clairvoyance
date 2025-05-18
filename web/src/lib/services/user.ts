/**
 * User service for handling user-related operations
 */
import * as api from './api';
import { writable, derived } from 'svelte/store';
import { token } from './auth';

// Types
export interface User {
  id: number;
  username: string;
  email: string;
  role: 'admin' | 'manager' | 'user';
  created_at: string;
  updated_at: string;
}

// User store
const userStore = writable<User[]>([]);
export const users = derived(userStore, $users => $users);

let tokenValue: string | null = null;
token.subscribe(value => {
  tokenValue = value;
});

/**
 * Fetch all users (admin only)
 */
export async function fetchUsers() {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.get('users', tokenValue);
    if (response.status === 'success' && response.data) {
      userStore.set(response.data);
      return { success: true };
    }

    return { success: false, message: 'Failed to fetch users' };
  } catch (error) {
    console.error('Error fetching users:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch users' 
    };
  }
}

/**
 * Fetch a single user by ID
 */
export async function fetchUser(id: number) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.get(`users/${id}`, tokenValue);
    if (response.status === 'success' && response.data) {
      return { success: true, user: response.data };
    }

    return { success: false, message: 'Failed to fetch user' };
  } catch (error) {
    console.error(`Error fetching user ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch user' 
    };
  }
}

/**
 * Create a new user (admin only)
 */
export async function createUser(data: { username: string; email: string; password: string; role: User['role'] }) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.post('users', data, tokenValue);
    if (response.status === 'success' && response.data) {
      // Update the store with the new user
      userStore.update(users => [...users, response.data]);
      return { success: true, user: response.data };
    }

    return { 
      success: false, 
      message: response.message || 'Failed to create user' 
    };
  } catch (error) {
    console.error('Error creating user:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to create user' 
    };
  }
}

/**
 * Update an existing user
 */
export async function updateUser(id: number, data: Partial<User> & { password?: string }) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.put(`users/${id}`, data, tokenValue);
    if (response.status === 'success') {
      // Update the store with the modified user
      userStore.update(users => users.map(user => 
        user.id === id ? { ...user, ...data } : user
      ));
      
      return { success: true };
    }

    return { 
      success: false, 
      message: response.message || 'Failed to update user' 
    };
  } catch (error) {
    console.error(`Error updating user ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to update user' 
    };
  }
}

/**
 * Delete a user (admin only)
 */
export async function deleteUser(id: number) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.del(`users/${id}`, tokenValue);
    if (response.status === 'success') {
      // Remove the user from the store
      userStore.update(users => users.filter(user => user.id !== id));
      return { success: true };
    }

    return { 
      success: false, 
      message: response.message || 'Failed to delete user' 
    };
  } catch (error) {
    console.error(`Error deleting user ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete user' 
    };
  }
}

/**
 * Update the current user's profile
 */
export async function updateProfile(data: { username?: string; email?: string; current_password?: string; new_password?: string }) {
  try {
    if (!tokenValue) {
      console.error('No token available');
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.put('profile', data, tokenValue);
    if (response.status === 'success') {
      return { success: true };
    }

    return { 
      success: false, 
      message: response.message || 'Failed to update profile' 
    };
  } catch (error) {
    console.error('Error updating profile:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to update profile' 
    };
  }
} 