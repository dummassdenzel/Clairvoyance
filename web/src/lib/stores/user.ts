/**
 * User service for handling user-related operations
 */
import { writable, derived } from 'svelte/store';
import * as api from '../services/api';
import { token as authTokenStore, type AuthenticatedUser } from './auth';
import { get } from 'svelte/store';

export interface UserProfile {
  id: number;
  username: string;
  email: string;
  role: 'admin' | 'editor' | 'viewer';
  created_at: string;
  updated_at?: string;
};

type UsersState = {
  users: UserProfile[];
  loading: boolean;
  error: string | null;
};

// Initialize users store
const initialState: UsersState = {
  users: [],
  loading: false,
  error: null
};

// Create a writable store
const userStore = writable<UsersState>(initialState);

// Create a derived store for just the users array
export const users = derived(userStore, $store => $store.users);

/**
 * Fetch all users from the API
 */
export async function fetchUsers() {
  userStore.update(state => ({ ...state, loading: true, error: null }));
  
  try {
    const token = get(authTokenStore);
    
    if (!token) {
      userStore.update(state => ({
        ...state,
        loading: false,
        error: 'Authentication required'
      }));
      return { success: false, message: 'Authentication required' };
    }
    
    const usersData = await api.get('/users', token) as UserProfile[];
    
    userStore.update(state => ({
      ...state,
      users: usersData,
      loading: false
    }));
    return { success: true, users: usersData };
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to fetch users';
    userStore.update(state => ({
      ...state,
      loading: false,
      error: errorMessage
    }));
    return { success: false, message: errorMessage };
  }
}

/**
 * Fetch a single user by ID
 */
export async function fetchUser(id: number) {
  try {
    const token = get(authTokenStore);
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    const userData = await api.get(`/users/${id}`, token) as UserProfile;
    return { success: true, user: userData };
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to fetch user';
    return { success: false, message: errorMessage };
  }
}

/**
 * Create a new user
 */
export async function createUser(userData: {
  username: string;
  email: string;
  password: string;
  role: 'admin' | 'editor' | 'viewer';
}) {
  try {
    const token = get(authTokenStore);
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    const newUser = await api.post('/users', userData, token) as UserProfile;
    // Update the users store with the new user
    userStore.update(state => ({
      ...state,
      users: [...state.users, newUser]
    }));
    return { success: true, user: newUser };
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to create user';
    return { success: false, message: errorMessage };
  }
}

/**
 * Update an existing user
 */
export async function updateUser(id: number, userData: {
  username?: string;
  email?: string;
  password?: string;
  role?: 'admin' | 'editor' | 'viewer';
}) {
  try {
    const token = get(authTokenStore);
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    const updatedUser = await api.put(`/users/${id}`, userData, token) as UserProfile;
    // Update the user in the store
    userStore.update(state => ({
      ...state,
      users: state.users.map(user => 
        user.id === id ? { ...user, ...updatedUser } : user
      )
    }));
    return { success: true, user: updatedUser };
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to update user';
    return { success: false, message: errorMessage };
  }
}

/**
 * Delete a user
 */
export async function deleteUser(id: number) {
  try {
    const token = get(authTokenStore);
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    await api.del(`/users/${id}`, token);
    // Remove the user from the store
    userStore.update(state => ({
      ...state,
      users: state.users.filter(user => user.id !== id)
    }));
    return { success: true, message: 'User deleted successfully' };
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to delete user';
    return { success: false, message: errorMessage };
  }
}

/**
 * Update the current user's profile
 */
export async function updateProfile(data: { username?: string; email?: string; current_password?: string; new_password?: string }) {
  try {
    const token = get(authTokenStore);
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }

    // The backend for PUT /profile returns the updated user object in the 'data' field upon success.
    // Or it might return null/empty if no specific data is returned for this operation, just success.
    // Assuming it might return the updated user or at least confirm success.
    await api.put('profile', data, token); // If this doesn't throw, it's a success.
    // Optionally, if the backend returns the updated user, you could fetch the current user again or update authStore's user.
    return { success: true };

  } catch (error) {
    console.error('Error updating profile:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to update profile'
    };
  }
}