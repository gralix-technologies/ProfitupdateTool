import React, { useState, useRef, useEffect } from 'react';
import { 
    IconMath, 
    IconCheck, 
    IconAlertCircle, 
    IconInfoCircle,
    IconPlus,
    IconX,
    IconRefresh
} from '@tabler/icons-react';

export default function EnhancedFormulaEditor({ 
    value, 
    onChange, 
    supportedOperations = [], 
    placeholder = "Enter formula expression...",
    className = "",
    availableFields = {},
    onInsertField = null,
    onInsertFunction = null,
    validationResult = null,
    onValidate = null
}) {
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [cursorPosition, setCursorPosition] = useState(0);
    const [filteredOperations, setFilteredOperations] = useState([]);
    const [showFieldSuggestions, setShowFieldSuggestions] = useState(false);
    const [showFunctionSuggestions, setShowFunctionSuggestions] = useState(false);
    const [autoValidate, setAutoValidate] = useState(true);
    const textareaRef = useRef(null);


    const handleInputChange = (e) => {
        const newValue = e.target.value;
        const position = e.target.selectionStart;
        
        onChange(newValue);
        setCursorPosition(position);
        
        // Auto-validate if enabled
        if (autoValidate && onValidate && newValue.trim()) {
            setTimeout(() => onValidate(newValue), 500); // Debounce validation
        }
        
        // Check if we should show suggestions
        const textBeforeCursor = newValue.substring(0, position);
        const lastWord = textBeforeCursor.split(/[\s\(\)\+\-\*\/\,]/).pop();
        
        if (lastWord && lastWord.length > 0) {
            const filtered = supportedOperations.filter(op => 
                op.toLowerCase().startsWith(lastWord.toLowerCase())
            );
            
            if (filtered.length > 0) {
                setFilteredOperations(filtered);
                setShowSuggestions(true);
            } else {
                setShowSuggestions(false);
            }
        } else {
            setShowSuggestions(false);
        }
    };

    const insertAtCursor = (text) => {
        const textarea = textareaRef.current;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const before = value.substring(0, start);
        const after = value.substring(end);
        const newValue = before + text + after;
        
        onChange(newValue);
        
        // Set cursor position after inserted text
        setTimeout(() => {
            textarea.focus();
            textarea.setSelectionRange(start + text.length, start + text.length);
        }, 0);
    };

    const insertFunction = (funcName) => {
        const insertText = funcName + '()';
        insertAtCursor(insertText);
        setShowFunctionSuggestions(false);
    };

    const insertField = (fieldName) => {
        insertAtCursor(fieldName);
        setShowFieldSuggestions(false);
    };

    const insertOperator = (op) => {
        insertAtCursor(op);
    };

    const formatExpression = () => {
        // Basic formatting - add spaces around operators
        let formatted = value
            .replace(/\+/g, ' + ')
            .replace(/\-/g, ' - ')
            .replace(/\*/g, ' * ')
            .replace(/\//g, ' / ')
            .replace(/\s+/g, ' ')
            .trim();
        
        onChange(formatted);
    };

    const clearExpression = () => {
        onChange('');
        if (textareaRef.current) {
            textareaRef.current.focus();
        }
    };

    // Calculate formula complexity
    const getFormulaComplexity = () => {
        if (!value) return { level: 'Simple', score: 0 };
        
        const functions = (value.match(/\b(SUM|AVG|COUNT|MIN|MAX|IF|CASE|RATIO|PERCENTAGE|MOVING_AVG|GROWTH_RATE)\b/g) || []).length;
        const operators = (value.match(/[+\-*/=<>()]/g) || []).length;
        const nested = (value.match(/\([^)]*\(/g) || []).length;
        
        const score = functions * 2 + operators * 1 + nested * 3;
        
        if (score <= 3) return { level: 'Simple', score };
        if (score <= 8) return { level: 'Medium', score };
        if (score <= 15) return { level: 'Complex', score };
        return { level: 'Very Complex', score };
    };

    const complexity = getFormulaComplexity();

    return (
        <div>
            {/* Enhanced Formula Editor */}
            <div className="relative">
                <div className="border rounded-lg bg-white">
                    {/* Main Textarea */}
                    <textarea
                        ref={textareaRef}
                        value={value}
                        onChange={handleInputChange}
                        placeholder={placeholder}
                        className={`form-control font-monospace ${className}`}
                        style={{ 
                            minHeight: '120px', 
                            resize: 'vertical',
                            border: 'none',
                            outline: 'none'
                        }}
                        rows={5}
                    />
                </div>
                
                {/* Formula Stats */}
                {value && (
                    <div className="d-flex justify-content-between align-items-center mt-2">
                        <div className="d-flex gap-3">
                            <small className="text-muted">
                                Characters: {value.length}
                            </small>
                            <small className="text-muted">
                                Complexity: <span className={`badge ${
                                    complexity.level === 'Simple' ? 'bg-success' :
                                    complexity.level === 'Medium' ? 'bg-warning' :
                                    complexity.level === 'Complex' ? 'bg-danger' :
                                    'bg-dark'
                                }`}>{complexity.level}</span>
                            </small>
                        </div>
                        <div className="btn-group btn-group-sm">
                            <button
                                type="button"
                                className="btn btn-outline-secondary"
                                onClick={formatExpression}
                                disabled={!value.trim()}
                            >
                                <IconRefresh size={14} className="me-1" />
                                Format
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-danger"
                                onClick={clearExpression}
                                disabled={!value.trim()}
                            >
                                <IconX size={14} className="me-1" />
                                Clear
                            </button>
                        </div>
                    </div>
                )}
                
                {/* Validation Status */}
                {validationResult && (
                    <div className="mt-2">
                        {validationResult.valid ? (
                            <div className="alert alert-success alert-sm">
                                <IconCheck size={16} className="me-2" />
                                Formula is valid
                                {validationResult.result !== undefined && (
                                    <span className="ms-2">
                                        (Result: {validationResult.result})
                                    </span>
                                )}
                            </div>
                        ) : (
                            <div className="alert alert-danger alert-sm">
                                <IconAlertCircle size={16} className="me-2" />
                                {validationResult.errors?.join(', ') || 'Formula validation failed'}
                            </div>
                        )}
                    </div>
                )}
                
                {/* Suggestions Dropdown */}
                {showSuggestions && filteredOperations.length > 0 && (
                    <div className="position-absolute top-100 start-0 w-100 bg-white border rounded shadow-lg mt-1" style={{zIndex: 1000}}>
                        <div className="p-2" style={{maxHeight: '200px', overflowY: 'auto'}}>
                            {filteredOperations.map((operation) => (
                                <button
                                    key={operation}
                                    type="button"
                                    className="btn btn-sm btn-outline-primary w-100 text-start mb-1"
                                    onClick={() => insertFunction(operation)}
                                >
                                    <strong>{operation}</strong>
                                    <small className="text-muted ms-2">
                                        {operation === 'SUM' ? 'Sum values' :
                                         operation === 'AVG' ? 'Average values' :
                                         operation === 'COUNT' ? 'Count records' :
                                         operation === 'IF' ? 'Conditional logic' :
                                         'Function'}
                                    </small>
                                </button>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Enhanced Quick Insert Section */}
            <div className="card">
                <div className="card-header">
                    <div className="d-flex align-items-center justify-content-between">
                        <h6 className="card-title mb-0">
                            <IconMath size={16} className="me-2" />
                            Quick Insert Tools
                        </h6>
                        <div className="form-check form-switch">
                            <input 
                                className="form-check-input" 
                                type="checkbox" 
                                id="autoValidate"
                                checked={autoValidate}
                                onChange={(e) => setAutoValidate(e.target.checked)}
                            />
                            <label className="form-check-label small" htmlFor="autoValidate">
                                Auto-validate
                            </label>
                        </div>
                    </div>
                </div>
                <div className="card-body">
                    {/* Function Buttons */}
                    <div className="mb-3">
                        <h6 className="small text-muted mb-2">Functions:</h6>
                        <div className="d-flex flex-wrap gap-1">
                            {supportedOperations.map((operation) => (
                                <button
                                    key={operation}
                                    type="button"
                                    className="btn btn-outline-primary btn-sm"
                                    onClick={() => insertFunction(operation)}
                                    title={`Insert ${operation} function`}
                                >
                                    {operation}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Field Buttons */}
                    {Object.keys(availableFields).length > 0 && (
                        <div className="mb-3">
                            <h6 className="small text-muted mb-2">Available Fields:</h6>
                            <div className="d-flex flex-wrap gap-1">
                                {Object.keys(availableFields).map((fieldName) => (
                                    <button
                                        key={fieldName}
                                        type="button"
                                        className="btn btn-outline-success btn-sm"
                                        onClick={() => insertField(fieldName)}
                                        title={availableFields[fieldName]}
                                    >
                                        {fieldName}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Operators */}
                    <div className="mb-3">
                        <h6 className="small text-muted mb-2">Operators:</h6>
                        <div className="d-flex flex-wrap gap-1">
                            {[
                                { op: '+', desc: 'Add' },
                                { op: '-', desc: 'Subtract' },
                                { op: '*', desc: 'Multiply' },
                                { op: '/', desc: 'Divide' },
                                { op: '=', desc: 'Equals' },
                                { op: '>', desc: 'Greater than' },
                                { op: '<', desc: 'Less than' },
                                { op: '(', desc: 'Open parenthesis' },
                                { op: ')', desc: 'Close parenthesis' }
                            ].map(({op, desc}) => (
                                <button
                                    key={op}
                                    type="button"
                                    className="btn btn-outline-warning btn-sm"
                                    onClick={() => insertOperator(op)}
                                    title={desc}
                                >
                                    {op}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Quick Templates */}
                    <div className="mb-3">
                        <h6 className="small text-muted mb-2">Quick Templates:</h6>
                        <div className="d-flex flex-wrap gap-1">
                            <button
                                type="button"
                                className="btn btn-outline-info btn-sm"
                                onClick={() => insertAtCursor('SUM()')}
                                title="Sum template"
                            >
                                SUM()
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-info btn-sm"
                                onClick={() => insertAtCursor('AVG()')}
                                title="Average template"
                            >
                                AVG()
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-info btn-sm"
                                onClick={() => insertAtCursor('IF(condition, true_value, false_value)')}
                                title="If statement template"
                            >
                                IF()
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-info btn-sm"
                                onClick={() => insertAtCursor('RATIO(numerator, denominator)')}
                                title="Ratio calculation template"
                            >
                                RATIO()
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
