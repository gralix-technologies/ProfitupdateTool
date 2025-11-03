import React, { useState, useEffect } from 'react';
import { IconX, IconDeviceFloppy } from '@tabler/icons-react';

export default function WidgetConfigModal({ widget, onSave, onClose, formulas = [], products = [], customers = [], widgetTypes = {} }) {
    const [config, setConfig] = useState({
        title: widget.configuration?.title || widget.title || '',
        data_source: widget.configuration?.data_source || widget.data_source || '',
        formula_id: widget.configuration?.formula_id || widget.formula_id || '',
        product_id: widget.configuration?.product_id || widget.product_id || '',
        format: widget.configuration?.format || 'currency',
        precision: widget.configuration?.precision || 2,
        category: widget.configuration?.category || 'Portfolio',
        chart_options: widget.configuration?.chart_options || {},
        ...widget.configuration
    });

    const [errors, setErrors] = useState({});

    useEffect(() => {
        setConfig({
            title: widget.configuration?.title || widget.title || '',
            data_source: widget.configuration?.data_source || widget.data_source || '',
            formula_id: widget.configuration?.formula_id || widget.formula_id || '',
            product_id: widget.configuration?.product_id || widget.product_id || '',
            format: widget.configuration?.format || 'currency',
            precision: widget.configuration?.precision || 2,
            category: widget.configuration?.category || 'Portfolio',
            chart_options: widget.configuration?.chart_options || {},
            ...widget.configuration
        });
    }, [widget]);

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Basic validation
        const newErrors = {};
        if (!config.title.trim()) {
            newErrors.title = 'Title is required';
        }
        
        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        onSave(config);
    };

    // Filter formulas based on selected product
    const filteredFormulas = config.product_id 
        ? formulas.filter(formula => formula.product_id == config.product_id)
        : formulas;

    const handleInputChange = (field, value) => {
        setConfig(prev => ({
            ...prev,
            [field]: value
        }));
        
        // Clear formula selection when product changes
        if (field === 'product_id') {
            setConfig(prev => ({
                ...prev,
                [field]: value,
                formula_id: '' // Reset formula selection
            }));
        }
        
        // Clear error when user starts typing
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: null
            }));
        }
    };

    const handleChartOptionChange = (option, value) => {
        setConfig(prev => ({
            ...prev,
            chart_options: {
                ...prev.chart_options,
                [option]: value
            }
        }));
    };

    const renderWidgetSpecificConfig = () => {
        switch (widget.type) {
            case 'KPI':
                return (
                    <>
                        <div className="mb-3">
                            <label className="form-label">Value Format</label>
                            <select
                                className="form-select"
                                value={config.format || 'number'}
                                onChange={(e) => handleInputChange('format', e.target.value)}
                            >
                                <option value="number">Number</option>
                                <option value="currency">Currency</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Decimal Precision</label>
                            <select
                                className="form-select"
                                value={config.precision || 2}
                                onChange={(e) => handleInputChange('precision', parseInt(e.target.value))}
                            >
                                <option value={0}>0 decimal places</option>
                                <option value={1}>1 decimal place</option>
                                <option value={2}>2 decimal places</option>
                                <option value={3}>3 decimal places</option>
                            </select>
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Category</label>
                            <select
                                className="form-select"
                                value={config.category || ''}
                                onChange={(e) => handleInputChange('category', e.target.value)}
                            >
                                <option value="">Select Category</option>
                                <option value="Portfolio">Portfolio</option>
                                <option value="Risk">Risk</option>
                                <option value="Profitability">Profitability</option>
                                <option value="Distribution">Distribution</option>
                            </select>
                        </div>
                    </>
                );

            case 'BarChart':
            case 'LineChart':
                return (
                    <>
                        <div className="mb-3">
                            <label className="form-label">X-Axis Label</label>
                            <input
                                type="text"
                                className="form-control"
                                value={config.chart_options.xAxisLabel || ''}
                                onChange={(e) => handleChartOptionChange('xAxisLabel', e.target.value)}
                            />
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Y-Axis Label</label>
                            <input
                                type="text"
                                className="form-control"
                                value={config.chart_options.yAxisLabel || ''}
                                onChange={(e) => handleChartOptionChange('yAxisLabel', e.target.value)}
                            />
                        </div>
                        <div className="mb-3">
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={config.chart_options.showLegend || false}
                                    onChange={(e) => handleChartOptionChange('showLegend', e.target.checked)}
                                />
                                <label className="form-check-label">
                                    Show Legend
                                </label>
                            </div>
                        </div>
                        <div className="mb-3">
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={config.chart_options.showGrid || true}
                                    onChange={(e) => handleChartOptionChange('showGrid', e.target.checked)}
                                />
                                <label className="form-check-label">
                                    Show Grid
                                </label>
                            </div>
                        </div>
                    </>
                );

            case 'PieChart':
                return (
                    <>
                        {config.data_source === 'direct_data' && config.product_id && (
                            <>
                                <div className="mb-3">
                                    <label className="form-label">Group By Field</label>
                                    <select
                                        className="form-select"
                                        value={config.group_by || ''}
                                        onChange={(e) => setConfig(prev => ({ ...prev, group_by: e.target.value }))}
                                    >
                                        <option value="">Select field...</option>
                                        {products.find(p => p.id.toString() === config.product_id)?.field_definitions?.map((field) => (
                                            <option key={field.name} value={field.name}>
                                                {field.label || field.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Value Field</label>
                                    <select
                                        className="form-select"
                                        value={config.value_field || ''}
                                        onChange={(e) => setConfig(prev => ({ ...prev, value_field: e.target.value }))}
                                    >
                                        <option value="">Select field...</option>
                                        {products.find(p => p.id.toString() === config.product_id)?.field_definitions?.map((field) => (
                                            <option key={field.name} value={field.name}>
                                                {field.label || field.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Aggregation</label>
                                    <select
                                        className="form-select"
                                        value={config.aggregation || 'SUM'}
                                        onChange={(e) => setConfig(prev => ({ ...prev, aggregation: e.target.value }))}
                                    >
                                        <option value="SUM">Sum</option>
                                        <option value="COUNT">Count</option>
                                        <option value="AVG">Average</option>
                                        <option value="MIN">Minimum</option>
                                        <option value="MAX">Maximum</option>
                                    </select>
                                </div>
                            </>
                        )}
                        <div className="mb-3">
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={config.chart_options.showLabels || true}
                                    onChange={(e) => handleChartOptionChange('showLabels', e.target.checked)}
                                />
                                <label className="form-check-label">
                                    Show Labels
                                </label>
                            </div>
                        </div>
                        <div className="mb-3">
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={config.chart_options.showPercentages || true}
                                    onChange={(e) => handleChartOptionChange('showPercentages', e.target.checked)}
                                />
                                <label className="form-check-label">
                                    Show Percentages
                                </label>
                            </div>
                        </div>
                    </>
                );

            case 'Heatmap':
                return (
                    <>
                        {config.data_source === 'direct_data' && config.product_id && (
                            <>
                                <div className="mb-3">
                                    <label className="form-label">X-Axis Field</label>
                                    <select
                                        className="form-select"
                                        value={config.x_axis_field || ''}
                                        onChange={(e) => setConfig(prev => ({ ...prev, x_axis_field: e.target.value }))}
                                    >
                                        <option value="">Select field...</option>
                                        {products.find(p => p.id.toString() === config.product_id)?.field_definitions?.map((field) => (
                                            <option key={field.name} value={field.name}>
                                                {field.label || field.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Y-Axis Field</label>
                                    <select
                                        className="form-select"
                                        value={config.y_axis_field || ''}
                                        onChange={(e) => setConfig(prev => ({ ...prev, y_axis_field: e.target.value }))}
                                    >
                                        <option value="">Select field...</option>
                                        {products.find(p => p.id.toString() === config.product_id)?.field_definitions?.map((field) => (
                                            <option key={field.name} value={field.name}>
                                                {field.label || field.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="mb-3">
                                    <label className="form-label">Value Field</label>
                                    <select
                                        className="form-select"
                                        value={config.value_field || ''}
                                        onChange={(e) => setConfig(prev => ({ ...prev, value_field: e.target.value }))}
                                    >
                                        <option value="">Select field...</option>
                                        {products.find(p => p.id.toString() === config.product_id)?.field_definitions?.map((field) => (
                                            <option key={field.name} value={field.name}>
                                                {field.label || field.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </>
                        )}
                        <div className="mb-3">
                            <label className="form-label">Color Scheme</label>
                            <select
                                className="form-select"
                                value={config.chart_options.colorScheme || 'viridis'}
                                onChange={(e) => handleChartOptionChange('colorScheme', e.target.value)}
                            >
                                <option value="viridis">Viridis</option>
                                <option value="plasma">Plasma</option>
                                <option value="inferno">Inferno</option>
                                <option value="magma">Magma</option>
                                <option value="cividis">Cividis</option>
                            </select>
                        </div>
                    </>
                );

            case 'Table':
                return (
                    <>
                        <div className="mb-3">
                            <label className="form-label">Rows Per Page</label>
                            <select
                                className="form-select"
                                value={config.chart_options.pageSize || 10}
                                onChange={(e) => handleChartOptionChange('pageSize', parseInt(e.target.value))}
                            >
                                <option value={5}>5</option>
                                <option value={10}>10</option>
                                <option value={25}>25</option>
                                <option value={50}>50</option>
                                <option value={100}>100</option>
                            </select>
                        </div>
                        <div className="mb-3">
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={config.chart_options.sortable || true}
                                    onChange={(e) => handleChartOptionChange('sortable', e.target.checked)}
                                />
                                <label className="form-check-label">
                                    Enable Sorting
                                </label>
                            </div>
                        </div>
                        <div className="mb-3">
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={config.chart_options.searchable || true}
                                    onChange={(e) => handleChartOptionChange('searchable', e.target.checked)}
                                />
                                <label className="form-check-label">
                                    Enable Search
                                </label>
                            </div>
                        </div>
                    </>
                );

            default:
                return null;
        }
    };

    const handleBackdropClick = (e) => {
        if (e.target === e.currentTarget) {
            onClose();
        }
    };

    return (
        <div 
            className="modal modal-blur fade show" 
            style={{ display: 'block', zIndex: 1050 }}
            onClick={handleBackdropClick}
        >
            <div className="modal-dialog modal-lg modal-dialog-centered">
                <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-header">
                            <h5 className="modal-title">
                                Configure {widget.type} Widget
                            </h5>
                            <button
                                type="button"
                                className="btn-close"
                                onClick={onClose}
                                aria-label="Close"
                            >
                                <IconX size={16} />
                            </button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-12">
                                    <div className="mb-3">
                                        <label className="form-label required">
                                            Widget Title
                                        </label>
                                        <input
                                            type="text"
                                            className={`form-control ${errors.title ? 'is-invalid' : ''}`}
                                            value={config.title}
                                            onChange={(e) => handleInputChange('title', e.target.value)}
                                            placeholder="Enter widget title"
                                            required
                                        />
                                        {errors.title && (
                                            <div className="invalid-feedback">
                                                {errors.title}
                                            </div>
                                        )}
                                    </div>

                                    <div className="mb-3">
                                        <label className="form-label">Data Source</label>
                                        <select
                                            className="form-select"
                                            value={config.data_source}
                                            onChange={(e) => handleInputChange('data_source', e.target.value)}
                                        >
                                            <option value="">Select data source...</option>
                                            <option value="products">Products</option>
                                            <option value="customers">Customers</option>
                                            <option value="Custom Formula">Custom Formula</option>
                                        </select>
                                    </div>

                                    {config.data_source === 'Custom Formula' && (
                                        <>
                                            <div className="mb-3">
                                                <label className="form-label">Product (Optional)</label>
                                                <select
                                                    className="form-select"
                                                    value={config.product_id || ''}
                                                    onChange={(e) => handleInputChange('product_id', e.target.value)}
                                                >
                                                    <option value="">All Products</option>
                                                    {products.map((product) => (
                                                        <option key={product.id} value={product.id}>
                                                            {product.name} ({product.category})
                                                        </option>
                                                    ))}
                                                </select>
                                                <div className="form-text text-muted">
                                                    Select a product to filter formulas, or leave blank to see all formulas.
                                                </div>
                                            </div>

                                            <div className="mb-3">
                                                <label className="form-label">Formula</label>
                                                <select
                                                    className="form-select"
                                                    value={config.formula_id || ''}
                                                    onChange={(e) => handleInputChange('formula_id', e.target.value)}
                                                >
                                                    <option value="">Select formula...</option>
                                                    {filteredFormulas.map((formula) => (
                                                        <option key={formula.id} value={formula.id}>
                                                            {formula.name} ({formula.product?.name || 'No Product'})
                                                        </option>
                                                    ))}
                                                </select>
                                                {filteredFormulas.length === 0 && formulas.length > 0 && (
                                                    <div className="form-text text-muted">
                                                        No formulas available for the selected product. Try selecting a different product or create a formula for this product.
                                                    </div>
                                                )}
                                                {formulas.length === 0 && (
                                                    <div className="form-text text-muted">
                                                        No formulas available. <a href="/formulas/create" target="_blank">Create a formula</a> first.
                                                    </div>
                                                )}
                                            </div>
                                        </>
                                    )}

                                    {config.data_source === 'direct_data' && (
                                        <div className="mb-3">
                                            <label className="form-label">Product</label>
                                            <select
                                                className="form-select"
                                                value={config.product_id || ''}
                                                onChange={(e) => setConfig(prev => ({ ...prev, product_id: e.target.value }))}
                                            >
                                                <option value="">Select product...</option>
                                                {products.map((product) => (
                                                    <option key={product.id} value={product.id}>
                                                        {product.name} ({product.category})
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}

                                    <hr />

                                    <h6 className="mb-3">Widget Options</h6>
                                    {renderWidgetSpecificConfig()}
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary"
                                onClick={onClose}
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                className="btn btn-primary"
                            >
                                <IconDeviceFloppy size={16} className="me-1" />
                                Save Widget
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}