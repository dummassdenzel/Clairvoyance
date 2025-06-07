/**
 * Authentication service for user login and registration
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';

// Check if we're in a browser environment
const browser = typeof window !== 'undefined';

// Define authenticated user type
export interface AuthenticatedUser {
  id: number;
  username: string;
  email: string;
  role: 'admin' | 'editor' | 'viewer';
}

// Define auth state
export interface AuthState {
  isAuthenticated: boolean;
  user: AuthenticatedUser | null;
  token: string | null;
  tokenExpiry: number | null;
}

// Initial auth state
const initialState: AuthState = {
  isAuthenticated: false,
  user: null,
  token: browser ? localStorage.getItem('token') : null,
  tokenExpiry: browser && localStorage.getItem('tokenExpiry') 
    ? parseInt(localStorage.getItem('tokenExpiry') || '0', 10) 
    : null
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
    
    if (browser && state.token !== undefined) {
      if (state.token) {
        localStorage.setItem('token', state.token);
      } else {
        localStorage.removeItem('token');
      }
    }
    
    if (browser && state.tokenExpiry !== undefined) {
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
    const verifiedUserData = await api.get('auth/verify', currentState.token);
    
    // If api.get succeeds, verifiedUserData will contain the backend's 'data' object, which is { user: { ... } }
    if (verifiedUserData && verifiedUserData.user) {
      updateAuthState({
        isAuthenticated: true,
        user: verifiedUserData.user
      });
      return true;
    } else {
      // This case should ideally not be reached if api.get throws on error or invalid structure as expected.
      // However, as a safeguard or if backend returns success:true but no user data:
      logout();
      return false;
    }
  } catch (error) {
    console.error('Failed to verify token:', error);
    // If the error is due to an invalid token (e.g., 401), the API might throw, leading here.
    // Consider if logout() should be called for specific error types (e.g., auth errors vs. network errors).
    // For now, maintaining existing behavior: don't logout automatically on network/generic errors.
    // If the token is truly invalid, the backend would have indicated success:false, and api.get would throw.
    // If it's a 401, response.ok would be false, and api.get would throw.
    logout(); // Let's logout if verification fails for any server-related reason.
    return false;
  }
}

/**
 * Log in a user
 */
export async function login(username: string, password: string) {
  try {
    const response = await api.post('auth/login', { username, password });
    
    // The API returns { status: 'success', message: string, data: { user: {...}, token: string } }
    if (response && response.user && response.token) {
      const expiryTime = Date.now() + (24 * 60 * 60 * 1000);
      
      updateAuthState({
        isAuthenticated: true,
        user: response.user,
        token: response.token,
        tokenExpiry: expiryTime
      });
      
      return { 
        success: true,
        user: response.user
      };
    } else {
      return { 
        success: false, 
        message: 'Login failed: Unexpected response from server.' 
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
    const registrationData = await api.post('auth/register', { username, email, password });
    
    // If api.post succeeds, registrationData will contain the backend's 'data' object: { token: "...", user: { ... } }
    if (registrationData && registrationData.token && registrationData.user) {
      const expiryTime = Date.now() + (24 * 60 * 60 * 1000);
      
      updateAuthState({
        isAuthenticated: true,
        user: registrationData.user,
        token: registrationData.token,
        tokenExpiry: expiryTime
      });
      
      return { success: true };
    } else {
      // This case implies the API call succeeded (no error thrown) but data was not as expected.
      return { 
        success: false, 
        message: 'Registration failed: Unexpected response from server.' 
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