/**
 * User service for handling user-related operations
 */
import { writable, derived } from 'svelte/store';
import { api } from './api';
import { authStore } from './auth';

export type User = {
  id: number;
  username: string;
  email: string;
  role: string;
  created_at: string;
  updated_at?: string;
};

type UsersState = {
  users: User[];
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
    const token = authStore.getToken();
    
    if (!token) {
      userStore.update(state => ({
        ...state,
        loading: false,
        error: 'Authentication required'
      }));
      return { success: false, message: 'Authentication required' };
    }
    
    const response = await api.get('/users', token);
    
    if (response.success) {
      userStore.update(state => ({
        ...state,
        users: response.data,
        loading: false
      }));
      return { success: true, users: response.data };
    } else {
      userStore.update(state => ({
        ...state,
        loading: false,
        error: response.message
      }));
      return { success: false, message: response.message };
    }
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
    const token = authStore.getToken();
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    const response = await api.get(`/users/${id}`, token);
    
    if (response.success) {
      return { success: true, user: response.data };
    } else {
      return { success: false, message: response.message };
    }
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
  role: string;
}) {
  try {
    const token = authStore.getToken();
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    const response = await api.post('/users', userData, token);
    
    if (response.success) {
      // Update the users store with the new user
      userStore.update(state => ({
        ...state,
        users: [...state.users, response.data]
      }));
      return { success: true, user: response.data };
    } else {
      return { success: false, message: response.message };
    }
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
  role?: string;
}) {
  try {
    const token = authStore.getToken();
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    const response = await api.put(`/users/${id}`, userData, token);
    
    if (response.success) {
      // Update the user in the store
      userStore.update(state => ({
        ...state,
        users: state.users.map(user => 
          user.id === id ? { ...user, ...response.data } : user
        )
      }));
      return { success: true, user: response.data };
    } else {
      return { success: false, message: response.message };
    }
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
    const token = authStore.getToken();
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }
    
    const response = await api.del(`/users/${id}`, token);
    
    if (response.success) {
      // Remove the user from the store
      userStore.update(state => ({
        ...state,
        users: state.users.filter(user => user.id !== id)
      }));
      return { success: true, message: 'User deleted successfully' };
    } else {
      return { success: false, message: response.message };
    }
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
    const token = authStore.getToken();
    
    if (!token) {
      return { success: false, message: 'Authentication required' };
    }

    const response = await api.put('profile', data, token);
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

// Mock functions for development (will use the API in production)
if (import.meta.env.DEV) {
  // Override the functions with mock implementations
  const mockUsers: User[] = [
    {
      id: 1,
      username: 'admin',
      email: 'admin@example.com',
      role: 'admin',
      created_at: '2023-01-01T00:00:00Z'
    },
    {
      id: 2,
      username: 'manager',
      email: 'manager@example.com',
      role: 'manager',
      created_at: '2023-01-02T00:00:00Z'
    },
    {
      id: 3,
      username: 'user',
      email: 'user@example.com',
      role: 'user',
      created_at: '2023-01-03T00:00:00Z'
    }
  ];
  
  // Initialize the store with mock data
  userStore.update(state => ({
    ...state,
    users: mockUsers
  }));
} 