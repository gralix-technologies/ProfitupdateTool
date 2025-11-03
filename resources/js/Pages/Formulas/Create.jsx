import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FormulaForm from '@/Components/Formulas/FormulaForm';
import { useApiErrorHandler } from '@/Hooks/useApiErrorHandler';
import { IconArrowLeft } from '@tabler/icons-react';
import { getCsrfToken, validateCsrfToken } from '@/Utils/csrf';

export default function Create({ products, returnTypes, supportedOperations, fieldSuggestions, functionDocumentation }) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { handleSuccess, handleError } = useApiErrorHandler();

    const handleSubmit = async (data) => {
        setIsSubmitting(true);
        
        console.log('Submitting formula data:', data);
        
        // Ensure CSRF token is available
        if (!validateCsrfToken()) {
            console.error('CSRF token not available for formula submission');
            handleError('CSRF token not available. Please refresh the page.');
            setIsSubmitting(false);
            return;
        }
        
        // Add CSRF token to data
        const csrfToken = getCsrfToken();
        if (csrfToken) {
            data._token = csrfToken;
        }
        
        try {
            await router.post('/formulas', data, {
                onStart: () => {
                    console.log('Formula form submission started...');
                },
                onProgress: () => {
                    console.log('Formula form submission in progress...');
                },
                onSuccess: (page) => {
                    console.log('Formula form submission successful:', page);
                    // Let Inertia handle the redirect, success message comes from backend flash data
                },
                onError: (errors) => {
                    console.error('Formula form submission error:', errors);
                    handleError(errors, {
                        fallbackMessage: 'Failed to create formula. Please check your inputs and try again.'
                    });
                },
                onFinish: () => {
                    console.log('Formula form submission finished');
                    setIsSubmitting(false);
                }
            });
        } catch (error) {
            console.error('Formula submit error:', error);
            setIsSubmitting(false);
        }
    };

    const handleCancel = () => {
        router.get('/formulas');
    };

    return (
        <AppLayout title="Create Formula">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/formulas">Formulas</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">Create</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Analytics
                            </div>
                            <h2 className="page-title">
                                Create Formula
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/formulas" className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Formulas
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-fluid px-3">
                    <div className="row g-3">
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">Formula Details</h3>
                                </div>
                                <div className="card-body">
                                    <FormulaForm
                                        onSubmit={handleSubmit}
                                        onCancel={handleCancel}
                                        products={products}
                                        returnTypes={returnTypes}
                                        supportedOperations={supportedOperations}
                                        fieldSuggestions={fieldSuggestions}
                                        functionDocumentation={functionDocumentation}
                                        isSubmitting={isSubmitting}
                                        submitLabel="Create Formula"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}