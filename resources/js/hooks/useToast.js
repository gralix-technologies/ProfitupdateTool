import { useState, useCallback } from 'react';

export const useToast = () => {
    const [toasts, setToasts] = useState([]);

    const addToast = useCallback((toast) => {
        const id = Date.now() + Math.random();
        const newToast = {
            id,
            type: 'success',
            duration: 5000,
            ...toast
        };
        
        setToasts(prev => [...prev, newToast]);
        return id;
    }, []);

    const removeToast = useCallback((id) => {
        setToasts(prev => prev.filter(toast => toast.id !== id));
    }, []);

    const showSuccess = useCallback((message, title = 'Success', options = {}) => {
        return addToast({
            type: 'success',
            title,
            message,
            ...options
        });
    }, [addToast]);

    const showError = useCallback((message, title = 'Error', options = {}) => {
        return addToast({
            type: 'error',
            title,
            message,
            duration: 7000, // Longer duration for errors
            ...options
        });
    }, [addToast]);

    const showWarning = useCallback((message, title = 'Warning', options = {}) => {
        return addToast({
            type: 'warning',
            title,
            message,
            duration: 6000,
            ...options
        });
    }, [addToast]);

    const showInfo = useCallback((message, title = 'Info', options = {}) => {
        return addToast({
            type: 'info',
            title,
            message,
            ...options
        });
    }, [addToast]);

    const clearAll = useCallback(() => {
        setToasts([]);
    }, []);

    return {
        toasts,
        addToast,
        removeToast,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        clearAll
    };
};
