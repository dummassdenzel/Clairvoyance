/**
 * Authentication service for user login and registration
 */
import * as api from './api';
import { writable, derived, get } from 'svelte/store';

// Define user type
export interface User {
  id: number;
  username: string;
  email: string;
  role: 'admin' | 'manager' | 'user';
}

// Define auth state
export interface AuthState {
  isAuthenticated: boolean;
  user: User | null;
  token: string | null;
  tokenExpiry: number | null;
}

// Initial auth state
const initialState: AuthState = {
  isAuthenticated: false,
  user: null,
  token: localStorage.getItem('token'),
  tokenExpiry: localStorage.getItem('tokenExpiry') ? parseInt(localStorage.getItem('tokenExpiry') || '0', 10) : null
};

// Create Svelte store for auth state
const authStore = writable<AuthState>(initialState);
export const token = derived(authStore, $authStore => $authStore.token);

/**
 * Update the authentication state
 */
function updateAuthState(state: Partial<AuthState>) {
  authStore.update(currentState => {
    const newState = { ...currentState, ...state };
    
    if (state.token !== undefined) {
      if (state.token) {
        localStorage.setItem('token', state.token);
      } else {
        localStorage.removeItem('token');
      }
    }
    
    if (state.tokenExpiry !== undefined) {
      if (state.tokenExpiry) {
        localStorage.setItem('tokenExpiry', state.tokenExpiry.toString());
      } else {
        localStorage.removeItem('tokenExpiry');
      }
    }
    
    return newState;
  });
}

/**
 * Verify if the current session is valid
 */
export async function verifySession(): Promise<boolean> {
  const currentState = get(authStore);
  
  // If no token, clearly not authenticated
  if (!currentState.token) {
    return false;
  }
  
  // If token has expired, clear it
  if (currentState.tokenExpiry && currentState.tokenExpiry < Date.now()) {
    logout();
    return false;
  }
  
  // Otherwise, verify the token with the server
  try {
    const response = await api.get('auth/verify', currentState.token);
    
    if (response.status === 'success' && response.data) {
      // Update the auth state with the user info from verify
      updateAuthState({
        isAuthenticated: true,
        user: response.data.user
      });
      return true;
    } else {
      // Token invalid, clear auth state
      logout();
      return false;
    }
  } catch (error) {
    console.error('Failed to verify token:', error);
    // Don't logout automatically on network errors
    return false;
  }
}

/**
 * Log in a user
 */
export async function login(username: string, password: string) {
  try {
    const response = await api.post('auth/login', { username, password });
    
    if (response.status === 'success' && response.data) {
      // Calculate token expiry (e.g., 24 hours from now)
      const expiryTime = Date.now() + (24 * 60 * 60 * 1000);
      
      updateAuthState({
        isAuthenticated: true,
        user: response.data.user,
        token: response.data.token,
        tokenExpiry: expiryTime
      });
      
      return { success: true };
    } else {
      return { 
        success: false, 
        message: response.message || 'Login failed. Please check your credentials.' 
      };
    }
  } catch (error) {
    console.error('Login error:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to connect to the server' 
    };
  }
}

/**
 * Register a new user
 */
export async function register(username: string, email: string, password: string) {
  try {
    const response = await api.post('auth/register', { username, email, password });
    
    if (response.status === 'success' && response.data) {
      // Calculate token expiry (e.g., 24 hours from now)
      const expiryTime = Date.now() + (24 * 60 * 60 * 1000);
      
      updateAuthState({
        isAuthenticated: true,
        user: response.data.user,
        token: response.data.token,
        tokenExpiry: expiryTime
      });
      
      return { success: true };
    } else {
      return { 
        success: false, 
        message: response.message || 'Registration failed. Please try again.' 
      };
    }
  } catch (error) {
    console.error('Registration error:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to connect to the server' 
    };
  }
}

/**
 * Log out the current user
 */
export async function logout() {
  // Clear the auth state
  updateAuthState({
    isAuthenticated: false,
    user: null,
    token: null,
    tokenExpiry: null
  });
  
  return { success: true };
}

// Export the auth store
export { authStore }; 