/**
 * API configuration for the application
 * This centralizes all API endpoints and configuration to make deployment easier
 */

// Base API URL - change this when deploying to production
// For production, this would be configured to use relative paths
// For now, we'll use the development URL pointing to the PHP server
export const API_BASE_URL = 'http://localhost:8000/api'; // Development: PHP server on port 8000

// For production, you would change this to:
// export const API_BASE_URL = '/api';

// Auth endpoints
export const AUTH_ENDPOINTS = {
	LOGIN: `${API_BASE_URL}/auth/login`,
	LOGOUT: `${API_BASE_URL}/auth/logout`,
	REGISTER: `${API_BASE_URL}/auth/register`,
	ME: `${API_BASE_URL}/auth/me`,
	VERIFY: `${API_BASE_URL}/auth/verify`
};

// Admin endpoints
export const ADMIN_ENDPOINTS = {
	USERS: {
		LIST: `${API_BASE_URL}/admin/users`,
		CREATE: `${API_BASE_URL}/admin/users`,
		UPDATE: (id: number) => `${API_BASE_URL}/admin/users/${id}`,
		DELETE: (id: number) => `${API_BASE_URL}/admin/users/${id}`,
		VERIFY: (id: number) => `${API_BASE_URL}/admin/users/${id}/verify`,
		RESET_VERIFICATION: (id: number) => `${API_BASE_URL}/admin/users/${id}/reset-verification`,
		RESET_PASSWORD: (id: number) => `${API_BASE_URL}/admin/users/${id}/reset-password`
	},
	POSTS: {
		LIST: `${API_BASE_URL}/admin/posts`,
		CREATE: `${API_BASE_URL}/admin/posts`,
		UPDATE: (id: number) => `${API_BASE_URL}/admin/posts/${id}`,
		DELETE: (id: number) => `${API_BASE_URL}/admin/posts/${id}`
	},
	COMMENTS: {
		LIST: `${API_BASE_URL}/admin/comments`,
		CREATE: `${API_BASE_URL}/admin/comments`,
		UPDATE: (id: number) => `${API_BASE_URL}/admin/comments/${id}`,
		DELETE: (id: number) => `${API_BASE_URL}/admin/comments/${id}`
	}
};

// Post endpoints
export const POST_ENDPOINTS = {
	LIST: `${API_BASE_URL}/posts`,
	GET: (id: number) => `${API_BASE_URL}/posts/${id}`,
	CREATE: `${API_BASE_URL}/posts`,
	UPDATE: (id: number) => `${API_BASE_URL}/posts/${id}`,
	DELETE: (id: number) => `${API_BASE_URL}/posts/${id}`
};

// Comment endpoints
export const COMMENT_ENDPOINTS = {
	LIST: `${API_BASE_URL}/comments`,
	GET: (id: number) => `${API_BASE_URL}/comments/${id}`,
	CREATE: `${API_BASE_URL}/comments/create`,
	UPDATE: (id: number) => `${API_BASE_URL}/comments/${id}`,
	DELETE: (id: number) => `${API_BASE_URL}/comments/${id}`
};

// Like endpoints
export const LIKE_ENDPOINTS = {
	LIST: `${API_BASE_URL}/likes`,
	CREATE: `${API_BASE_URL}/likes`,
	DELETE: (id: number) => `${API_BASE_URL}/likes/${id}`
};
