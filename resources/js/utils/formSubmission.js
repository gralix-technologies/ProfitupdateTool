/**
 * Form Submission Utilities
 * Provides consistent form submission handling with CSRF token management
 */

import { getCsrfToken, validateCsrfToken } from './csrf';
import { router } from '@inertiajs/react';

/**
 * Submit form data with automatic CSRF token handling
 */
export const submitFormWithCsrf = (url, data, options = {}) => {
    // Validate CSRF token before submission
    if (!validateCsrfToken()) {
        console.error('CSRF token not available for form submission');
        if (options.onError) {
            options.onError('CSRF token not available. Please refresh the page.');
        }
        return Promise.reject(new Error('CSRF token not available'));
    }

    // Add CSRF token to data
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        data._token = csrfToken;
    }

    // Add enhanced error handling
    const enhancedOptions = {
        ...options,
        onError: (errors) => {
            console.error('Form submission error:', errors);
            
            // Handle 419 CSRF token mismatch specifically
            if (typeof errors === 'object' && errors.message && 
                (errors.message.includes('419') || errors.message.includes('CSRF'))) {
                console.warn('CSRF token mismatch detected. Attempting to refresh...');
                
                // Refresh the page to get a new CSRF token
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                
                if (options.onError) {
                    options.onError('Session expired. Refreshing page...');
                }
                return;
            }
            
            // Call original error handler
            if (options.onError) {
                options.onError(errors);
            }
        }
    };

    return router.post(url, data, enhancedOptions);
};

/**
 * Submit form data with PUT method and CSRF token
 */
export const putFormWithCsrf = (url, data, options = {}) => {
    if (!validateCsrfToken()) {
        console.error('CSRF token not available for PUT submission');
        if (options.onError) {
            options.onError('CSRF token not available. Please refresh the page.');
        }
        return Promise.reject(new Error('CSRF token not available'));
    }

    const csrfToken = getCsrfToken();
    if (csrfToken) {
        data._token = csrfToken;
    }

    const enhancedOptions = {
        ...options,
        onError: (errors) => {
            console.error('PUT submission error:', errors);
            
            if (typeof errors === 'object' && errors.message && 
                (errors.message.includes('419') || errors.message.includes('CSRF'))) {
                console.warn('CSRF token mismatch detected for PUT. Refreshing...');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                
                if (options.onError) {
                    options.onError('Session expired. Refreshing page...');
                }
                return;
            }
            
            if (options.onError) {
                options.onError(errors);
            }
        }
    };

    return router.put(url, data, enhancedOptions);
};

/**
 * Submit form data with PATCH method and CSRF token
 */
export const patchFormWithCsrf = (url, data, options = {}) => {
    if (!validateCsrfToken()) {
        console.error('CSRF token not available for PATCH submission');
        if (options.onError) {
            options.onError('CSRF token not available. Please refresh the page.');
        }
        return Promise.reject(new Error('CSRF token not available'));
    }

    const csrfToken = getCsrfToken();
    if (csrfToken) {
        data._token = csrfToken;
    }

    const enhancedOptions = {
        ...options,
        onError: (errors) => {
            console.error('PATCH submission error:', errors);
            
            if (typeof errors === 'object' && errors.message && 
                (errors.message.includes('419') || errors.message.includes('CSRF'))) {
                console.warn('CSRF token mismatch detected for PATCH. Refreshing...');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                
                if (options.onError) {
                    options.onError('Session expired. Refreshing page...');
                }
                return;
            }
            
            if (options.onError) {
                options.onError(errors);
            }
        }
    };

    return router.patch(url, data, enhancedOptions);
};

/**
 * Delete with CSRF token
 */
export const deleteFormWithCsrf = (url, options = {}) => {
    if (!validateCsrfToken()) {
        console.error('CSRF token not available for DELETE submission');
        if (options.onError) {
            options.onError('CSRF token not available. Please refresh the page.');
        }
        return Promise.reject(new Error('CSRF token not available'));
    }

    const enhancedOptions = {
        ...options,
        onError: (errors) => {
            console.error('DELETE submission error:', errors);
            
            if (typeof errors === 'object' && errors.message && 
                (errors.message.includes('419') || errors.message.includes('CSRF'))) {
                console.warn('CSRF token mismatch detected for DELETE. Refreshing...');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                
                if (options.onError) {
                    options.onError('Session expired. Refreshing page...');
                }
                return;
            }
            
            if (options.onError) {
                options.onError(errors);
            }
        }
    };

    return router.delete(url, enhancedOptions);
};

/**
 * Create FormData with CSRF token for file uploads
 */
export const createFormDataWithCsrf = (data = {}) => {
    const formData = new FormData();
    
    // Add CSRF token first
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
