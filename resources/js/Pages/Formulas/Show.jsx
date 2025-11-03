import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    IconArrowLeft, 
    IconEdit, 
    IconTrash, 
    IconCode, 
    IconChartBar,
    IconCopy,
    IconPlayerPlay,
    IconDownload
} from '@tabler/icons-react';

export default function Show({ formula, usageStats }) {
    const [testResult, setTestResult] = useState(null);
    const [testData, setTestData] = useState('{}');
    const [testing, setTesting] = useState(false);

    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getReturnTypeBadgeColor = (type) => {
        const colors = {
            'numeric': 'bg-blue-500',
            'text': 'bg-green',
            'boolean': 'bg-blue-600',
            'date': 'bg-orange-500'
        };
        return colors[type] || 'bg-secondary';
    };

    // Handle radio button changes
    useEffect(() => {
        const handleRadioChange = (e) => {
            const customSection = document.getElementById('custom-data-section');
            if (e.target.value === 'custom') {
                customSection.style.display = 'block';
            } else {
                customSection.style.display = 'none';
            }
        };

        const radioButtons = document.querySelectorAll('input[name="test-data-source"]');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', handleRadioChange);
        });

        return () => {
            radioButtons.forEach(radio => {
                radio.removeEventListener('change', handleRadioChange);
            });
        };
    }, []);

    const handleTest = async () => {
        setTesting(true);
        try {
            const selectedDataSource = document.querySelector('input[name="test-data-source"]:checked').value;
            
            let requestBody = {
                expression: formula.expression,
                product_id: formula.product_id,
                use_real_data: selectedDataSource === 'real'
            };

            if (selectedDataSource === 'custom') {
                try {
                    requestBody.sample_data = JSON.parse(testData);
                } catch (e) {
                    setTestResult({
                        success: false,
                        message: 'Invalid JSON format in custom data'
                    });
                    setTesting(false);
                    return;
                }
            }

            // Use the standardized API client instead of fetch
            const { default: apiClient } = await import('../../utils/apiClient');
            const response = await apiClient.post('/formulas/test', requestBody);
            setTestResult(response.data);
        } catch (error) {
            setTestResult({
                success: false,
                message: 'Test failed: ' + error.message
            });
        } finally {
            setTesting(false);
        }
    };

    const handleDuplicate = async () => {
        try {
            const response = await fetch(`/api/formulas/${formula.id}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    name: `${formula.name} (Copy)`,
                    description: `Copy of ${formula.name}`
                })
            });
            
            if (response.ok) {
                const result = await response.json();
                window.location.href = `/formulas/${result.formula.id}`;
            }
        } catch (error) {
            console.error('Failed to duplicate formula:', error);
        }
    };

    const handleExport = async () => {
        try {
            const response = await fetch(`/api/formulas/${formula.id}/export`);
            const result = await response.json();
            
            if (result.success) {
                const blob = new Blob([JSON.stringify(result.data, null, 2)], {
                    type: 'application/json'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `formula-${formula.name.toLowerCase().replace(/\s+/g, '-')}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        } catch (error) {
            console.error('Failed to export formula:', error);
        }
    };

    return (
        <AppLayout title={`Formula: ${formula.name}`}>
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/formulas">Formulas</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">{formula.name}</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Formula Details
                            </div>
                            <h2 className="page-title">
                                {formula.name}
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/formulas" className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Formulas
                                </Link>
                                <button className="btn" onClick={handleDuplicate}>
                                    <IconCopy size={16} className="me-1" />
                                    Duplicate
                                </button>
                                <button className="btn" onClick={handleExport}>
                                    <IconDownload size={16} className="me-1" />
                                    Export
                                </button>
                                <Link href={`/formulas/${formula.id}/edit`} className="btn btn-primary">
                                    <IconEdit size={16} className="me-1" />
                                    Edit Formula
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    <div className="row row-deck row-cards">
                        {/* Formula Information */}
                        <div className="col-md-8">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconCode size={18} className="me-2" />
                                        Formula Details
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="row">
                                        <div className="col-12">
                                            <div className="mb-3">
                                                <label className="form-label">Name</label>
                                                <div className="form-control-plaintext">{formula.name}</div>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <div className="mb-3">
                                                <label className="form-label">Description</label>
                                                <div className="form-control-plaintext">
                                                    {formula.description || 'No description provided'}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-12">
                                            <div className="mb-3">
                                                <label className="form-label">Expression</label>
                                                <div className="card">
                                                    <div className="card-body">
                                                        <pre className="mb-0"><code>{formula.expression}</code></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Return Type</label>
                                                <div>
                                                    <span className={`badge ${getReturnTypeBadgeColor(formula.return_type)} text-white`}>
                                                        {formula.return_type}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Status</label>
                                                <div>
                                                    <span className={`badge ${formula.is_active ? 'bg-success' : 'bg-secondary'} text-white`}>
                                                        {formula.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Product</label>
                                                <div className="form-control-plaintext">
                                                    {formula.product ? (
                                                        <Link href={`/products/${formula.product.id}`} className="text-decoration-none">
                                                            {formula.product.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-muted">Global Formula</span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Created By</label>
                                                <div className="form-control-plaintext">
                                                    {formula.creator ? formula.creator.name : 'Unknown'}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Created</label>
                                                <div className="form-control-plaintext">
                                                    {formatDate(formula.created_at)}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Last Updated</label>
                                                <div className="form-control-plaintext">
                                                    {formatDate(formula.updated_at)}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Usage Statistics */}
                        <div className="col-md-4">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconChartBar size={18} className="me-2" />
                                        Usage Statistics
                                    </h3>
                                </div>
                                <div className="card-body">
                                    {usageStats ? (
                                        <div className="row">
                                            <div className="col-12">
                                                <div className="d-flex align-items-center mb-3">
                                                    <div className="subheader">Total Executions</div>
                                                    <div className="ms-auto">
                                                        <div className="h3 mb-0">{usageStats.total_executions || 0}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-12">
                                                <div className="d-flex align-items-center mb-3">
                                                    <div className="subheader">Success Rate</div>
                                                    <div className="ms-auto">
                                                        <div className="h3 mb-0">{usageStats.success_rate || 0}%</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-12">
                                                <div className="d-flex align-items-center mb-3">
                                                    <div className="subheader">Avg Execution Time</div>
                                                    <div className="ms-auto">
                                                        <div className="h3 mb-0">{usageStats.avg_execution_time || 0}ms</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-12">
                                                <div className="d-flex align-items-center">
                                                    <div className="subheader">Last Used</div>
                                                    <div className="ms-auto">
                                                        <div className="text-muted">
                                                            {usageStats.last_used ? formatDate(usageStats.last_used) : 'Never'}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="empty">
                                            <div className="empty-img">
                                                <IconChartBar size={48} className="text-muted" />
                                            </div>
                                            <p className="empty-title">No usage data</p>
                                            <p className="empty-subtitle text-muted">
                                                This formula hasn't been used yet.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Formula Tester */}
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconPlayerPlay size={18} className="me-2" />
                                        Test Formula
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Test Data Source</label>
                                                <div className="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                                    <label className="form-selectgroup-item flex-fill">
                                                        <input 
                                                            type="radio" 
                                                            name="test-data-source" 
                                                            value="real" 
                                                            className="form-selectgroup-input" 
                                                            defaultChecked
                                                        />
                                                        <div className="form-selectgroup-label">
                                                            <div className="fw-bold">Use Real Database Data</div>
                                                            <div className="text-muted">Test formula with actual product data from database</div>
                                                        </div>
                                                    </label>
                                                    <label className="form-selectgroup-item flex-fill">
                                                        <input 
                                                            type="radio" 
                                                            name="test-data-source" 
                                                            value="custom" 
                                                            className="form-selectgroup-input"
                                                        />
                                                        <div className="form-selectgroup-label">
                                                            <div className="fw-bold">Custom Sample Data</div>
                                                            <div className="text-muted">Provide your own test data</div>
                                                        </div>
                                                    </label>
                                                </div>
                                                <div id="custom-data-section" style={{ display: 'none' }}>
                                                    <textarea
                                                        className="form-control mt-3"
                                                        rows="6"
                                                        value={testData}
                                                        onChange={(e) => setTestData(e.target.value)}
                                                        placeholder='{"field1": 100, "field2": 200}'
                                                    />
                                                    <div className="form-hint">
                                                        Provide sample data in JSON format to test the formula
                                                    </div>
                                                </div>
                                            </div>
                                            <button 
                                                className="btn btn-primary"
                                                onClick={handleTest}
                                                disabled={testing}
                                            >
                                                <IconPlayerPlay size={16} className="me-1" />
                                                {testing ? 'Testing...' : 'Test Formula'}
                                            </button>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <label className="form-label">Test Result</label>
                                                <div className="card">
                                                    <div className="card-body">
                                                        {testResult ? (
                                                            <div>
                                                                {testResult.success ? (
                                                                    <div>
                                                                        <div className="text-success mb-2">✓ Test Successful</div>
                                                                        <div><strong>Result:</strong> {JSON.stringify(testResult.result)}</div>
                                                                        <div><strong>Type:</strong> {testResult.result_type}</div>
                                                                        <div><strong>Execution Time:</strong> {testResult.execution_time}ms</div>
                                                                    </div>
                                                                ) : (
                                                                    <div>
                                                                        <div className="text-danger mb-2">✗ Test Failed</div>
                                                                        <div>{testResult.message}</div>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <div className="text-muted">
                                                                Click "Test Formula" to see results here
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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