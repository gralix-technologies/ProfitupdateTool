import React from 'react';
import { router } from '@inertiajs/react';
import { getCsrfToken } from '../Utils/csrf';

const LogoutForm = ({ onSuccess, onError, className = '', children }) => {
    const handleLogout = (e) => {
        e.preventDefault();
        
        // Get CSRF token
        const csrfToken = getCsrfToken();
        
        if (!csrfToken) {
            console.error('CSRF token not found');
            if (onError) onError('CSRF token not found');
            return;
        }
        
        // Create form data with CSRF token
        const formData = new FormData();
        formData.append('_token', csrfToken);
        
        // Submit logout request
        router.post('/logout', formData, {
            onSuccess: () => {
                if (onSuccess) onSuccess();
            },
            onError: (errors) => {
                console.error('Logout error:', errors);
                if (onError) onError(errors);
            },
            onFinish: () => {
                // Always close dropdown or reset UI state
            }
        });
    };
    
    return (
        <form onSubmit={handleLogout} className={className}>
            {children}
        </form>
    );
};

export default LogoutForm;
