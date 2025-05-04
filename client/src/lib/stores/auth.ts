import { writable } from 'svelte/store';
import { AUTH_ENDPOINTS } from '$lib/config/api';

// Define auth types
type User = {
  id: number;
  username: string;
  email: string;
  verified: boolean;
  created_at: string;
  is_admin?: boolean | number;
  // Add other user properties as needed
};

type AuthState = {
  user: User | null;
  token: string | null;
  isAdmin: boolean;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
};

// Browser check helper
const isBrowser = typeof window !== 'undefined';

// Initial auth state
const initialState: AuthState = {
  user: null,
  token: isBrowser ? localStorage.getItem('auth_token') : null,
  isAdmin: isBrowser ? localStorage.getItem('is_admin') === 'true' || localStorage.getItem('is_admin') === '1' : false,
  isAuthenticated: isBrowser ? !!localStorage.getItem('auth_token') : false,
  isLoading: false,
  error: null
};

// Initialize auth state if we have a token
if (initialState.token) {
  // Try to fetch user data from localStorage
  const userData = localStorage.getItem('user_data');
  if (userData) {
    try {
      const user = JSON.parse(userData);
      if (user && typeof user.id === 'number') {
        initialState.user = user;
        console.log('Loaded user from localStorage:', user);
      }
    } catch (e) {
      console.error('Failed to parse user data:', e);
    }
  }
  // If we have a token but no user data, mark as authenticated anyway
  if (!initialState.user) {
    initialState.isAuthenticated = true;
    console.warn('Authenticated but no user data found in localStorage');
  }
}

// Create the store
const createAuthStore = () => {
  const { subscribe, set, update } = writable<AuthState>(initialState);
  const authState = { subscribe };

  return {
    subscribe,
    get: () => {
      let current: AuthState | undefined;
      const unsubscribe = authState.subscribe(state => {
        current = state;
        return true;
      });
      unsubscribe();
      
      if (!current) {
    return initialState;
  }
  return current;
},
    login: async (username: string, password: string) => {
      update(state => ({ ...state, isLoading: true, error: null }));
      
      try {
        // Use centralized API endpoint
        const response = await fetch(AUTH_ENDPOINTS.LOGIN, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (data.status === 'error') {
          throw new Error(data.message || 'Login failed');
        }

        if (!response.ok) {
          throw new Error('Login failed: Server error');
        }

        // Use backend's is_admin value to set admin status
        const isAdmin = data.user && (data.user.is_admin === 1 || data.user.is_admin === '1' || data.user.is_admin === true);
        // Also store is_admin in user object as boolean
        if (data.user) {
          data.user.is_admin = (data.user.is_admin === 1 || data.user.is_admin === '1' || data.user.is_admin === true);
        }

        // Store auth data (browser-only)
        if (isBrowser) {
          // Store token from backend
          if (data.token) {
            localStorage.setItem('auth_token', data.token);
          }
          localStorage.setItem('is_admin', String(isAdmin));

          // Store user data
          if (data.user) {
            // Ensure user data has all required fields
            const user: User = {
              id: parseInt(data.user.id),
              username: data.user.username,
              email: data.user.email,
              verified: data.user.verified === '1' || data.user.verified === 1,
              created_at: data.user.created_at,
              is_admin: data.user.is_admin === true || data.user.is_admin === 1 || data.user.is_admin === '1'
            };
            localStorage.setItem('user_data', JSON.stringify(user));
          }
        }

        // The server returns { status: 'success', user: {...}, token: ... }
        update(state => ({
          ...state,
          user: data.user ? {
            id: parseInt(data.user.id),
            username: data.user.username,
            email: data.user.email,
            verified: data.user.verified === '1' || data.user.verified === 1,
            created_at: data.user.created_at,
            is_admin: data.user.is_admin === true || data.user.is_admin === 1 || data.user.is_admin === '1'
          } : null,
          token: data.token || null,
          isAdmin,
          isAuthenticated: true,
          isLoading: false
        }));
        
        return true;
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Login failed';
        update(state => ({
          ...state,
          error: errorMessage,
          isLoading: false
        }));
        return false;
      }
    },
    logout: () => {
      // Clear stored data (browser-only)
      if (isBrowser) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('is_admin');
      }
      
      // Reset state
      set({
        user: null,
        token: null,
        isAdmin: false,
        isAuthenticated: false,
        isLoading: false,
        error: null
      });
      
      // Optionally call logout endpoint (browser-only)
      if (isBrowser) {
        fetch('/api/auth/logout', { method: 'POST' }).catch(() => {});
      }
    },
    // Get the auth header for API requests
    getAuthHeader: (): Record<string, string> => {
      if (!isBrowser) return {};
      const token = localStorage.getItem('auth_token');
      return token ? { Authorization: `Bearer ${token}` } : {};
    }
  };
};

// Export the store
export const auth = createAuthStore();
