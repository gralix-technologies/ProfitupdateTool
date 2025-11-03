import React, { useState, useRef, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import { getCsrfToken, validateCsrfToken } from '@/Utils/csrf';

const FileUpload = ({ products, onUploadStart, onUploadComplete, onUploadError }) => {
    const [selectedFile, setSelectedFile] = useState(null);
    const [validationResult, setValidationResult] = useState(null);
    const [isValidating, setIsValidating] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [fieldRequirements, setFieldRequirements] = useState(null);
    const [isLoadingRequirements, setIsLoadingRequirements] = useState(false);
    const [isDownloadingSample, setIsDownloadingSample] = useState(false);
    const fileInputRef = useRef(null);

    const { data, setData, errors, setError, clearErrors } = useForm({
        product_id: '',
        mode: 'append'
    });

    const handleFileSelect = (event) => {
        const file = event.target.files[0];
        setSelectedFile(file);
        setValidationResult(null);
        clearErrors();

        if (file && data.product_id) {
            validateFile(file);
        }
    };

    const handleProductChange = (event) => {
        const productId = event.target.value;
        setData('product_id', productId);
        setValidationResult(null);
        setFieldRequirements(null);
        clearErrors();

        if (productId) {
            loadFieldRequirements(productId);
        }

        if (selectedFile && productId) {
            validateFile(selectedFile);
        }
    };

    const loadFieldRequirements = async (productId) => {
        setIsLoadingRequirements(true);
        try {
            const response = await axios.get('/api/data/field-requirements', {
                params: { product_id: productId }
            });
            setFieldRequirements(response.data.field_requirements);
        } catch (error) {
            console.error('Failed to load field requirements:', error);
        } finally {
            setIsLoadingRequirements(false);
        }
    };

    const downloadSampleFile = async () => {
        if (!data.product_id) {
            onUploadError?.('Please select a product first');
            return;
        }

        setIsDownloadingSample(true);
        try {
            const response = await axios.get('/api/data/sample-file', {
                params: { 
                    product_id: data.product_id,
                    rows: 5
                },
                responseType: 'blob',
                timeout: 30000 // 30 second timeout
            });

            // Check if response is actually JSON error (sometimes happens when API returns JSON instead of blob)
            if (response.data.type === 'application/json') {
                const text = await response.data.text();
                const jsonError = JSON.parse(text);
                throw new Error(jsonError.message || 'Failed to generate sample file');
            }

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv' }));
            const link = document.createElement('a');
            link.href = url;
            
            // Get product name for better filename
            const product = products.find(p => p.id == data.product_id);
            const productName = product ? product.name.replace(/[^a-zA-Z0-9]/g, '_') : 'product';
            const filename = `sample_${productName}_${new Date().toISOString().split('T')[0]}.csv`;
            
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            
            // Cleanup
            setTimeout(() => {
                link.remove();
                window.URL.revokeObjectURL(url);
            }, 100);
            
            // Success notification
            console.log('✓ Sample file downloaded successfully:', filename);
            
        } catch (error) {
            console.error('Failed to download sample file:', error);
            
            // Enhanced error message
            let errorMessage = 'Failed to download sample file';
            
            if (error.response) {
                // Server responded with error
                if (error.response.status === 404) {
                    errorMessage = 'Sample file endpoint not found. Please contact support.';
                } else if (error.response.status === 403) {
                    errorMessage = 'Permission denied. You may not have access to download sample files.';
                } else if (error.response.status === 500) {
                    errorMessage = 'Server error generating sample file. Please try again or contact support.';
                } else if (error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                }
            } else if (error.request) {
                // Request made but no response
                errorMessage = 'No response from server. Please check your connection.';
            } else {
                // Error setting up request
                errorMessage = error.message || 'Failed to download sample file';
            }
            
            onUploadError?.(errorMessage);
        } finally {
            setIsDownloadingSample(false);
        }
    };

    const validateFile = async (file) => {
        if (!file || !data.product_id) return;

        setIsValidating(true);
        const formData = new FormData();
        formData.append('file', file);
        formData.append('product_id', data.product_id);

        try {
            const response = await axios.post('/api/data/validate-file', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            setValidationResult(response.data);
            clearErrors();
        } catch (error) {
            const errorMessage = error.response?.data?.message || 'File validation failed';
            setError('file', errorMessage);
            setValidationResult(null);
        } finally {
            setIsValidating(false);
        }
    };

    const handleUpload = async () => {
        if (!selectedFile || !data.product_id || !validationResult?.success) {
            return;
        }

        // Ensure CSRF token is available
        if (!validateCsrfToken()) {
            console.error('CSRF token not available for file upload');
            onUploadError('CSRF token not available. Please refresh the page.');
            return;
        }

        setIsUploading(true);
        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('product_id', data.product_id);
        formData.append('mode', data.mode);
        
        // Add CSRF token
        const csrfToken = getCsrfToken();
        if (csrfToken) {
            formData.append('_token', csrfToken);
        }

        try {
            const response = await axios.post('/api/data/upload', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            if (response.data.success) {
                onUploadStart?.(response.data);
                // Reset form
                setSelectedFile(null);
                setValidationResult(null);
                setData({ product_id: '', mode: 'append' });
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || 'Upload failed';
            onUploadError?.(errorMessage);
        } finally {
            setIsUploading(false);
        }
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <div className="card">
            <div className="card-header">
                <h3 className="card-title">Upload CSV Data</h3>
            </div>
            <div className="card-body">
                {/* Product Selection */}
                <div className="mb-3">
                    <label className="form-label">Select Product</label>
                    <select
                        className={`form-select ${errors.product_id ? 'is-invalid' : ''}`}
                        value={data.product_id}
                        onChange={handleProductChange}
                        disabled={isUploading}
                    >
                        <option value="">Choose a product...</option>
                        {products.map((product) => (
                            <option key={product.id} value={product.id}>
                                {product.name} ({product.category})
                            </option>
                        ))}
                    </select>
                    {errors.product_id && (
                        <div className="invalid-feedback">{errors.product_id}</div>
                    )}
                </div>

                {/* Field Requirements Display */}
                {data.product_id && (
                    <div className="mb-3">
                        <div className="d-flex justify-content-between align-items-center mb-2">
                            <label className="form-label mb-0">Field Requirements</label>
                            <button
                                type="button"
                                className="btn btn-outline-primary btn-sm"
                                onClick={downloadSampleFile}
                                disabled={isDownloadingSample || isLoadingRequirements}
                            >
                                {isDownloadingSample ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-1" role="status"></span>
                                        Downloading...
                                    </>
                                ) : (
                                    <>
                                        <svg xmlns="http://www.w3.org/2000/svg" className="icon me-1" width="16" height="16" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                            <path d="M7 11l5 5l5 -5"/>
                                            <path d="M12 4v12"/>
                                        </svg>
                                        Download Sample
                                    </>
                                )}
                            </button>
                        </div>
                        
                        {isLoadingRequirements ? (
                            <div className="text-center py-3">
                                <div className="spinner-border spinner-border-sm me-2" role="status"></div>
                                Loading field requirements...
                            </div>
                        ) : fieldRequirements ? (
                            <div className="card card-sm">
                                <div className="card-body">
                                    <div className="row">
                                        {fieldRequirements.map((field, index) => (
                                            <div key={index} className="col-md-6 mb-2">
                                                <div className="d-flex align-items-center">
                                                    <span className={`badge me-2 ${field.required ? 'bg-red' : 'bg-blue'}`}>
                                                        {field.required ? 'Required' : 'Optional'}
                                                    </span>
                                                    <span className="font-weight-medium">{field.name}</span>
                                                    <span className="text-muted ms-2">({field.type})</span>
                                                </div>
                                                {field.description && (
                                                    <small className="text-muted d-block">{field.description}</small>
                                                )}
                                                {field.options && (
                                                    <small className="text-muted d-block">
                                                        Options: {field.options.join(', ')}
                                                    </small>
                                                )}
                                                {field.constraints && (
                                                    <small className="text-muted d-block">
                                                        Constraints: {Object.entries(field.constraints).map(([key, value]) => `${key}: ${value}`).join(', ')}
                                                    </small>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        ) : null}
                    </div>
                )}

                {/* Import Mode Selection */}
                <div className="mb-3">
                    <label className="form-label">Import Mode</label>
                    <div className="form-selectgroup">
                        <label className="form-selectgroup-item">
                            <input
                                type="radio"
                                name="mode"
                                value="append"
                                className="form-selectgroup-input"
                                checked={data.mode === 'append'}
                                onChange={(e) => setData('mode', e.target.value)}
                                disabled={isUploading}
                            />
                            <span className="form-selectgroup-label">
                                <span className="form-selectgroup-title">Append</span>
                                <span className="form-selectgroup-text">Add new data to existing records</span>
                            </span>
                        </label>
                        <label className="form-selectgroup-item">
                            <input
                                type="radio"
                                name="mode"
                                value="overwrite"
                                className="form-selectgroup-input"
                                checked={data.mode === 'overwrite'}
                                onChange={(e) => setData('mode', e.target.value)}
                                disabled={isUploading}
                            />
                            <span className="form-selectgroup-label">
                                <span className="form-selectgroup-title">Overwrite</span>
                                <span className="form-selectgroup-text">Replace all existing data</span>
                            </span>
                        </label>
                    </div>
                </div>

                {/* File Selection */}
                <div className="mb-3">
                    <label className="form-label">CSV File</label>
                    <input
                        ref={fileInputRef}
                        type="file"
                        className={`form-control ${errors.file ? 'is-invalid' : ''}`}
                        accept=".csv,.txt"
                        onChange={handleFileSelect}
                        disabled={isUploading}
                    />
                    {errors.file && (
                        <div className="invalid-feedback">{errors.file}</div>
                    )}
                    <div className="form-hint">
                        Maximum file size: 200MB. Supported formats: CSV, TXT
                    </div>
                </div>

                {/* File Information */}
                {selectedFile && (
                    <div className="mb-3">
                        <div className="card card-sm">
                            <div className="card-body">
                                <div className="row align-items-center">
                                    <div className="col-auto">
                                        <span className="bg-blue text-white avatar">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                            </svg>
                                        </span>
                                    </div>
                                    <div className="col">
                                        <div className="font-weight-medium">{selectedFile.name}</div>
                                        <div className="text-muted">{formatFileSize(selectedFile.size)}</div>
                                    </div>
                                    {isValidating && (
                                        <div className="col-auto">
                                            <div className="spinner-border spinner-border-sm text-blue" role="status"></div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Validation Result */}
                {validationResult && (
                    <div className="mb-3">
                        {validationResult.success ? (
                            <div className="alert alert-success">
                                <div className="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" className="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M5 12l5 5l10 -10"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 className="alert-title">File validation passed!</h4>
                                        <div className="text-muted">
                                            Headers found: {validationResult.headers?.join(', ')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="alert alert-danger">
                                <div className="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" className="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M10.29 3.86l-1.82 -1.82a2 2 0 0 0 -2.83 0l-1.82 1.82a2 2 0 0 0 0 2.83l1.82 1.82"/>
                                            <path d="M13.71 20.14l1.82 1.82a2 2 0 0 0 2.83 0l1.82 -1.82a2 2 0 0 0 0 -2.83l-1.82 -1.82"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 className="alert-title">File validation failed</h4>
                                        <div className="text-muted">{validationResult.message}</div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Upload Button */}
                <div className="d-flex justify-content-end">
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={handleUpload}
                        disabled={!selectedFile || !data.product_id || !validationResult?.success || isUploading}
                    >
                        {isUploading ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                Starting Upload...
                            </>
                        ) : (
                            'Start Import'
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default FileUpload;