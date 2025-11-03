import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { IconArrowLeft, IconDeviceFloppy, IconPlus } from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';
import DashboardBuilder from '@/Components/Dashboards/DashboardBuilder';
import { useToast } from '@/Hooks/useToast';
import { getCsrfToken, validateCsrfToken } from '@/Utils/csrf';

export default function Create({ formulas, products, customers, widgetTypes }) {
    const [formData, setFormData] = useState({
        name: '',
        layout: [],
        filters: {}
    });
    const [widgets, setWidgets] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});
    const { showSuccess, showError } = useToast();

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Ensure CSRF token is available
        if (!validateCsrfToken()) {
            console.error('CSRF token not available for dashboard submission');
            showError('CSRF token not available. Please refresh the page.');
            return;
        }
        
        console.log('Submitting dashboard data:', formData);
        console.log('Widgets data:', widgets);
        
        // Prepare the complete data object with widgets
        const completeFormData = {
            ...formData,
            layout: widgets,
            widgets: widgets,
            _token: getCsrfToken()
        };
        
        console.log('Complete form data being sent:', completeFormData);
        
        setProcessing(true);
        setErrors({});
        
        router.post('/dashboards', completeFormData, {
            onStart: () => {
                console.log('Dashboard form submission started...');
            },
            onProgress: () => {
                console.log('Dashboard form submission in progress...');
            },
            onSuccess: (page) => {
                console.log('Dashboard form submission successful:', page);
                showSuccess('Dashboard created successfully!');
                setProcessing(false);
            },
            onError: (errors) => {
                console.error('Dashboard form submission error:', errors);
                console.error('Error type:', typeof errors);
                console.error('Error details:', JSON.stringify(errors, null, 2));
                
                setErrors(errors);
                setProcessing(false);
                
                if (typeof errors === 'object' && errors !== null) {
                    if (Array.isArray(errors)) {
                        showError(errors.join(', '), 'Validation Error');
                    } else {
                        const errorMessages = Object.values(errors).flat();
                        showError(errorMessages.join(', '), 'Validation Error');
                    }
                } else if (typeof errors === 'string') {
                    showError(errors);
                } else {
                    showError('Failed to create dashboard. Please try again.');
                }
            },
            onFinish: () => {
                console.log('Dashboard form submission finished');
                setProcessing(false);
            }
        });
    };

    const handleWidgetsChange = (newWidgets) => {
        setWidgets(newWidgets);
    };

    return (
        <AppLayout title="Create Dashboard">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/dashboards">Dashboards</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">Create</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Analytics
                            </div>
                            <h2 className="page-title">
                                Create Dashboard
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/dashboards" className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Dashboards
                                </Link>
                                <button
                                    type="submit"
                                    form="dashboard-form"
                                    className="btn btn-primary"
                                    disabled={processing}
                                >
                                    <IconDeviceFloppy size={16} className="me-1" />
                                    {processing ? 'Saving...' : 'Save Dashboard'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-fluid px-3">
                    <div className="row g-3">
                        {/* Dashboard Settings - Full Width */}
                        <div className="col-12">
                            <form id="dashboard-form" onSubmit={handleSubmit}>
                                <div className="card mb-4">
                                    <div className="card-header">
                                        <h3 className="card-title">Dashboard Settings</h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="row g-3">
                                            <div className="col-lg-8 col-xl-6">
                                                <div className="mb-0">
                                                    <label className="form-label required">
                                                        Dashboard Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        className={`form-control form-control-lg ${errors.name ? 'is-invalid' : ''}`}
                                                        value={formData.name}
                                                        onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                                                        placeholder="Enter dashboard name"
                                                        required
                                                    />
                                                    {errors.name && (
                                                        <div className="invalid-feedback">
                                                            {errors.name}
                                                        </div>
                                                    )}
                                                    <div className="form-text">
                                                        Choose a descriptive name for your dashboard that reflects its purpose and content.
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-lg-4 col-xl-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Quick Actions</label>
                                                    <div className="d-flex flex-column gap-2">
                                                        <button
                                                            type="button"
                                                            className="btn btn-outline-primary btn-sm"
                                                            onClick={() => setFormData(prev => ({ ...prev, name: 'Analytics Dashboard' }))}
                                                        >
                                                            Use Analytics Template
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="btn btn-outline-secondary btn-sm"
                                                            onClick={() => setFormData(prev => ({ ...prev, name: 'Portfolio Overview' }))}
                                                        >
                                                            Use Portfolio Template
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {/* Dashboard Layout - Full Width */}
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <div className="row align-items-center">
                                        <div className="col">
                                            <h3 className="card-title mb-0">Dashboard Layout</h3>
                                            <div className="text-muted small">
                                                Drag and drop widgets to build your dashboard
                                            </div>
                                        </div>
                                        <div className="col-auto">
                                            <div className="btn-list">
                                                <button type="button" className="btn btn-outline-secondary btn-sm">
                                                    Reset Layout
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="card-body p-0">
                                    <div className="min-vh-50">
                                        <DashboardBuilder
                                            widgets={widgets}
                                            onWidgetsChange={handleWidgetsChange}
                                            isEditing={true}
                                            formulas={formulas}
                                            products={products}
                                            customers={customers}
                                            widgetTypes={widgetTypes}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}