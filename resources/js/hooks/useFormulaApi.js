import { useState, useCallback } from 'react';
import apiClient from '../utils/apiClient';
import { useApiErrorHandler } from './useApiErrorHandler';

/**
 * Custom hook for formula-related API operations
 */
export const useFormulaApi = () => {
    const [loading, setLoading] = useState(false);
    const [validationResult, setValidationResult] = useState(null);
    const [testResult, setTestResult] = useState(null);
    const [productFields, setProductFields] = useState({});
    
    const { handleError, handleSuccess, handleAsync } = useApiErrorHandler();

    /**
     * Validate a formula expression
     */
    const validateFormula = useCallback(async (expression, productId = null, sampleData = null) => {
        setLoading(true);
        
        return handleAsync(async () => {
            const response = await apiClient.post('/formulas/test', {
                expression,
                product_id: productId,
                sample_data: sampleData || [],
                use_real_data: false
            });
            
            // Ensure we have the expected response format
            const result = response.data;
            
            setValidationResult(result);
            
            // Show success message if validation passes
            if (result.valid) {
                handleSuccess(result.message || 'Formula is valid', 'Validation Success');
            }
            
            return result;
        }, {
            showToast: false // Don't show toast for validation errors
        }).finally(() => {
            setLoading(false);
        });
    }, [handleAsync, handleSuccess]);

    /**
     * Test a formula with real data
     */
    const testFormula = useCallback(async (expression, productId, sampleData = null) => {
        setLoading(true);
        
        return handleAsync(async () => {
            const response = await apiClient.post('/formulas/test', {
                expression,
                product_id: productId,
                sample_data: sampleData,
                use_real_data: true
            });
            
            setTestResult(response.data);
            return response.data;
        }, {
            showToast: false // Don't show toast for test errors
        }).finally(() => {
            setLoading(false);
        });
    }, [handleAsync]);

    /**
     * Get field suggestions for a product
     */
    const getFieldSuggestions = useCallback(async (productId = null) => {
        return handleAsync(async () => {
            const params = productId ? `?product_id=${productId}` : '';
            const response = await apiClient.get(`/formulas/field-suggestions${params}`);
            return response.data.fields || response.data;
        });
    }, [handleAsync]);

    /**
     * Get product-specific fields
     */
    const getProductFields = useCallback(async (productId) => {
        if (!productId) {
            setProductFields({});
            return {};
        }

        return handleAsync(async () => {
            const response = await apiClient.get(`/formulas/products/${productId}/fields`);
            const fields = response.data.fields || response.data;
            setProductFields(fields);
            return fields;
        }, {
            showToast: false // Don't show toast for field fetching errors
        });
    }, [handleAsync]);

    /**
     * Get formula templates
     */
    const getFormulaTemplates = useCallback(async () => {
        return handleAsync(async () => {
            const response = await apiClient.get('/formulas/templates/list');
            return response.data;
        });
    }, [handleAsync]);

    /**
     * Create a new formula
     */
    const createFormula = useCallback(async (formulaData) => {
        setLoading(true);
        
        return handleAsync(async () => {
            const response = await apiClient.post('/formulas', formulaData);
            handleSuccess('Formula created successfully!');
            return response.data;
        }).finally(() => {
            setLoading(false);
        });
    }, [handleAsync, handleSuccess]);

    /**
     * Update an existing formula
     */
    const updateFormula = useCallback(async (formulaId, formulaData) => {
        setLoading(true);
        
        return handleAsync(async () => {
            const response = await apiClient.put(`/formulas/${formulaId}`, formulaData);
            handleSuccess('Formula updated successfully!');
            return response.data;
        }).finally(() => {
            setLoading(false);
        });
    }, [handleAsync, handleSuccess]);

    /**
     * Delete a formula
     */
    const deleteFormula = useCallback(async (formulaId) => {
        setLoading(true);
        
        return handleAsync(async () => {
            const response = await apiClient.delete(`/formulas/${formulaId}`);
            handleSuccess('Formula deleted successfully!');
            return response.data;
        }).finally(() => {
            setLoading(false);
        });
    }, [handleAsync, handleSuccess]);

    /**
     * Clear validation and test results
     */
    const clearResults = useCallback(() => {
        setValidationResult(null);
        setTestResult(null);
    }, []);

    /**
     * Generate sample data for testing
     */
    const generateSampleData = useCallback((productFields, count = 5) => {
        const sampleData = [];
        const fieldNames = Object.keys(productFields);
        
        for (let i = 0; i < count; i++) {
            const record = {};
            fieldNames.forEach(fieldName => {
                if (fieldName.includes('amount') || fieldName.includes('balance')) {
                    record[fieldName] = Math.random() * 10000;
                } else if (fieldName.includes('rate') || fieldName.includes('percentage')) {
                    record[fieldName] = Math.random() * 100;
                } else if (fieldName.includes('date')) {
                    record[fieldName] = new Date(Date.now() - Math.random() * 365 * 24 * 60 * 60 * 1000);
                } else if (fieldName.includes('status')) {
                    record[fieldName] = ['active', 'inactive', 'pending'][Math.floor(Math.random() * 3)];
                } else {
                    record[fieldName] = `Value_${i + 1}`;
                }
            });
            sampleData.push(record);
        }
        
        return sampleData;
    }, []);

    return {
        // State
        loading,
        validationResult,
        testResult,
        productFields,
        
        // Actions
        validateFormula,
        testFormula,
        getFieldSuggestions,
        getProductFields,
        getFormulaTemplates,
        createFormula,
        updateFormula,
        deleteFormula,
        clearResults,
        generateSampleData,
        
        // Setters for external control
        setValidationResult,
        setTestResult,
        setProductFields
    };
};
