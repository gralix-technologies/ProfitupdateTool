/**
 * Standardized API Client for consistent request/response handling
 */

class ApiClient {
    constructor() {
        this.baseURL = '/api';
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        };
    }

    /**
     * Make a standardized API request
     */
    async request(endpoint, options = {}) {
        const {
            method = 'GET',
            body = null,
            headers = {},
            ...fetchOptions
        } = options;

        const url = endpoint.startsWith('/') ? `${this.baseURL}${endpoint}` : `${this.baseURL}/${endpoint}`;
        
        const config = {
            method,
            headers: {
                ...this.defaultHeaders,
                ...headers
            },
            ...fetchOptions
        };

        if (body && method !== 'GET') {
            config.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            // Standardize response format
            const standardizedResponse = {
                success: response.ok,
                status: response.status,
                data: data.data || data,
                message: data.message || (response.ok ? 'Success' : 'Request failed'),
                errors: data.errors || null,
                meta: data.meta || null
            };

            // Handle non-200 status codes
            if (!response.ok) {
                throw new ApiError(
                    standardizedResponse.message,
                    response.status,
                    standardizedResponse.errors,
                    standardizedResponse.data
                );
            }

            return standardizedResponse;
        } catch (error) {
            // Handle network errors or JSON parsing errors
            if (error instanceof ApiError) {
                throw error;
            }

            throw new ApiError(
                'Network error or server unavailable',
                0,
                null,
                null,
                error
            );
        }
    }

    // Convenience methods
    async get(endpoint, options = {}) {
        return this.request(endpoint, { ...options, method: 'GET' });
    }

    async post(endpoint, body, options = {}) {
        return this.request(endpoint, { ...options, method: 'POST', body });
    }

    async put(endpoint, body, options = {}) {
        return this.request(endpoint, { ...options, method: 'PUT', body });
    }

    async patch(endpoint, body, options = {}) {
        return this.request(endpoint, { ...options, method: 'PATCH', body });
    }

    async delete(endpoint, options = {}) {
        return this.request(endpoint, { ...options, method: 'DELETE' });
    }
}

/**
 * Custom API Error class for better error handling
 */
class ApiError extends Error {
    constructor(message, status, errors = null, data = null, originalError = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
        this.data = data;
        this.originalError = originalError;
        this.isNetworkError = status === 0;
        this.isValidationError = status === 422;
        this.isServerError = status >= 500;
        this.isClientError = status >= 400 && status < 500;
    }

    /**
     * Get user-friendly error message
     */
    getUserMessage() {
        if (this.isNetworkError) {
            return 'Unable to connect to server. Please check your internet connection.';
        }
        
        if (this.isValidationError && this.errors) {
            // Handle validation errors
            if (typeof this.errors === 'object') {
                return Object.values(this.errors).flat().join(', ');
            }
            return this.errors;
        }

        if (this.isServerError) {
            return 'Server error occurred. Please try again later.';
        }

        return this.message || 'An unexpected error occurred.';
    }

    /**
     * Get error details for debugging
     */
    getDebugInfo() {
        return {
            message: this.message,
            status: this.status,
            errors: this.errors,
            data: this.data,
            originalError: this.originalError
        };
    }
}

// Create and export a singleton instance
const apiClient = new ApiClient();

export { ApiError };
export default apiClient;
