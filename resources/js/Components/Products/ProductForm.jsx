import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import FieldDefinitionBuilder from './FieldDefinitionBuilder';

export default function ProductForm({ 
    data,
    setData,
    errors = {},
    categories, 
    field_types,
    processing = false,
    submitText = 'Save Product',
    cancelUrl = '/products',
    onSubmit
}) {
    const handleInputChange = (field, value) => {
        setData(field, value);
    };

    const handleFieldDefinitionsChange = (fieldDefinitions) => {
        setData('field_definitions', fieldDefinitions);
    };

    return (
        <div className="card">
            <div className="card-header">
                <h3 className="card-title text-dark">Product Information</h3>
            </div>
            <div className="card-body">
                {/* Basic Information - Full Width Layout */}
                <div className="row g-3 mb-4">
                    <div className="col-lg-4 col-md-6">
                        <div className="mb-0">
                            <label className="form-label required">Product Name</label>
                            <input
                                type="text"
                                className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                value={data.name}
                                onChange={(e) => handleInputChange('name', e.target.value)}
                                placeholder="Enter product name"
                            />
                            {errors.name && (
                                <div className="invalid-feedback">{errors.name}</div>
                            )}
                        </div>
                    </div>

                    <div className="col-lg-4 col-md-6">
                        <div className="mb-0">
                            <label className="form-label required">Category</label>
                            <select 
                                className={`form-select ${errors.category ? 'is-invalid' : ''}`}
                                value={data.category}
                                onChange={(e) => handleInputChange('category', e.target.value)}
                            >
                                <option value="">Select category</option>
                                {categories.map((category) => (
                                    <option key={category} value={category}>
                                        {category}
                                    </option>
                                ))}
                            </select>
                            {errors.category && (
                                <div className="invalid-feedback">{errors.category}</div>
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
                                    onChange={(e) => handleInputChange('is_active', e.target.checked)}
                                />
                                <span className="form-check-label">Active Product</span>
                            </div>
                            <div className="form-hint">
                                Active products are available for use in the system.
                            </div>
                        </div>
                    </div>
                </div>

                <div className="row g-3 mb-4">
                    <div className="col-12">
                        <div className="mb-0">
                            <label className="form-label">Description</label>
                            <textarea
                                className="form-control"
                                rows="3"
                                value={data.description}
                                onChange={(e) => handleInputChange('description', e.target.value)}
                                placeholder="Enter product description"
                            />
                            <div className="form-hint">
                                Provide a detailed description of the product and its purpose.
                            </div>
                        </div>
                    </div>
                </div>

                <div className="row g-3 mb-4">
                    <div className="col-lg-6 col-md-8">
                        <div className="mb-0">
                            <label className="form-label required">Portfolio Value Field</label>
                            <select 
                                className={`form-select ${errors.portfolio_value_field ? 'is-invalid' : ''}`}
                                value={data.portfolio_value_field || ''}
                                onChange={(e) => handleInputChange('portfolio_value_field', e.target.value)}
                            >
                                <option value="">Select field for portfolio value</option>
                                {(data.field_definitions || [])
                                    .filter(field => {
                                        if (!field || !field.type) return false;
                                        const type = (field.type || '').toLowerCase();
                                        return ['numeric', 'number', 'decimal', 'integer', 'float'].includes(type);
                                    })
                                    .map((field, index) => (
                                        <option key={`pvf-${field.name}-${index}`} value={field.name}>
                                            {field.name} - {field.label || field.description || 'No label'} ({field.type})
                                        </option>
                                    ))
                                }
                            </select>
                            <div className="form-hint">
                                This numeric field will be summed to calculate total portfolio value.
                                Examples: outstanding_balance, balance, principal_amount
                            </div>
                            {errors.portfolio_value_field && (
                                <div className="invalid-feedback">{errors.portfolio_value_field}</div>
                            )}
                        </div>
                    </div>
                    <div className="col-lg-6 col-md-4">
                        <div className="mb-0">
                            <label className="form-label">Quick Actions</label>
                            <div className="d-flex flex-column gap-2">
                                <button 
                                    type="button" 
                                    className="btn btn-outline-primary btn-sm"
                                    onClick={() => handleInputChange('category', 'Loan')}
                                >
                                    Set as Loan Product
                                </button>
                                <button 
                                    type="button" 
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={() => handleInputChange('category', 'Deposit')}
                                >
                                    Set as Deposit Product
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Field Definitions - Compact */}
                <div className="row">
                    <div className="col-12">
                        <div className="card">
                            <div className="card-header py-2">
                                <h5 className="card-title text-dark mb-0">Field Definitions</h5>
                                <div className="card-subtitle text-muted small">
                                    Define the data fields for this product
                                </div>
                            </div>
                            <div className="card-body py-3" style={{maxHeight: '400px', overflowY: 'auto'}}>
                                <FieldDefinitionBuilder
                                    fieldDefinitions={data.field_definitions}
                                    fieldTypes={field_types}
                                    onChange={handleFieldDefinitionsChange}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Submit Buttons */}
                <div className="card-footer bg-transparent mt-auto">
                    <div className="btn-list justify-content-end">
                        <Link href={cancelUrl} className="btn">
                            Cancel
                        </Link>
                        <button type="button" className="btn btn-primary" disabled={processing} onClick={onSubmit}>
                            {processing ? (
                                <>
                                    <div className="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Saving...
                                </>
                            ) : (
                                submitText
                            )}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}