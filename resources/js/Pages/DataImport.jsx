import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { IconUpload, IconDownload, IconCheck, IconAlertTriangle } from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';
import { useToast } from '@/Hooks/useToast';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function DataImport({ products = [], templates = [] }) {
    const { data, setData, post, processing, errors } = useFormWithCsrf({
        product_id: '',
        csv_file: null,
        field_mapping: {}
    });

    const [mappingComplete, setMappingComplete] = useState(false);
    const [uploadedData, setUploadedData] = useState([]);
    const [fieldMappings, setFieldMappings] = useState({});
    const { showSuccess, showError } = useToast();

    const handleFileUpload = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('csv_file', file);
            
            // Parse CSV file for preview
            const reader = new FileReader();
            reader.onload = (event) => {
                const csvText = event.target.result;
                const lines = csvText.split('\n');
                const headers = lines[0].split(',').map(h => h.trim());
                const dataRows = lines.slice(1, 6).map(line => {
                    const values = line.split(',').map(v => v.trim());
                    return headers.reduce((obj, header, index) => {
                        obj[header] = values[index] || '';
                        return obj;
                    }, {});
                });
                
                setUploadedData(dataRows);
                setMappingComplete(true);
            };
            reader.readAsText(file);
        }
    };

    const handleFieldMapping = (csvField, productField) => {
        setFieldMappings(prev => ({
            ...prev,
            [csvField]: productField
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (!data.product_id) {
            showError('Please select a product');
            return;
        }
        
        if (!data.csv_file) {
            showError('Please select a CSV file');
            return;
        }

        post('/data-import', {
            onSuccess: () => {
                showSuccess('Data imported successfully!');
            },
            onError: (errors) => {
                if (typeof errors === 'object' && errors !== null) {
                    const errorMessages = Object.values(errors).flat();
                    showError(errorMessages.join(', '), 'Import Error');
                } else if (typeof errors === 'string') {
                    showError(errors);
                } else {
                    showError('Failed to import data. Please try again.');
                }
            }
        });
    };

    const selectedProduct = products.find(p => p.id.toString() === data.product_id);

    return (
        <AppLayout>
            <Head title="Data Import" />
            
            <div className="page-header d-print-none">
                <div className="container-fluid px-3">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <h2 className="page-title">
                                <IconUpload size={24} className="me-2" />
                                Data Import
                            </h2>
                            <div className="text-muted">
                                Upload your product data to populate dashboards
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-fluid px-3">
                    <div className="row g-3">
                        {/* Import Configuration */}
                        <div className="col-lg-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">Import Configuration</h3>
                                </div>
                                <div className="card-body">
                                    <form onSubmit={handleSubmit}>
                                        <div className="mb-3">
                                            <label className="form-label">Product</label>
                                            <select
                                                className="form-control form-control-lg"
                                                value={data.product_id}
                                                onChange={(e) => setData('product_id', e.target.value)}
                                                required
                                            >
                                                <option value="">Select a product...</option>
                                                {products.map((product) => (
                                                    <option key={product.id} value={product.id}>
                                                        {product.name} ({product.category})
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.product_id && (
                                                <div className="text-danger mt-1">{errors.product_id}</div>
                                            )}
                                        </div>

                                        <div className="mb-3">
                                            <label className="form-label">CSV File</label>
                                            <input
                                                type="file"
                                                className="form-control form-control-lg"
                                                accept=".csv"
                                                onChange={handleFileUpload}
                                                required
                                            />
                                            {errors.csv_file && (
                                                <div className="text-danger mt-1">{errors.csv_file}</div>
                                            )}
                                        </div>

                                        {mappingComplete && selectedProduct && (
                                            <div className="mb-3">
                                                <h6>Field Mapping</h6>
                                                <div className="table-responsive">
                                                    <table className="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>CSV Column</th>
                                                                <th>Product Field</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {Object.keys(uploadedData[0] || {}).map((csvField) => (
                                                                <tr key={csvField}>
                                                                    <td>
                                                                        <code>{csvField}</code>
                                                                    </td>
                                                                    <td>
                                                                        <select
                                                                            className="form-select form-select-sm"
                                                                            value={fieldMappings[csvField] || ''}
                                                                            onChange={(e) => handleFieldMapping(csvField, e.target.value)}
                                                                        >
                                                                            <option value="">Map to field...</option>
                                                                            {selectedProduct.field_definitions?.map((field) => (
                                                                                <option key={field.name} value={field.name}>
                                                                                    {field.name} ({field.type})
                                                                                </option>
                                                                            ))}
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        )}

                                        <div className="d-grid">
                                            <button
                                                type="submit"
                                                className="btn btn-primary btn-lg"
                                                disabled={processing}
                                            >
                                                {processing ? (
                                                    <>
                                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                        Importing...
                                                    </>
                                                ) : (
                                                    <>
                                                        <IconUpload size={20} className="me-2" />
                                                        Import Data
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {/* Data Preview */}
                        <div className="col-lg-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">Data Preview</h3>
                                </div>
                                <div className="card-body">
                                    {uploadedData.length > 0 ? (
                                        <div className="table-responsive">
                                            <table className="table table-sm">
                                                <thead>
                                                    <tr>
                                                        {Object.keys(uploadedData[0]).map((header) => (
                                                            <th key={header}>{header}</th>
                                                        ))}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {uploadedData.map((row, index) => (
                                                        <tr key={index}>
                                                            {Object.values(row).map((value, cellIndex) => (
                                                                <td key={cellIndex}>{value}</td>
                                                            ))}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="text-center text-muted py-4">
                                            <IconUpload size={48} className="mb-3 opacity-50" />
                                            <p>Upload a CSV file to preview data</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Templates */}
                            <div className="card mt-3">
                                <div className="card-header">
                                    <h3 className="card-title">Download Templates</h3>
                                </div>
                                <div className="card-body">
                                    <div className="list-group list-group-flush">
                                        {templates.map((template) => (
                                            <div key={template.id} className="list-group-item">
                                                <div className="row align-items-center">
                                                    <div className="col">
                                                        <h6 className="mb-1">{template.name}</h6>
                                                        <p className="text-muted mb-0">{template.description}</p>
                                                    </div>
                                                    <div className="col-auto">
                                                        <a 
                                                            href={template.download_url}
                                                            className="btn btn-outline-primary btn-sm"
                                                        >
                                                            <IconDownload size={16} className="me-1" />
                                                            Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
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
