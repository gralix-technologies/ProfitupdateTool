/**
 * CSRF Token Utilities
 * Provides consistent CSRF token handling across the application
 */

// Get CSRF token from meta tag or Laravel global
export const getCsrfToken = () => {
    // Try meta tag first
    const metaToken = document.head.querySelector('meta[name="csrf-token"]');
    if (metaToken && metaToken.getAttribute('content')) {
        return metaToken.getAttribute('content');
    }
    
    // Try Laravel global
    if (window.Laravel && window.Laravel.csrfToken) {
        return window.Laravel.csrfToken;
    }
    
    // Try Inertia props
    if (window.$page && window.$page.props && window.$page.props.csrf_token) {
        return window.$page.props.csrf_token;
    }
    
    console.warn('CSRF token not found in any location');
    return null;
};

// Refresh CSRF token from server
export const refreshCsrfToken = async () => {
    try {
        const response = await fetch('/test-csrf', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.csrf_token) {
                // Update meta tag
                const metaToken = document.head.querySelector('meta[name="csrf-token"]');
                if (metaToken) {
                    metaToken.setAttribute('content', data.csrf_token);
                }
                
                // Update Laravel global
                if (window.Laravel) {
                    window.Laravel.csrfToken = data.csrf_token;
                }
                
                // Update axios headers
                if (window.axios) {
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.csrf_token;
                }
                
                console.log('CSRF token refreshed successfully');
                return data.csrf_token;
            }
        }
    } catch (error) {
        console.error('Failed to refresh CSRF token:', error);
    }
    
    return null;
};

// Get CSRF token from Inertia props
export const getCsrfTokenFromProps = (page) => {
    return page?.props?.csrf_token || getCsrfToken();
};

// Create form data with CSRF token
export const createFormDataWithCsrf = (data = {}) => {
    const formData = new FormData();
    
    // Add CSRF token
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    
    // Add other data
    Object.keys(data).forEach(key => {
        if (data[key] !== null && data[key] !== undefined) {
            if (data[key] instanceof File) {
                formData.append(key, data[key]);
            } else if (Array.isArray(data[key])) {
                data[key].forEach((item, index) => {
                    formData.append(`${key}[${index}]`, item);
                });
            } else if (typeof data[key] === 'object') {
                formData.append(key, JSON.stringify(data[key]));
            } else {
                formData.append(key, data[key]);
            }
        }
    });
    
    return formData;
};

// Create JSON data with CSRF token for axios
export const createJsonDataWithCsrf = (data = {}) => {
    const csrfToken = getCsrfToken();
    return {
        _token: csrfToken,
        ...data
    };
};

// Validate CSRF token exists
export const validateCsrfToken = () => {
    const token = getCsrfToken();
    if (!token) {
        console.error('CSRF token not found. Please refresh the page.');
        return false;
    }
    return true;
};

// Update axios headers with current CSRF token
export const updateAxiosHeaders = () => {
    const token = getCsrfToken();
    if (token && window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
};

// CSRF token refresh handler
export const handleCsrfTokenRefresh = () => {
    // Update axios headers
    updateAxiosHeaders();
    
    // Dispatch custom event for other components to listen to
    window.dispatchEvent(new CustomEvent('csrf-token-refreshed', {
        detail: { token: getCsrfToken() }
    }));
};

// Initialize CSRF handling
export const initializeCsrf = () => {
    // Update axios headers on page load
    updateAxiosHeaders();
    
    // Listen for token refresh events
    window.addEventListener('csrf-token-refreshed', updateAxiosHeaders);
    
    // Update token periodically (every 5 minutes)
    setInterval(updateAxiosHeaders, 5 * 60 * 1000);
    
    // Ensure CSRF token is available globally for Inertia
    window.csrfToken = getCsrfToken();
    
    // Update global token when it changes
    const observer = new MutationObserver(() => {
        const newToken = getCsrfToken();
        if (newToken && newToken !== window.csrfToken) {
            window.csrfToken = newToken;
            updateAxiosHeaders();
        }
    });
    
    // Observe changes to the head element
    observer.observe(document.head, { childList: true, subtree: true });
};
