import React, { useState, useEffect } from 'react';
import EnhancedFormulaEditor from './EnhancedFormulaEditor';
import FormulaTemplates from './FormulaTemplates';
import FormulaValidator from './FormulaValidator';
import { useFormulaApi } from '../../Hooks/useFormulaApi';
import { 
    IconPlayerPlay, 
    IconCircleCheck, 
    IconCircleX, 
    IconAlertTriangle,
    IconBook,
    IconInfoCircle,
    IconChevronDown,
    IconChevronUp,
    IconTestPipe,
    IconCopy,
    IconDownload
} from '@tabler/icons-react';

export default function FormulaForm({ 
    formula = null, 
    onSubmit, 
    onCancel, 
    products = [], 
    returnTypes = [], 
    supportedOperations = [],
    fieldSuggestions = {},
    functionDocumentation = {},
    isSubmitting = false,
    submitLabel = 'Save Formula'
}) {
    const [formData, setFormData] = useState({
        name: formula?.name || '',
        expression: formula?.expression || '',
        description: formula?.description || '',
        product_id: formula?.product_id || '',
        return_type: formula?.return_type || 'numeric',
        is_active: formula?.is_active ?? true,
        parameters: formula?.parameters || {}
    });

    const [showTemplates, setShowTemplates] = useState(false);
    const [showFunctionDocs, setShowFunctionDocs] = useState(false);
    const [selectedFunction, setSelectedFunction] = useState(null);
    const [testData, setTestData] = useState([]);
    const [formulaHistory, setFormulaHistory] = useState([]);
    const [showHistory, setShowHistory] = useState(false);

    // Use the standardized API hook
    const {
        loading,
        validationResult,
        testResult,
        productFields,
        validateFormula,
        testFormula,
        getProductFields,
        generateSampleData,
        clearResults,
        setValidationResult,
        setTestResult
    } = useFormulaApi();

    // Fetch product fields when product changes
    useEffect(() => {
        if (formData.product_id) {
            getProductFields(formData.product_id);
        }
    }, [formData.product_id, getProductFields]);

    const handleInputChange = (field, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
        
        // Clear validation when expression changes
        if (field === 'expression') {
            setValidationResult(null);
            setTestResult(null);
            
            // Add to history
            if (value && value !== formData.expression) {
                setFormulaHistory(prev => [
                    { expression: value, timestamp: new Date() },
                    ...prev.slice(0, 9) // Keep last 10 entries
                ]);
            }
        }
    };

    // Enhanced validation with real-time feedback
    const handleRealTimeValidation = async (expression) => {
        if (!expression.trim()) return;
        
        try {
            await validateFormula(expression, formData.product_id || null);
        } catch (error) {
            // Error handling is done in the hook
        }
    };

    // Generate test data for the selected product
    const generateTestData = () => {
        if (!formData.product_id) return;
        
        const mockData = generateSampleData(productFields, 5);
        setTestData(mockData);
    };

    // Copy formula to clipboard
    const copyFormula = () => {
        navigator.clipboard.writeText(formData.expression);
        // Could add a toast notification here
    };

    // Export formula as JSON
    const exportFormula = () => {
        const formulaData = {
            name: formData.name,
            expression: formData.expression,
            description: formData.description,
            return_type: formData.return_type,
            parameters: formData.parameters,
            exported_at: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(formulaData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${formData.name || 'formula'}.json`;
        a.click();
        URL.revokeObjectURL(url);
    };

    const handleValidateFormula = async () => {
        if (!formData.expression.trim()) {
            return;
        }

        try {
            const result = await validateFormula(formData.expression, formData.product_id || null);
            // Success/error feedback is handled by the hook
        } catch (error) {
            // Error handling is done in the hook
        }
    };

    const handleTestFormula = async () => {
        if (!validationResult?.valid) {
            alert('Please validate the formula first');
            return;
        }

        try {
            const result = await testFormula(
                formData.expression, 
                formData.product_id || null, 
                testData.length > 0 ? testData : null
            );
            // Success/error feedback is handled by the hook
        } catch (error) {
            // Error handling is done in the hook
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (!formData.name.trim()) {
            return;
        }
        
        if (!formData.expression.trim()) {
            return;
        }

        onSubmit(formData);
    };

    const handleTemplateSelect = (template) => {
        setFormData(prev => ({
            ...prev,
            name: prev.name || template.name,
            expression: template.expression,
            description: prev.description || template.description
        }));
        setShowTemplates(false);
        setValidationResult(null);
        setTestResult(null);
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* Basic Information - Optimized Layout */}
            <div className="row g-3 mb-4">
                <div className="col-lg-3 col-md-6">
                    <div className="mb-0">
                        <label className="form-label required">Formula Name</label>
                        <input
                            type="text"
                            className="form-control form-control-lg"
                            value={formData.name}
                            onChange={(e) => handleInputChange('name', e.target.value)}
                            placeholder="Enter formula name"
                            required
                        />
                    </div>
                </div>

                <div className="col-lg-3 col-md-6">
                    <div className="mb-0">
                        <label className="form-label required">Return Type</label>
                        <select 
                            className="form-select form-select-lg"
                            value={formData.return_type}
                            onChange={(e) => handleInputChange('return_type', e.target.value)}
                        >
                            {returnTypes.map((type) => (
                                <option key={type} value={type}>
                                    {type.charAt(0).toUpperCase() + type.slice(1)}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="col-lg-3 col-md-6">
                    <div className="mb-0">
                        <label className="form-label">Status</label>
                        <div className="form-check form-switch mt-2">
                            <input
                                className="form-check-input"
                                type="checkbox"
                                checked={formData.is_active}
                                onChange={(e) => handleInputChange('is_active', e.target.checked)}
                            />
                            <label className="form-check-label">
                                {formData.is_active ? 'Active' : 'Inactive'}
                            </label>
                        </div>
                    </div>
                </div>

                <div className="col-lg-3 col-md-6">
                    <div className="mb-0">
                        <label className="form-label">Quick Actions</label>
                        <div className="d-flex flex-column gap-2">
                            <button 
                                type="button" 
                                className="btn btn-outline-primary btn-sm"
                                onClick={() => handleInputChange('return_type', 'numeric')}
                            >
                                Set Numeric Return
                            </button>
                            <button 
                                type="button" 
                                className="btn btn-outline-secondary btn-sm"
                                onClick={() => handleInputChange('return_type', 'percentage')}
                            >
                                Set Percentage Return
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="row g-3 mb-4">
                <div className="col-lg-6 col-md-8">
                    <div className="mb-0">
                        <label className="form-label">Product (Optional)</label>
                        <select 
                            className="form-select form-select-lg"
                            value={formData.product_id?.toString() || ''}
                            onChange={(e) => handleInputChange('product_id', e.target.value || null)}
                        >
                            <option value="">Select a Product (Optional)</option>
                            {products.map((product) => (
                                <option key={product.id} value={product.id.toString()}>
                                    {product.name} ({product.category})
                                </option>
                            ))}
                        </select>
                        <div className="form-hint">
                            Select a product to get field suggestions and product-specific validation
                        </div>
                    </div>
                </div>
                <div className="col-lg-6 col-md-4">
                    <div className="mb-0">
                        <label className="form-label">Description</label>
                        <textarea
                            className="form-control"
                            rows="3"
                            value={formData.description}
                            onChange={(e) => handleInputChange('description', e.target.value)}
                            placeholder="Describe what this formula calculates"
                        />
                    </div>
                </div>
            </div>

            {/* Product Field Suggestions */}
            {formData.product_id && Object.keys(productFields).length > 0 && (
                <div className="row mb-4">
                    <div className="col-12">
                        <div className="card">
                            <div className="card-header">
                                <h4 className="card-title">
                                    Available Fields for {products.find(p => p.id.toString() === formData.product_id.toString())?.name}
                                </h4>
                            </div>
                            <div className="card-body">
                                <div className="row g-2">
                                    {Object.entries(productFields).map(([fieldName, description]) => (
                                        <div key={fieldName} className="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                            <div className="card card-sm border-primary">
                                                <div className="card-body p-2">
                                                    <div className="d-flex align-items-center">
                                                        <div className="flex-grow-1">
                                                            <div className="fw-bold text-primary small">{fieldName}</div>
                                                            <div className="text-muted" style={{fontSize: '0.7rem'}}>{description}</div>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            className="btn btn-primary btn-sm ms-2"
                                                            style={{fontSize: '0.7rem', padding: '0.2rem 0.4rem'}}
                                                            onClick={() => {
                                                                const newExpression = formData.expression + (formData.expression ? ' ' : '') + fieldName;
                                                                handleInputChange('expression', newExpression);
                                                            }}
                                                        >
                                                            +
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}


            {/* Formula Expression */}
            <div className="row mb-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-header">
                            <div className="d-flex align-items-center justify-content-between">
                                <h4 className="card-title">Formula Expression</h4>
                                <button
                                    type="button"
                                    className="btn btn-outline-primary btn-sm"
                                    onClick={() => setShowTemplates(!showTemplates)}
                                >
                                    <IconBook size={16} className="me-1" />
                                    Templates
                                </button>
                            </div>
                        </div>
                        <div className="card-body">
                            {showTemplates && (
                                <div className="mb-3">
                                    <FormulaTemplates onSelect={handleTemplateSelect} />
                                </div>
                            )}

                            <div className="mb-3">
                                <EnhancedFormulaEditor
                                    value={formData.expression}
                                    onChange={(value) => handleInputChange('expression', value)}
                                    supportedOperations={supportedOperations}
                                    availableFields={productFields}
                                    placeholder="Enter your formula expression (e.g., SUM(amount) + AVG(balance))"
                                    validationResult={validationResult}
                                    onValidate={handleRealTimeValidation}
                                />
                            </div>

                            {/* Enhanced Validation and Testing */}
                            <div className="d-flex justify-content-between align-items-center mb-3">
                                <div className="btn-group">
                                    <button
                                        type="button"
                                        className="btn btn-outline-success"
                                        onClick={handleValidateFormula}
                                        disabled={loading || !formData.expression.trim()}
                                    >
                                        {loading ? (
                                            <div className="spinner-border spinner-border-sm me-2" role="status"></div>
                                        ) : (
                                            <IconCircleCheck size={16} className="me-1" />
                                        )}
                                        Validate
                                    </button>

                                    {validationResult?.valid && (
                                        <button
                                            type="button"
                                            className="btn btn-outline-primary"
                                            onClick={handleTestFormula}
                                        >
                                            <IconTestPipe size={16} className="me-1" />
                                            Test with Data
                                        </button>
                                    )}

                                    {formData.product_id && (
                                        <button
                                            type="button"
                                            className="btn btn-outline-info"
                                            onClick={generateTestData}
                                        >
                                            <IconPlayerPlay size={16} className="me-1" />
                                            Generate Test Data
                                        </button>
                                    )}
                                </div>

                                <div className="btn-group">
                                    <button
                                        type="button"
                                        className="btn btn-outline-secondary btn-sm"
                                        onClick={copyFormula}
                                        disabled={!formData.expression.trim()}
                                        title="Copy formula to clipboard"
                                    >
                                        <IconCopy size={14} />
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-outline-secondary btn-sm"
                                        onClick={exportFormula}
                                        disabled={!formData.expression.trim()}
                                        title="Export formula as JSON"
                                    >
                                        <IconDownload size={14} />
                                    </button>
                                </div>
                            </div>

                            {/* Validation Results */}
                            {validationResult && (
                                <div className="mt-3">
                                    <FormulaValidator result={validationResult} />
                                </div>
                            )}

                            {/* Test Data Display */}
                            {testData.length > 0 && (
                                <div className="mt-3">
                                    <div className="card">
                                        <div className="card-header">
                                            <h6 className="card-title">Generated Test Data</h6>
                                        </div>
                                        <div className="card-body">
                                            <div className="table-responsive">
                                                <table className="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            {Object.keys(testData[0] || {}).map(field => (
                                                                <th key={field}>{field}</th>
                                                            ))}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {testData.map((row, index) => (
                                                            <tr key={index}>
                                                                <td>{index + 1}</td>
                                                                {Object.values(row).map((value, idx) => (
                                                                    <td key={idx}>
                                                                        {typeof value === 'number' ? value.toFixed(2) : String(value)}
                                                                    </td>
                                                                ))}
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Test Results */}
                            {testResult && (
                                <div className="mt-3">
                                    <div className="card">
                                        <div className="card-header">
                                            <h5 className="card-title">Test Results</h5>
                                        </div>
                                        <div className="card-body">
                                            {testResult.execution_result !== null ? (
                                                <div className="d-flex align-items-center text-success">
                                                    <IconCircleCheck size={16} className="me-2" />
                                                    <span>Result: {testResult.execution_result}</span>
                                                </div>
                                            ) : testResult.execution_error ? (
                                                <div className="d-flex align-items-center text-danger">
                                                    <IconCircleX size={16} className="me-2" />
                                                    <span>Error: {testResult.execution_error}</span>
                                                </div>
                                            ) : null}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Formula History */}
            {formulaHistory.length > 0 && (
                <div className="row mb-4">
                    <div className="col-12">
                        <div className="card">
                            <div className="card-header">
                                <div className="d-flex align-items-center justify-content-between">
                                    <h5 className="card-title">
                                        <IconBook size={20} className="me-2" />
                                        Formula History
                                    </h5>
                                    <button
                                        type="button"
                                        className="btn btn-outline-secondary btn-sm"
                                        onClick={() => setShowHistory(!showHistory)}
                                    >
                                        {showHistory ? (
                                            <>
                                                <IconChevronUp size={16} className="me-1" />
                                                Hide History
                                            </>
                                        ) : (
                                            <>
                                                <IconChevronDown size={16} className="me-1" />
                                                Show History
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                            {showHistory && (
                                <div className="card-body">
                                    <div className="list-group">
                                        {formulaHistory.map((entry, index) => (
                                            <div key={index} className="list-group-item">
                                                <div className="d-flex justify-content-between align-items-start">
                                                    <div className="flex-grow-1">
                                                        <code className="small">{entry.expression}</code>
                                                    </div>
                                                    <div className="ms-3">
                                                        <button
                                                            type="button"
                                                            className="btn btn-outline-primary btn-sm"
                                                            onClick={() => handleInputChange('expression', entry.expression)}
                                                        >
                                                            Restore
                                                        </button>
                                                    </div>
                                                </div>
                                                <small className="text-muted">
                                                    {entry.timestamp.toLocaleString()}
                                                </small>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Function Documentation */}
            <div className="row mb-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-header">
                            <div className="d-flex align-items-center justify-content-between">
                                <h5 className="card-title">
                                    <IconBook size={20} className="me-2" />
                                    Function Reference & Examples
                                </h5>
                                <button
                                    type="button"
                                    className="btn btn-outline-primary btn-sm"
                                    onClick={() => setShowFunctionDocs(!showFunctionDocs)}
                                >
                                    {showFunctionDocs ? (
                                        <>
                                            <IconChevronUp size={16} className="me-1" />
                                            Hide Details
                                        </>
                                    ) : (
                                        <>
                                            <IconChevronDown size={16} className="me-1" />
                                            Show Details
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                        <div className="card-body">
                            {showFunctionDocs ? (
                                <div className="row g-3">
                                    {Object.entries(functionDocumentation).map(([functionName, docs]) => (
                                        <div key={functionName} className="col-lg-6 col-xl-4">
                                            <div className="card card-sm border">
                                                <div className="card-header bg-light">
                                                    <div className="d-flex align-items-center justify-content-between">
                                                        <h6 className="card-title mb-0 text-primary">{functionName}</h6>
                                                        <span className={`badge ${
                                                            docs.category === 'aggregation' ? 'bg-primary' :
                                                            docs.category === 'conditional' ? 'bg-warning' :
                                                            docs.category === 'calculation' ? 'bg-success' :
                                                            docs.category === 'statistical' ? 'bg-info' :
                                                            'bg-secondary'
                                                        }`}>
                                                            {docs.category}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="card-body">
                                                    <p className="small mb-2">{docs.description}</p>
                                                    <div className="mb-2">
                                                        <strong className="small">Syntax:</strong>
                                                        <code className="small ms-1">{docs.syntax}</code>
                                                    </div>
                                                    {docs.examples && (
                                                        <div className="mb-2">
                                                            <strong className="small">Examples:</strong>
                                                            <ul className="small mb-0 mt-1">
                                                                {Object.entries(docs.examples).map(([example, description]) => (
                                                                    <li key={example}>
                                                                        <code className="small">{example}</code>
                                                                        <br />
                                                                        <span className="text-muted">{description}</span>
                                                                    </li>
                                                                ))}
                                                            </ul>
                                                        </div>
                                                    )}
                                                    {docs.use_cases && (
                                                        <div className="mb-2">
                                                            <strong className="small">Use Cases:</strong>
                                                            <ul className="small mb-0 mt-1">
                                                                {docs.use_cases.map((useCase, index) => (
                                                                    <li key={index}>{useCase}</li>
                                                                ))}
                                                            </ul>
                                                        </div>
                                                    )}
                                                    {docs.note && (
                                                        <div className="alert alert-info alert-sm mb-0">
                                                            <IconInfoCircle size={14} className="me-1" />
                                                            <small>{docs.note}</small>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="d-flex flex-wrap gap-1">
                                    {Object.keys(functionDocumentation).map((functionName) => (
                                        <button
                                            key={functionName}
                                            type="button"
                                            className="btn btn-outline-primary btn-sm"
                                            onClick={() => {
                                                setSelectedFunction(functionName);
                                                setShowFunctionDocs(true);
                                            }}
                                        >
                                            {functionName}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Form Actions */}
            <div className="card-footer bg-transparent mt-auto">
                <div className="btn-list justify-content-end">
                    <button
                        type="button"
                        className="btn"
                        onClick={onCancel}
                        disabled={isSubmitting}
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={isSubmitting}
                    >
                        {isSubmitting && (
                            <div className="spinner-border spinner-border-sm me-2" role="status"></div>
                        )}
                        {submitLabel}
                    </button>
                </div>
            </div>
        </form>
    );
}