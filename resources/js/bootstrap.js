import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set up CSRF token for axios
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Add response interceptor to handle CSRF token refresh
window.axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 419) {
            // CSRF token mismatch - try to refresh token first
            console.warn('CSRF token mismatch detected. Attempting to refresh token...');
            
            // Import refreshCsrfToken function
            const { refreshCsrfToken } = await import('./Utils/csrf');
            
            try {
                const newToken = await refreshCsrfToken();
                if (newToken) {
                    console.log('CSRF token refreshed successfully. Retrying request...');
                    
                    // Retry the original request with new token
                    const originalRequest = error.config;
                    originalRequest.headers['X-CSRF-TOKEN'] = newToken;
                    
                    return window.axios(originalRequest);
                }
            } catch (refreshError) {
                console.error('Failed to refresh CSRF token:', refreshError);
            }
            
            // If refresh failed, show message and reload
            console.warn('CSRF token refresh failed. Refreshing page...');
            
            // Show user-friendly message before refresh
            if (typeof window.showToast === 'function') {
                window.showToast('Session expired. Refreshing page...', 'warning');
            }
            
            // Refresh after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        return Promise.reject(error);
    }
);

// Add request interceptor to ensure CSRF token is always included
window.axios.interceptors.request.use(
    config => {
        // Ensure CSRF token is included in all requests
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token && !config.headers['X-CSRF-TOKEN']) {
            config.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Import Bootstrap JavaScript
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
