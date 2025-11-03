import React, { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { IconArrowLeft, IconDeviceFloppy } from '@tabler/icons-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DashboardBuilder from '@/Components/Dashboards/DashboardBuilder';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Edit() {
    const { dashboard, formulas = [], products = [], customers = [] } = usePage().props;
    
    const { data, setData, put, processing, errors } = useFormWithCsrf({
        name: dashboard.name,
        layout: dashboard.layout || [],
        filters: dashboard.filters || {},
        widgets: dashboard.widgets || [],
        product_id: dashboard.product_id || null,
        description: dashboard.description || '',
        is_public: dashboard.is_public || false,
        is_active: dashboard.is_active !== false
    });

    const [widgets, setWidgets] = useState(dashboard.widgets || []);

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Prepare the data with current widget state
        const formData = {
            name: data.name,
            description: data.description,
            product_id: data.product_id,
            is_public: data.is_public,
            is_active: data.is_active,
            layout: widgets,
            widgets: widgets,
            filters: data.filters
        };
        
        console.log('Submitting dashboard data:', formData);
        console.log('Widgets array:', widgets);
        console.log('Widgets count:', widgets.length);
        
        // Validate that we have widgets to save
        if (widgets.length === 0) {
            alert('Please add at least one widget before saving the dashboard.');
            return;
        }
        
        put(`/dashboards/${dashboard.id}`, {
            data: formData,
            onSuccess: (page) => {
                console.log('Dashboard updated successfully:', page);
                // Show success message
                alert('Dashboard updated successfully!');
                // Redirect to dashboard show page
                window.location.href = `/dashboards/${dashboard.id}`;
            },
            onError: (errors) => {
                console.error('Dashboard update errors:', errors);
                // Show error message
                const errorMessage = Object.values(errors).flat().join(', ');
                alert('Error updating dashboard: ' + errorMessage);
            },
            onFinish: () => {
                console.log('Request finished');
            }
        });
    };

    const handleWidgetsChange = (newWidgets) => {
        console.log('Widgets changed:', newWidgets);
        console.log('New widgets count:', newWidgets.length);
        setWidgets(newWidgets);
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit Dashboard - ${dashboard.name}`} />
            
            <div className="page-wrapper">
                <div className="page-header d-print-none">
                    <div className="container-xl">
                        <div className="row g-2 align-items-center">
                            <div className="col">
                                <div className="page-pretitle">
                                    <a href={`/dashboards/${dashboard.id}`} className="text-muted">
                                        <IconArrowLeft size={16} className="me-1" />
                                        Back to Dashboard
                                    </a>
                                </div>
                                <h2 className="page-title">Edit Dashboard</h2>
                            </div>
                            <div className="col-auto ms-auto d-print-none">
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    disabled={processing}
                                    onClick={handleSubmit}
                                >
                                    <IconDeviceFloppy size={16} className="me-1" />
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="page-body">
                    <div className="container-xl">
                        <div className="row">
                            <div className="col-12">
                                <div className="card mb-4">
                                    <div className="card-header">
                                        <h3 className="card-title">Dashboard Settings</h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <label className="form-label required">
                                                        Dashboard Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                                        value={data.name}
                                                        onChange={(e) => setData('name', e.target.value)}
                                                        placeholder="Enter dashboard name"
                                                        required
                                                    />
                                                    {errors.name && (
                                                        <div className="invalid-feedback">
                                                            {errors.name}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <label className="form-label">
                                                        Product
                                                    </label>
                                                    <select
                                                        className={`form-select ${errors.product_id ? 'is-invalid' : ''}`}
                                                        value={data.product_id || ''}
                                                        onChange={(e) => setData('product_id', e.target.value || null)}
                                                    >
                                                        <option value="">Select a Product (Optional)</option>
                                                        {products.map((product) => (
                                                            <option key={product.id} value={product.id}>
                                                                {product.name} ({product.category})
                                                            </option>
                                                        ))}
                                                    </select>
                                                    {errors.product_id && (
                                                        <div className="invalid-feedback">
                                                            {errors.product_id}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            
                                            <div className="col-12">
                                                <div className="mb-3">
                                                    <label className="form-label">
                                                        Description
                                                    </label>
                                                    <textarea
                                                        className={`form-control ${errors.description ? 'is-invalid' : ''}`}
                                                        value={data.description}
                                                        onChange={(e) => setData('description', e.target.value)}
                                                        placeholder="Enter dashboard description"
                                                        rows="3"
                                                    />
                                                    {errors.description && (
                                                        <div className="invalid-feedback">
                                                            {errors.description}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <div className="form-check form-switch">
                                                        <input
                                                            className="form-check-input"
                                                            type="checkbox"
                                                            id="is_public"
                                                            checked={data.is_public}
                                                            onChange={(e) => setData('is_public', e.target.checked)}
                                                        />
                                                        <label className="form-check-label" htmlFor="is_public">
                                                            Public Dashboard
                                                        </label>
                                                    </div>
                                                    <div className="form-text text-muted">
                                                        Allow other users to view this dashboard
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div className="col-md-6">
                                                <div className="mb-3">
                                                    <div className="form-check form-switch">
                                                        <input
                                                            className="form-check-input"
                                                            type="checkbox"
                                                            id="is_active"
                                                            checked={data.is_active}
                                                            onChange={(e) => setData('is_active', e.target.checked)}
                                                        />
                                                        <label className="form-check-label" htmlFor="is_active">
                                                            Active Dashboard
                                                        </label>
                                                    </div>
                                                    <div className="form-text text-muted">
                                                        Enable this dashboard for use
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-header">
                                <h3 className="card-title">Dashboard Layout</h3>
                                <div className="card-actions">
                                    <div className="text-muted small">
                                        Drag and drop widgets to customize your dashboard
                                    </div>
                                </div>
                            </div>
                            <div className="card-body">
                                <DashboardBuilder
                                    widgets={widgets}
                                    onWidgetsChange={handleWidgetsChange}
                                    isEditing={true}
                                    dashboardId={dashboard.id}
                                    formulas={formulas}
                                    products={products}
                                    customers={customers}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}