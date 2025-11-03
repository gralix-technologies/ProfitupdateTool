import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconArrowLeft, IconPlus } from '@tabler/icons-react';
import ProductForm from '@/Components/Products/ProductForm';
import { useToast } from '@/Hooks/useToast';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Create({ categories, field_types }) {
    const { data, setData, post, processing, errors } = useFormWithCsrf({
        name: '',
        category: '',
        description: '',
        is_active: true,
        field_definitions: [],
        _token: null // Will be auto-populated by useFormWithCsrf
    });

    const { showSuccess, showError } = useToast();

    const handleSubmit = () => {
        console.log('Submitting product data:', data);
        
        // Simple validation for field definitions
        const fieldErrors = [];
        if (data.field_definitions && Array.isArray(data.field_definitions)) {
            data.field_definitions.forEach((field, index) => {
                if (field.type === 'Lookup' && (!field.options || field.options.length === 0 || field.options.every(opt => !opt.trim()))) {
                    fieldErrors.push(`Field ${index + 1} (${field.name || 'Unnamed'}): Lookup fields must have at least one option`);
                }
            });
        }
        
        if (fieldErrors.length > 0) {
            showError(fieldErrors.join('\n'), 'Validation Error');
            return;
        }
        
        post('/products', {
            onStart: () => {
                console.log('Form submission started...');
            },
            onProgress: () => {
                console.log('Form submission in progress...');
            },
            onSuccess: (page) => {
                console.log('Form submission successful:', page);
                showSuccess('Product created successfully!');
            },
            onError: (errors) => {
                console.error('Form submission error:', errors);
                console.error('Error type:', typeof errors);
                console.error('Error details:', JSON.stringify(errors, null, 2));
                
                if (typeof errors === 'object' && errors !== null) {
                    if (Array.isArray(errors)) {
                        // Handle array of error messages
                        showError(errors.join(', '), 'Validation Error');
                    } else {
                        // Handle validation errors object
                        const errorMessages = Object.values(errors).flat();
                        showError(errorMessages.join(', '), 'Validation Error');
                    }
                } else if (typeof errors === 'string') {
                    showError(errors);
                } else {
                    showError('Failed to create product. Please try again.');
                }
            },
            onFinish: () => {
                console.log('Form submission finished');
            }
        });
    };

    return (
        <AppLayout title="Create Product">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/products">Products</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">Create</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Financial Products
                            </div>
                            <h2 className="page-title">
                                Create New Product
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/products" className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Products
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
                            <ProductForm
                                data={data}
                                setData={setData}
                                errors={errors}
                                categories={categories}
                                field_types={field_types}
                                processing={processing}
                                submitText="Create Product"
                                cancelUrl="/products"
                                onSubmit={handleSubmit}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}