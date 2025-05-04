import { writable } from 'svelte/store';

// Define error types
export type ErrorType = 'error' | 'warning' | 'info' | 'success';

// Define error interface
export interface ErrorMessage {
  type: ErrorType;
  message: string;
  details?: string;
  timestamp: number;
  id: string;
}

// Create the error store
function createErrorStore() {
  const { subscribe, update, set } = writable<ErrorMessage[]>([]);

  return {
    subscribe,
    
    // Add a new error message
    addError: (message: string, type: ErrorType = 'error', details?: string) => {
      const id = Math.random().toString(36).substring(2, 9);
      const timestamp = Date.now();
      
      update(errors => [
        { type, message, details, timestamp, id },
        ...errors
      ].slice(0, 5)); // Keep only the 5 most recent errors
      
      // Auto-remove success and info messages after 5 seconds
      if (type === 'success' || type === 'info') {
        setTimeout(() => {
          update(errors => errors.filter(e => e.id !== id));
        }, 5000);
      }
      
      return id;
    },
    
    // Remove a specific error by ID
    removeError: (id: string) => {
      update(errors => errors.filter(e => e.id !== id));
    },
    
    // Clear all errors
    clearErrors: () => {
      set([]);
    }
  };
}

// Export the store
export const errorStore = createErrorStore();

// Helper functions for different error types
export const showError = (message: string, details?: string) => 
  errorStore.addError(message, 'error', details);

export const showWarning = (message: string, details?: string) => 
  errorStore.addError(message, 'warning', details);

export const showInfo = (message: string, details?: string) => 
  errorStore.addError(message, 'info', details);

export const showSuccess = (message: string, details?: string) => 
  errorStore.addError(message, 'success', details);
