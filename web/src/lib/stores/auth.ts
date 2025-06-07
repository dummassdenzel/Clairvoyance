/**
 * Authentication service for user login and registration
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';

// Types
export type UserRole = 'admin' | 'editor' | 'viewer';

export interface AuthenticatedUser {
  id: number;
  username: string;
  email: string;
  role: UserRole;
}

export interface AuthState {
  isAuthenticated: boolean;
  user: AuthenticatedUser | null;
  token: string | null;
  tokenExpiry: number | null;
}

export interface AuthResponse {
  success: boolean;
  message?: string;
  user?: AuthenticatedUser;
}

// Constants
const TOKEN_KEY = 'auth_token';
const TOKEN_EXPIRY_KEY = 'auth_token_expiry';
const TOKEN_EXPIRY_DURATION = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

// Check if we're in a browser environment
const isBrowser = typeof window !== 'undefined';

// Token management
const TokenManager = {
  getToken: (): string | null => 
    isBrowser ? localStorage.getItem(TOKEN_KEY) : null,
    
  setToken: (token: string | null): void => {
    if (!isBrowser) return;
    if (token) {
      localStorage.setItem(TOKEN_KEY, token);
    } else {
      localStorage.removeItem(TOKEN_KEY);
    }
  },
  
  getExpiry: (): number | null => {
    if (!isBrowser) return null;
    const expiry = localStorage.getItem(TOKEN_EXPIRY_KEY);
    return expiry ? parseInt(expiry, 10) : null;
  },
  
  setExpiry: (expiry: number | null): void => {
    if (!isBrowser) return;
    if (expiry) {
      localStorage.setItem(TOKEN_EXPIRY_KEY, expiry.toString());
    } else {
      localStorage.removeItem(TOKEN_EXPIRY_KEY);
    }
  },
  
  clear: (): void => {
    if (!isBrowser) return;
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(TOKEN_EXPIRY_KEY);
  }
};

// Initial auth state
const initialState: AuthState = {
  isAuthenticated: false,
  user: null,
  token: TokenManager.getToken(),
  tokenExpiry: TokenManager.getExpiry()
};

// Create Svelte store for auth state
const authStore = writable<AuthState>(initialState);
export const token = derived(authStore, $authStore => $authStore.token);

/**
 * Update the authentication state
 */
function updateAuthState(state: Partial<AuthState>): void {
  authStore.update(currentState => {
    const newState = { ...currentState, ...state };
    
    if (state.token !== undefined) {
      TokenManager.setToken(state.token);
    }
    
    if (state.tokenExpiry !== undefined) {
      TokenManager.setExpiry(state.tokenExpiry);
    }
    
    return newState;
  });
}

/**
 * Verify if the current session is valid
 */
export async function verifySession(): Promise<boolean> {
  const currentState = get(authStore);
  
  if (!currentState.token) {
    return false;
  }
  
  if (currentState.tokenExpiry && currentState.tokenExpiry < Date.now()) {
    await logout();
    return false;
  }
  
  try {
    const response = await api.get('auth/verify', currentState.token);
    
    if (!response?.user) {
      await logout();
      return false;
    }
    
    updateAuthState({
      isAuthenticated: true,
      user: response.user
    });
    
    return true;
  } catch (error) {
    console.error('Session verification failed:', error);
    await logout();
    return false;
  }
}

/**
 * Log in a user
 */
export async function login(username: string, password: string): Promise<AuthResponse> {
  try {
    const response = await api.post('auth/login', { username, password });
    
    if (!response?.user || !response?.token) {
      return {
        success: false,
        message: 'Invalid server response'
      };
    }
    
    const expiryTime = Date.now() + TOKEN_EXPIRY_DURATION;
    
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
  } catch (error) {
    console.error('Login failed:', error);
    return {
      success: false,
      message: error instanceof Error ? error.message : 'Authentication failed'
    };
  }
}

/**
 * Register a new user
 */
export async function register(
  username: string,
  email: string,
  password: string
): Promise<AuthResponse> {
  try {
    const response = await api.post('auth/register', { username, email, password });
    
    if (!response?.user || !response?.token) {
      return {
        success: false,
        message: 'Invalid server response'
      };
    }
    
    const expiryTime = Date.now() + TOKEN_EXPIRY_DURATION;
    
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
  } catch (error) {
    console.error('Registration failed:', error);
    return {
      success: false,
      message: error instanceof Error ? error.message : 'Registration failed'
    };
  }
}

/**
 * Log out the current user
 */
export async function logout(): Promise<AuthResponse> {
  TokenManager.clear();
  
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