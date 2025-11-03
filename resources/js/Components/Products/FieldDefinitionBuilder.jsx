import React, { useState, useEffect } from 'react';
import { IconPlus, IconTrash } from '@tabler/icons-react';

export default function FieldDefinitionBuilder({ fieldDefinitions, fieldTypes, onChange }) {
    const [fields, setFields] = useState(fieldDefinitions || []);

    // Update local state when fieldDefinitions prop changes
    useEffect(() => {
        if (fieldDefinitions && fieldDefinitions.length > 0) {
            setFields(fieldDefinitions);
        }
    }, [fieldDefinitions]);


    const updateFields = (newFields) => {
        setFields(newFields);
        onChange(newFields);
    };

    const addField = () => {
        const newField = {
            name: '',
            type: 'Text',
            required: false
        };
        updateFields([...fields, newField]);
    };

    const removeField = (index) => {
        const newFields = fields.filter((_, i) => i !== index);
        updateFields(newFields);
    };

    const updateField = (index, property, value) => {
        const newFields = [...fields];
        newFields[index] = {
            ...newFields[index],
            [property]: value
        };
        
        // Handle type changes
        if (property === 'type') {
            if (value !== 'Lookup') {
                // Remove options field entirely for non-Lookup types
                delete newFields[index].options;
            } else {
                // Auto-add one option if switching to Lookup type
                if (!newFields[index].options || newFields[index].options.length === 0) {
                    newFields[index].options = [''];
                }
            }
        }
        
        updateFields(newFields);
    };

    const addOption = (fieldIndex) => {
        const newFields = [...fields];
        if (!newFields[fieldIndex].options) {
            newFields[fieldIndex].options = [];
        }
        newFields[fieldIndex].options.push('');
        updateFields(newFields);
    };

    const updateOption = (fieldIndex, optionIndex, value) => {
        const newFields = [...fields];
        newFields[fieldIndex].options[optionIndex] = value;
        updateFields(newFields);
    };

    const removeOption = (fieldIndex, optionIndex) => {
        const newFields = [...fields];
        newFields[fieldIndex].options.splice(optionIndex, 1);
        updateFields(newFields);
    };

    return (
        <div>
            {fields.map((field, fieldIndex) => (
                <div key={fieldIndex} className="border rounded p-3 mb-2 bg-light">
                    <div className="d-flex align-items-center justify-content-between mb-2">
                        <h6 className="mb-0 text-primary">Field {fieldIndex + 1}</h6>
                        <button
                            type="button"
                            className="btn btn-outline-danger btn-sm"
                            onClick={() => removeField(fieldIndex)}
                        >
                            <IconTrash size={14} />
                        </button>
                    </div>

                    <div className="row g-2">
                        <div className="col-md-2">
                            <label className="form-label small">Name</label>
                            <input
                                type="text"
                                className="form-control form-control-sm"
                                value={field.name || ''}
                                onChange={(e) => updateField(fieldIndex, 'name', e.target.value)}
                                placeholder="customer_id"
                            />
                        </div>
                        
                        <div className="col-md-2">
                            <label className="form-label small">Label</label>
                            <input
                                type="text"
                                className="form-control form-control-sm"
                                value={field.label || ''}
                                onChange={(e) => updateField(fieldIndex, 'label', e.target.value)}
                                placeholder="Customer ID"
                            />
                        </div>

                        <div className="col-md-2">
                            <label className="form-label small">Type</label>
                            <select
                                className="form-select form-select-sm"
                                value={field.type || 'Text'}
                                onChange={(e) => updateField(fieldIndex, 'type', e.target.value)}
                            >
                                {(fieldTypes || []).map((type) => (
                                    <option key={type} value={type}>
                                        {type}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="col-md-2">
                            <label className="form-label small">Required</label>
                            <div className="form-check form-switch">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={field.required || false}
                                    onChange={(e) => updateField(fieldIndex, 'required', e.target.checked)}
                                />
                            </div>
                        </div>
                        
                        <div className="col-md-4">
                            <label className="form-label small">Description</label>
                            <input
                                type="text"
                                className="form-control form-control-sm"
                                value={field.description || ''}
                                onChange={(e) => updateField(fieldIndex, 'description', e.target.value)}
                                placeholder="Brief description"
                            />
                        </div>
                    </div>

                    {/* Lookup Options - Compact */}
                    {field.type === 'Lookup' && (
                        <div className="mt-2">
                            <div className="d-flex align-items-center justify-content-between mb-1">
                                <label className="form-label small mb-0">Lookup Options</label>
                                <button
                                    type="button"
                                    className="btn btn-outline-primary btn-sm"
                                    onClick={() => addOption(fieldIndex)}
                                >
                                    <IconPlus size={12} className="me-1" />
                                    Add
                                </button>
                            </div>
                            
                            <div className="row g-1">
                                {(field.options || []).map((option, optionIndex) => (
                                    <div key={optionIndex} className="col-md-6">
                                        <div className="input-group input-group-sm">
                                            <input
                                                type="text"
                                                className="form-control form-control-sm"
                                                value={option}
                                                onChange={(e) => updateOption(fieldIndex, optionIndex, e.target.value)}
                                                placeholder={`Option ${optionIndex + 1}`}
                                            />
                                            <button
                                                type="button"
                                                className="btn btn-outline-danger btn-sm"
                                                onClick={() => removeOption(fieldIndex, optionIndex)}
                                            >
                                                <IconTrash size={12} />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            
                            {(!field.options || field.options.length === 0) && (
                                <div className="alert alert-warning alert-sm mt-1 py-2">
                                    <div className="d-flex align-items-center">
                                        <span className="me-2">⚠️</span>
                                        <div>
                                            <strong>Lookup fields require at least one option</strong>
                                            <br />
                                            <small>Click "Add" to add options for this field</small>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            ))}

            <button
                type="button"
                className="btn btn-outline-primary btn-sm"
                onClick={addField}
            >
                <IconPlus size={14} className="me-1" />
                Add Field
            </button>

            {fields.length === 0 && (
                <div className="text-center py-4 text-muted">
                    <IconPlus size={32} className="mb-2" />
                    <p className="mb-0">No field definitions yet</p>
                    <small>Click "Add Field" to define the data structure</small>
                </div>
            )}
        </div>
    );
}