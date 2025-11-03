import React, { useState, useRef, useEffect } from 'react';
import { 
    IconMath, 
    IconCheck, 
    IconAlertCircle, 
    IconInfoCircle,
    IconChevronDown,
    IconPlus
} from '@tabler/icons-react';

export default function FormulaEditor({ 
    value, 
    onChange, 
    supportedOperations = [], 
    placeholder = "Enter formula expression...",
    className = "",
    availableFields = [],
    onInsertField = null,
    onInsertFunction = null,
    validationResult = null
}) {
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [cursorPosition, setCursorPosition] = useState(0);
    const [filteredOperations, setFilteredOperations] = useState([]);
    const [showFieldSuggestions, setShowFieldSuggestions] = useState(false);
    const [showFunctionSuggestions, setShowFunctionSuggestions] = useState(false);
    const textareaRef = useRef(null);

    // Syntax highlighting function
    const highlightSyntax = (text) => {
        if (!text) return '';
        
        return text
            // Highlight functions
            .replace(/\b(SUM|AVG|COUNT|MIN|MAX|IF|CASE|RATIO|PERCENTAGE|MOVING_AVG|GROWTH_RATE)\b/g, 
                '<span class="text-primary fw-bold">$1</span>')
            // Highlight operators
            .replace(/([+\-*/=<>()])/g, '<span class="text-warning fw-bold">$1</span>')
            // Highlight numbers
            .replace(/\b(\d+\.?\d*)\b/g, '<span class="text-success">$1</span>')
            // Highlight strings
            .replace(/"([^"]*)"/g, '<span class="text-info">"$1"</span>');
    };

    const handleInputChange = (e) => {
        const newValue = e.target.value;
        const position = e.target.selectionStart;
        
        onChange(newValue);
        setCursorPosition(position);
        
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

    const handleKeyDown = (e) => {
        if (e.key === 'Tab' && showSuggestions && filteredOperations.length > 0) {
            e.preventDefault();
            insertSuggestion(filteredOperations[0]);
        } else if (e.key === 'Escape') {
            setShowSuggestions(false);
        }
    };

    const insertSuggestion = (operation) => {
        const textBeforeCursor = value.substring(0, cursorPosition);
        const textAfterCursor = value.substring(cursorPosition);
        const lastWordStart = textBeforeCursor.lastIndexOf(textBeforeCursor.split(/[\s\(\)\+\-\*\/\,]/).pop());
        
        const newValue = 
            value.substring(0, lastWordStart) + 
            operation + 
            (operation.endsWith('(') ? '' : '(') +
            textAfterCursor;
        
        onChange(newValue);
        setShowSuggestions(false);
        
        // Focus back to textarea and set cursor position
        setTimeout(() => {
            if (textareaRef.current) {
                const newPosition = lastWordStart + operation.length + 1;
                textareaRef.current.focus();
                textareaRef.current.setSelectionRange(newPosition, newPosition);
            }
        }, 0);
    };

    const insertOperation = (operation) => {
        const textarea = textareaRef.current;
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const textBefore = value.substring(0, start);
        const textAfter = value.substring(end);
        
        const operationText = operation + '(';
        const newValue = textBefore + operationText + textAfter;
        const newCursorPosition = start + operationText.length;
        
        onChange(newValue);
        
        // Set cursor position after the operation
        setTimeout(() => {
            textarea.focus();
            textarea.setSelectionRange(newCursorPosition, newCursorPosition);
        }, 0);
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

    return (
        <div className="space-y-4">
            <div className="relative">
                <Textarea
                    ref={textareaRef}
                    value={value}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder}
                    className={`font-mono text-sm min-h-[120px] ${className}`}
                    rows={5}
                />
                
                {/* Suggestions Dropdown */}
                {showSuggestions && filteredOperations.length > 0 && (
                    <Card className="absolute top-full left-0 right-0 z-10 mt-1 max-h-48 overflow-y-auto">
                        <CardContent className="p-2">
                            <div className="space-y-1">
                                {filteredOperations.map((operation) => (
                                    <Button
                                        key={operation}
                                        variant="ghost"
                                        size="sm"
                                        className="w-full justify-start text-left font-mono"
                                        onClick={() => insertSuggestion(operation)}
                                    >
                                        {operation}
                                    </Button>
                                ))}
                            </div>
                            <div className="text-xs text-gray-500 mt-2 pt-2 border-t">
                                Press Tab to insert first suggestion, Esc to close
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Quick Insert Buttons */}
            <div className="space-y-3">
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <span>Quick Insert:</span>
                </div>
                
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    {supportedOperations.slice(0, 12).map((operation) => (
                        <Button
                            key={operation}
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => insertOperation(operation)}
                            className="text-xs font-mono"
                        >
                            {operation}
                        </Button>
                    ))}
                </div>
                
                {supportedOperations.length > 12 && (
                    <details className="text-sm">
                        <summary className="cursor-pointer text-gray-600 hover:text-gray-800">
                            Show all operations ({supportedOperations.length - 12} more)
                        </summary>
                        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 mt-2">
                            {supportedOperations.slice(12).map((operation) => (
                                <Button
                                    key={operation}
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => insertOperation(operation)}
                                    className="text-xs font-mono"
                                >
                                    {operation}
                                </Button>
                            ))}
                        </div>
                    </details>
                )}
            </div>

            {/* Format Button */}
            <div className="flex justify-end">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={formatExpression}
                    disabled={!value.trim()}
                >
                    Format Expression
                </Button>
            </div>
        </div>
    );
}