import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconArrowLeft, IconEdit, IconTrash, IconCode, IconDatabase, IconChartBar } from '@tabler/icons-react';

export default function Show({ product, categories, field_types }) {
    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const getCategoryBadgeColor = (category) => {
        const colors = {
            'Loan': 'bg-blue',
            'Deposit': 'bg-green',
            'Account': 'bg-blue-600',
            'Transaction': 'bg-orange-500',
            'Other': 'bg-gray'
        };
        return colors[category] || 'bg-secondary';
    };

    const getFieldTypeBadgeColor = (type) => {
        const colors = {
            'string': 'bg-blue-500',
            'number': 'bg-green',
            'boolean': 'bg-blue-600',
            'date': 'bg-orange-500',
            'array': 'bg-red',
            'object': 'bg-orange-400'
        };
        return colors[type] || 'bg-secondary';
    };

    return (
        <AppLayout title={`Product: ${product.name}`}>
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/products">Products</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">{product.name}</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Product Details
                            </div>
                            <h2 className="page-title">
                                {product.name}
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/products" className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Products
                                </Link>
                                <Link href={`/products/${product.id}/edit`} className="btn btn-primary">
                                    <IconEdit size={16} className="me-1" />
                                    Edit Product
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    <div className="row row-deck row-cards">
                        {/* Product Information */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconDatabase size={18} className="me-2" />
                                        Product Information
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="row">
                                        <div className="col-12">
                                            <div className="mb-3">
                                                <label className="form-label">Name</label>
                                                <div className="form-control-plaintext">{product.name}</div>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <div className="mb-3">
                                                <label className="form-label">Description</label>
                                                <div className="form-control-plaintext">
                                                    {product.description || 'No description provided'}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Category</label>
                                                <div>
                                                    <span className={`badge ${getCategoryBadgeColor(product.category)} text-white`}>
                                                        {product.category}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Status</label>
                                                <div>
                                                    <span className={`badge ${product.is_active ? 'bg-success' : 'bg-secondary'} text-white`}>
                                                        {product.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <div className="mb-3">
                                                <label className="form-label">
                                                    <IconChartBar size={16} className="me-1 text-primary" />
                                                    Portfolio Value Field
                                                </label>
                                                <div className="alert alert-info mb-0">
                                                    <div className="d-flex align-items-center">
                                                        <div className="flex-fill">
                                                            <strong className="text-dark">
                                                                {product.portfolio_value_field || 'Not set'}
                                                            </strong>
                                                            <div className="text-muted small mt-1">
                                                                This field is summed across all records to calculate the total portfolio value for this product.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Created</label>
                                                <div className="form-control-plaintext">
                                                    {formatDate(product.created_at)}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Last Updated</label>
                                                <div className="form-control-plaintext">
                                                    {formatDate(product.updated_at)}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Field Definitions */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconCode size={18} className="me-2" />
                                        Field Definitions
                                    </h3>
                                </div>
                                <div className="card-body">
                                    {product.field_definitions && Array.isArray(product.field_definitions) && product.field_definitions.length > 0 ? (
                                        <div className="table-responsive">
                                            <table className="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Field Name</th>
                                                        <th>Label</th>
                                                        <th>Type</th>
                                                        <th>Required</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {product.field_definitions.map((field, index) => (
                                                        <tr key={field.name || index}>
                                                            <td>
                                                                <code>{field.name || `Field ${index + 1}`}</code>
                                                            </td>
                                                            <td>
                                                                {field.label || field.name || `Field ${index + 1}`}
                                                            </td>
                                                            <td>
                                                                <span className={`badge ${getFieldTypeBadgeColor(field.type)} text-white`}>
                                                                    {field.type}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                {field.required ? (
                                                                    <span className="badge bg-danger text-white">Required</span>
                                                                ) : (
                                                                    <span className="badge bg-secondary text-white">Optional</span>
                                                                )}
                                                            </td>
                                                            <td>
                                                                <small className="text-muted">
                                                                    {field.description || '-'}
                                                                </small>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="empty">
                                            <div className="empty-img">
                                                <IconCode size={48} className="text-muted" />
                                            </div>
                                            <p className="empty-title">No field definitions</p>
                                            <p className="empty-subtitle text-muted">
                                                This product doesn't have any field definitions yet.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Associated Formulas */}
                        {product.formulas && product.formulas.length > 0 && (
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">Associated Formulas</h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="row">
                                            {product.formulas.map((formula) => (
                                                <div key={formula.id} className="col-md-6 mb-3">
                                                    <div className="card card-sm">
                                                        <div className="card-body">
                                                            <div className="d-flex align-items-center">
                                                                <div className="flex-fill">
                                                                    <div className="font-weight-medium">
                                                                        <Link href={`/formulas/${formula.id}`} className="text-decoration-none">
                                                                            {formula.name}
                                                                        </Link>
                                                                    </div>
                                                                    <div className="text-muted small">
                                                                        {formula.description}
                                                                    </div>
                                                                    <div className="text-muted small">
                                                                        Returns: {formula.return_type}
                                                                    </div>
                                                                </div>
                                                                <div className="ms-auto">
                                                                    <span className={`badge ${formula.is_active ? 'bg-success' : 'bg-secondary'} text-white`}>
                                                                        {formula.is_active ? 'Active' : 'Inactive'}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Product Data Statistics */}
                        {product.product_data && product.product_data.length > 0 && (
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">Data Statistics</h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="row">
                                            <div className="col-md-3">
                                                <div className="d-flex align-items-center">
                                                    <div className="subheader">Total Records</div>
                                                    <div className="ms-auto">
                                                        <div className="h1 mb-0">{product.product_data.length}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-md-3">
                                                <div className="d-flex align-items-center">
                                                    <div className="subheader">Latest Record</div>
                                                    <div className="ms-auto">
                                                        <div className="text-muted">
                                                            {formatDate(product.product_data[0]?.created_at)}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}