import React from 'react';

export const Textarea = ({ 
    className = '', 
    placeholder = '',
    value,
    onChange,
    disabled = false,
    rows = 4,
    ...props 
}) => {
    return (
        <textarea
            className={`form-control ${className}`}
            placeholder={placeholder}
            value={value}
            onChange={onChange}
            disabled={disabled}
            rows={rows}
            {...props}
        />
    );
};