import { useCallback } from 'react';
import { useToast } from './useToast';
import { ApiError } from '../utils/apiClient';

/**
 * Custom hook for standardized API error handling
 */
export const useApiErrorHandler = () => {
    const { showSuccess, showError, showWarning, showInfo } = useToast();

    const handleError = useCallback((error, options = {}) => {
        const {
            showToast = true,
            fallbackMessage = 'An unexpected error occurred',
            logError = true,
            customHandler = null
        } = options;

        // Log error for debugging
        if (logError) {
            console.error('API Error:', error);
            if (error instanceof ApiError) {
                console.error('Error Details:', error.getDebugInfo());
            }
        }

        // Use custom handler if provided
        if (customHandler) {
            customHandler(error);
            return;
        }

        // Don't show toast if disabled
        if (!showToast) {
            return;
        }

        let message = fallbackMessage;
        let title = 'Error';

        if (error instanceof ApiError) {
            message = error.getUserMessage();
            
            // Customize title based on error type
            if (error.isNetworkError) {
                title = 'Connection Error';
            } else if (error.isValidationError) {
                title = 'Validation Error';
            } else if (error.isServerError) {
                title = 'Server Error';
            } else if (error.isClientError) {
                title = 'Request Error';
            }
        } else if (error instanceof Error) {
            message = error.message;
        } else if (typeof error === 'string') {
            message = error;
        }

        // Show appropriate toast
        if (error instanceof ApiError && error.isValidationError) {
            showWarning(message, title);
        } else {
            showError(message, title);
        }
    }, [showError, showWarning]);

    const handleSuccess = useCallback((message, title = 'Success') => {
        showSuccess(message, title);
    }, [showSuccess]);

    const handleAsync = useCallback(async (asyncFunction, options = {}) => {
        try {
            return await asyncFunction();
        } catch (error) {
            handleError(error, options);
            throw error; // Re-throw for further handling if needed
        }
    }, [handleError]);

    return {
        handleError,
        handleSuccess,
        handleAsync,
        showSuccess,
        showError,
        showWarning,
        showInfo
    };
};
