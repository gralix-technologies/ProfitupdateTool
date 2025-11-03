import React from 'react';

export const Input = ({ 
    type = 'text', 
    className = '', 
    placeholder = '',
    value,
    onChange,
    disabled = false,
    ...props 
}) => {
    return (
        <input
            type={type}
            className={`form-control ${className}`}
            placeholder={placeholder}
            value={value}
            onChange={onChange}
            disabled={disabled}
            {...props}
        />
    );
};