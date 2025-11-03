import { useForm } from '@inertiajs/react';
import { getCsrfToken, validateCsrfToken, refreshCsrfToken } from '../Utils/csrf';
import { useEffect } from 'react';

/**
 * Custom hook that extends Inertia's useForm with automatic CSRF token handling
 * This ensures CSRF tokens are always properly included in form submissions
 */
export const useFormWithCsrf = (initialData = {}, options = {}) => {
    const form = useForm(initialData, options);
    
    // Ensure CSRF token is available before any submission
    useEffect(() => {
        if (!validateCsrfToken()) {
            console.warn('CSRF token validation failed. Page may need to be refreshed.');
        }
    }, []);
    
    // Override the transform method to ensure CSRF token is included
    const originalTransform = form.transform;
    form.transform = (data) => {
        // Apply original transform if it exists
        const transformedData = originalTransform ? originalTransform(data) : data;
        
        // Ensure CSRF token is included
        const csrfToken = getCsrfToken();
        if (csrfToken && !transformedData._token) {
            transformedData._token = csrfToken;
        }
        
        return transformedData;
    };
    
    // Override submit methods to ensure CSRF token is included
    const originalPost = form.post;
    const originalPut = form.put;
    const originalPatch = form.patch;
    const originalDelete = form.delete;
    
    form.post = (url, options = {}) => {
        // Ensure CSRF token is available
        if (!validateCsrfToken()) {
            console.error('CSRF token not available for POST request');
            return Promise.reject(new Error('CSRF token not available'));
        }
        
        // Add CSRF token to data if not present
        if (form.data && !form.data._token) {
            const csrfToken = getCsrfToken();
            if (csrfToken) {
                form.setData('_token', csrfToken);
            }
        }
        
        return originalPost(url, options);
    };
    
    form.put = (url, options = {}) => {
        if (!validateCsrfToken()) {
            console.error('CSRF token not available for PUT request');
            return Promise.reject(new Error('CSRF token not available'));
        }
        
        if (form.data && !form.data._token) {
            const csrfToken = getCsrfToken();
            if (csrfToken) {
                form.setData('_token', csrfToken);
            }
        }
        
        return originalPut(url, options);
    };
    
    form.patch = (url, options = {}) => {
        if (!validateCsrfToken()) {
            console.error('CSRF token not available for PATCH request');
            return Promise.reject(new Error('CSRF token not available'));
        }
        
        if (form.data && !form.data._token) {
            const csrfToken = getCsrfToken();
            if (csrfToken) {
                form.setData('_token', csrfToken);
            }
        }
        
        return originalPatch(url, options);
    };
    
    form.delete = (url, options = {}) => {
        if (!validateCsrfToken()) {
            console.error('CSRF token not available for DELETE request');
            return Promise.reject(new Error('CSRF token not available'));
        }
        
        if (form.data && !form.data._token) {
            const csrfToken = getCsrfToken();
            if (csrfToken) {
                form.setData('_token', csrfToken);
            }
        }
        
        return originalDelete(url, options);
    };
    
    return form;
};