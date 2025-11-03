import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconArrowLeft, IconDeviceFloppy, IconUser, IconUsers, IconUpload, IconDownload } from '@tabler/icons-react';
import { useToast } from '@/Hooks/useToast';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Create({ riskLevels }) {
    const [showBulkUpload, setShowBulkUpload] = useState(false);
    const { showSuccess, showError } = useToast();
    
    const { data, setData, post, processing, errors } = useFormWithCsrf({
        customer_id: '',
        name: '',
        email: '',
        phone: '',
        branch_code: '',
        risk_level: '',
        is_active: true,
        // Financial metrics
        total_loans_outstanding: 0,
        total_deposits: 0,
        npl_exposure: 0,
        profitability: 0,
        // Demographics
        demographics: {
            age: '',
            gender: '',
            occupation: '',
            address: '',
            city: '',
            country: 'Zambia'
        }
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        console.log('Submitting customer data:', data);
        
        post('/customers', {
            onStart: () => {
                console.log('Customer form submission started...');
            },
            onProgress: () => {
                console.log('Customer form submission in progress...');
            },
            onSuccess: (page) => {
                console.log('Customer form submission successful:', page);
                showSuccess('Customer created successfully!');
            },
            onError: (errors) => {
                console.error('Customer form submission error:', errors);
                console.error('Error type:', typeof errors);
                console.error('Error details:', JSON.stringify(errors, null, 2));
                
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
                    showError('Failed to create customer. Please try again.');
                }
            },
            onFinish: () => {
                console.log('Customer form submission finished');
            }
        });
    };

    const handleBulkUpload = (e) => {
        e.preventDefault();
        post('/customers/bulk-upload', {
            onSuccess: () => {
                showSuccess('Customers uploaded successfully!');
            },
            onError: (errors) => {
                if (typeof errors === 'object' && errors !== null) {
                    const errorMessages = Object.values(errors).flat();
                    showError(errorMessages.join(', '), 'Upload Error');
                } else if (typeof errors === 'string') {
                    showError(errors);
                } else {
                    showError('Failed to upload customers. Please try again.');
                }
            }
        });
    };

    const downloadTemplate = () => {
        const link = document.createElement('a');
        link.href = '/customers/download-template';
        link.download = 'customer_bulk_upload_template.csv';
        link.click();
    };

    return (
        <AppLayout title="Create Customer">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/customers">Customers</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">Create</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Customer Management
                            </div>
                            <h2 className="page-title">
                                Create New Customer
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <button 
                                    type="button" 
                                    className="btn btn-outline-primary"
                                    onClick={downloadTemplate}
                                >
                                    <IconDownload size={16} className="me-1" />
                                    Download Template
                                </button>
                                <button 
                                    type="button" 
                                    className={`btn ${showBulkUpload ? 'btn-primary' : 'btn-outline-secondary'}`}
                                    onClick={() => setShowBulkUpload(!showBulkUpload)}
                                >
                                    <IconUpload size={16} className="me-1" />
                                    Bulk Upload
                                </button>
                                <Link href="/customers" className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Customers
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
                            {/* Bulk Upload Section */}
                            {showBulkUpload && (
                                <div className="card mb-4">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <IconUpload size={18} className="me-2" />
                                            Bulk Upload Customers
                                        </h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="row g-3">
                                            <div className="col-lg-8 col-xl-9">
                                                <div className="mb-0">
                                                    <label className="form-label">Upload CSV File</label>
                                                    <input
                                                        type="file"
                                                        className="form-control form-control-lg"
                                                        accept=".csv"
                                                        onChange={(e) => setData('csv_file', e.target.files[0])}
                                                    />
                                                    <div className="form-text">
                                                        Upload a CSV file with customer data. Download the template for the correct format.
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-lg-4 col-xl-3">
                                                <div className="mb-0">
                                                    <label className="form-label">&nbsp;</label>
                                                    <button 
                                                        type="button" 
                                                        className="btn btn-primary btn-lg w-100"
                                                        onClick={handleBulkUpload}
                                                        disabled={processing}
                                                    >
                                                        <IconUpload size={16} className="me-1" />
                                                        Upload Customers
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <form onSubmit={handleSubmit}>
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <IconUser size={18} className="me-2" />
                                            Customer Information
                                        </h3>
                                    </div>
                                    <div className="card-body">
                                        {/* Basic Information - Full Width Layout */}
                                        <div className="row g-3 mb-4">
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label required">Customer ID</label>
                                                    <input
                                                        type="text"
                                                        className={`form-control ${errors.customer_id ? 'is-invalid' : ''}`}
                                                        value={data.customer_id}
                                                        onChange={(e) => setData('customer_id', e.target.value)}
                                                        placeholder="Enter unique customer ID"
                                                    />
                                                    {errors.customer_id && (
                                                        <div className="invalid-feedback">{errors.customer_id}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label required">Full Name</label>
                                                    <input
                                                        type="text"
                                                        className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                                        value={data.name}
                                                        onChange={(e) => setData('name', e.target.value)}
                                                        placeholder="Enter customer full name"
                                                    />
                                                    {errors.name && (
                                                        <div className="invalid-feedback">{errors.name}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Email Address</label>
                                                    <input
                                                        type="email"
                                                        className={`form-control ${errors.email ? 'is-invalid' : ''}`}
                                                        value={data.email}
                                                        onChange={(e) => setData('email', e.target.value)}
                                                        placeholder="Enter email address"
                                                    />
                                                    {errors.email && (
                                                        <div className="invalid-feedback">{errors.email}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Phone Number</label>
                                                    <input
                                                        type="text"
                                                        className={`form-control ${errors.phone ? 'is-invalid' : ''}`}
                                                        value={data.phone}
                                                        onChange={(e) => setData('phone', e.target.value)}
                                                        placeholder="Enter phone number"
                                                    />
                                                    {errors.phone && (
                                                        <div className="invalid-feedback">{errors.phone}</div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Branch & Risk Information - Full Width Layout */}
                                        <div className="row g-3 mb-4">
                                            <div className="col-lg-4 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label required">Branch</label>
                                                    <input
                                                        type="text"
                                                        className={`form-control ${errors.branch_code ? 'is-invalid' : ''}`}
                                                        value={data.branch_code}
                                                        onChange={(e) => setData('branch_code', e.target.value)}
                                                        placeholder="Enter branch code (e.g., LUS001, NDL001)"
                                                    />
                                                    {errors.branch_code && (
                                                        <div className="invalid-feedback">{errors.branch_code}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-4 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label required">Risk Level</label>
                                                    <select
                                                        className={`form-select ${errors.risk_level ? 'is-invalid' : ''}`}
                                                        value={data.risk_level}
                                                        onChange={(e) => setData('risk_level', e.target.value)}
                                                    >
                                                        <option value="">Select Risk Level</option>
                                                        {riskLevels.map((level) => (
                                                            <option key={level} value={level}>
                                                                {level}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    {errors.risk_level && (
                                                        <div className="invalid-feedback">{errors.risk_level}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-4 col-md-12">
                                                <div className="mb-0">
                                                    <label className="form-label">Status</label>
                                                    <div className="form-check form-switch mt-2">
                                                        <input
                                                            className="form-check-input"
                                                            type="checkbox"
                                                            checked={data.is_active}
                                                            onChange={(e) => setData('is_active', e.target.checked)}
                                                        />
                                                        <label className="form-check-label">
                                                            {data.is_active ? 'Active' : 'Inactive'}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Financial Metrics Section */}
                                        <div className="row g-3 mb-4">
                                            <div className="col-12">
                                                <h4 className="text-dark fw-bold mb-3">Financial Metrics (Optional)</h4>
                                            </div>
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Total Loans Outstanding</label>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        className={`form-control ${errors.total_loans_outstanding ? 'is-invalid' : ''}`}
                                                        value={data.total_loans_outstanding}
                                                        onChange={(e) => setData('total_loans_outstanding', parseFloat(e.target.value) || 0)}
                                                        placeholder="0.00"
                                                    />
                                                    {errors.total_loans_outstanding && (
                                                        <div className="invalid-feedback">{errors.total_loans_outstanding}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Total Deposits</label>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        className={`form-control ${errors.total_deposits ? 'is-invalid' : ''}`}
                                                        value={data.total_deposits}
                                                        onChange={(e) => setData('total_deposits', parseFloat(e.target.value) || 0)}
                                                        placeholder="0.00"
                                                    />
                                                    {errors.total_deposits && (
                                                        <div className="invalid-feedback">{errors.total_deposits}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">NPL Exposure</label>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        className={`form-control ${errors.npl_exposure ? 'is-invalid' : ''}`}
                                                        value={data.npl_exposure}
                                                        onChange={(e) => setData('npl_exposure', parseFloat(e.target.value) || 0)}
                                                        placeholder="0.00"
                                                    />
                                                    {errors.npl_exposure && (
                                                        <div className="invalid-feedback">{errors.npl_exposure}</div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-lg-3 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Profitability</label>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        className={`form-control ${errors.profitability ? 'is-invalid' : ''}`}
                                                        value={data.profitability}
                                                        onChange={(e) => setData('profitability', parseFloat(e.target.value) || 0)}
                                                        placeholder="0.00"
                                                    />
                                                    {errors.profitability && (
                                                        <div className="invalid-feedback">{errors.profitability}</div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Demographics Section */}
                                        <div className="row g-3 mb-4">
                                            <div className="col-12">
                                                <h4 className="text-dark fw-bold mb-3">Demographics (Optional)</h4>
                                            </div>
                                            <div className="col-lg-2 col-md-4 col-sm-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Age</label>
                                                    <input
                                                        type="number"
                                                        className="form-control"
                                                        value={data.demographics.age}
                                                        onChange={(e) => setData('demographics', {...data.demographics, age: e.target.value})}
                                                        placeholder="Age"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-lg-2 col-md-4 col-sm-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Gender</label>
                                                    <select
                                                        className="form-select"
                                                        value={data.demographics.gender}
                                                        onChange={(e) => setData('demographics', {...data.demographics, gender: e.target.value})}
                                                    >
                                                        <option value="">Select Gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div className="col-lg-4 col-md-8">
                                                <div className="mb-0">
                                                    <label className="form-label">Occupation</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={data.demographics.occupation}
                                                        onChange={(e) => setData('demographics', {...data.demographics, occupation: e.target.value})}
                                                        placeholder="Occupation"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-lg-4 col-md-12">
                                                <div className="mb-0">
                                                    <label className="form-label">Address</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={data.demographics.address}
                                                        onChange={(e) => setData('demographics', {...data.demographics, address: e.target.value})}
                                                        placeholder="Street address"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div className="row g-3">
                                            <div className="col-lg-6 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">City</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={data.demographics.city}
                                                        onChange={(e) => setData('demographics', {...data.demographics, city: e.target.value})}
                                                        placeholder="City"
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-lg-6 col-md-6">
                                                <div className="mb-0">
                                                    <label className="form-label">Country</label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        value={data.demographics.country}
                                                        onChange={(e) => setData('demographics', {...data.demographics, country: e.target.value})}
                                                        placeholder="Country"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="card-footer text-end">
                                        <div className="d-flex">
                                            <Link href="/customers" className="btn btn-link">
                                                Cancel
                                            </Link>
                                            <button 
                                                type="submit" 
                                                className="btn btn-primary ms-auto"
                                                disabled={processing}
                                            >
                                                <IconDeviceFloppy size={16} className="me-1" />
                                                {processing ? 'Creating...' : 'Create Customer'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}