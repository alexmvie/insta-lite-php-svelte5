import { errorStore } from '$lib/stores/error';
import { auth } from '$lib/stores/auth';
import { goto } from '$app/navigation';
import { API_BASE_URL } from '$lib/config/api';

/**
 * Handles API responses and errors consistently across the application
 * @param response The fetch response object
 * @param options Configuration options
 * @returns The parsed JSON response data if successful
 */
export async function handleApiResponse(
  response: Response, 
  options: {
    successMessage?: string;
    errorPrefix?: string;
    redirectOnUnauthorized?: boolean;
    redirectPath?: string;
  } = {}
) {
  const { 
    successMessage, 
    errorPrefix = 'API Error', 
    redirectOnUnauthorized = true,
    redirectPath = '/login'
  } = options;
  
  // Parse the JSON response (or handle text if not JSON)
  let data;
  const contentType = response.headers.get('content-type');
  
  try {
    if (contentType && contentType.includes('application/json')) {
      data = await response.json();
    } else {
      data = await response.text();
    }
  } catch {
    errorStore.addError(`${errorPrefix}: Failed to parse response`, 'error', 'The server response could not be processed');
    throw new Error('Failed to parse response');
  }
  
  // Handle different response statuses
  if (response.ok) {
    // Success case
    if (successMessage) {
      errorStore.addError(successMessage, 'success');
    }
    return data;
  } else {
    // Error cases
    let errorMessage = 'An unknown error occurred';
    let errorDetails = '';
    
    // Handle specific status codes
    switch (response.status) {
      case 400:
        errorMessage = typeof data === 'object' && data && 'message' in data ? String(data.message) : 'Invalid request';
        errorDetails = typeof data === 'object' && data && 'error' in data ? String(data.error) : 'The server could not process your request';
        break;
      case 401:
        errorMessage = 'Unauthorized';
        errorDetails = 'Your session may have expired. Please log in again.';
        
        // Handle authentication error
        if (redirectOnUnauthorized) {
          auth.logout();
          goto(redirectPath);
        }
        break;
      case 403:
        errorMessage = 'Access denied';
        errorDetails = 'You do not have permission to perform this action';
        break;
      case 404:
        errorMessage = 'Resource not found';
        errorDetails = 'The requested item could not be found';
        break;
      case 409:
        errorMessage = typeof data === 'object' && data && 'message' in data ? String(data.message) : 'Conflict error';
        errorDetails = typeof data === 'object' && data && 'error' in data ? String(data.error) : 'The requested operation conflicts with the current state';
        break;
      case 500:
        errorMessage = 'Server error';
        errorDetails = 'Something went wrong on the server. Please try again later.';
        break;
      default:
        errorMessage = typeof data === 'object' && data && 'message' in data ? String(data.message) : `Error ${response.status}`;
        errorDetails = typeof data === 'object' && data && 'error' in data ? String(data.error) : 'An unexpected error occurred';
    }
    
    // Show the error in the UI
    errorStore.addError(`${errorPrefix}: ${errorMessage}`, 'error', errorDetails);
    
    // Throw an error for the calling code to handle
    // Create a custom error with additional properties
    interface ApiError extends Error {
      status: number;
      data: unknown;
    }
    
    const error = new Error(errorMessage) as ApiError;
    error.status = response.status;
    error.data = data;
    throw error;
  }
}

/**
 * Wrapper for fetch that handles common API request patterns
 * @param url The URL to fetch
 * @param options Fetch options and error handling configuration
 * @returns The parsed response data
 */
export async function apiRequest(
  url: string,
  options: {
    method?: string;
    headers?: Record<string, string>;
    body?: unknown;
    includeAuth?: boolean;
    successMessage?: string;
    errorPrefix?: string;
    redirectOnUnauthorized?: boolean;
    redirectPath?: string;
  } = {}
) {
  const {
    method = 'GET',
    headers = {},
    body,
    includeAuth = true,
    ...responseOptions
  } = options;
  
  // For URLs that don't include the full path (don't start with http), prepend the API_BASE_URL
  let fullUrl = url;
  if (!url.startsWith('http')) {
    // If the URL already starts with /api, don't add the API_BASE_URL
    if (!url.startsWith('/api')) {
      fullUrl = `${API_BASE_URL}${url.startsWith('/') ? '' : '/'}${url}`;
    }
  }
  
  // Prepare headers
  const requestHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    ...headers
  };
  
  // Add auth token if required
  if (includeAuth) {
    const authHeader = auth.getAuthHeader();
    if (Object.keys(authHeader).length > 0) {
      Object.assign(requestHeaders, authHeader);
    }
  }
  
  // Prepare request options
  const requestOptions: RequestInit = {
    method,
    headers: requestHeaders
  };
  
  // Add body if provided
  if (body) {
    requestOptions.body = typeof body === 'string' ? body : JSON.stringify(body);
  }
  
  try {
    console.log(`API Request to ${fullUrl}`, { method: requestOptions.method, body: requestOptions.body });
    const response = await fetch(fullUrl, requestOptions);
    console.log(`API Response from ${fullUrl}`, { status: response.status, statusText: response.statusText });
    return await handleApiResponse(response, responseOptions);
  } catch (error) {
    
    // This will catch network errors, not HTTP errors (which are handled by handleApiResponse)
    if (!(error instanceof Error) || !('status' in error)) {
      errorStore.addError(
        `${responseOptions.errorPrefix || 'Network Error'}: Could not connect to server`, 
        'error', 
        'Please check your internet connection and try again'
      );
    }
    throw error;
  }
}
