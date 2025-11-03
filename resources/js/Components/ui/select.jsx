import React from 'react';

export const Select = ({ children, value, onValueChange, ...props }) => {
    return (
        <select 
            className="form-select" 
            value={value} 
            onChange={(e) => onValueChange && onValueChange(e.target.value)}
            {...props}
        >
            {children}
        </select>
    );
};

export const SelectContent = ({ children }) => {
    return <>{children}</>;
};

export const SelectItem = ({ value, children }) => {
    return <option value={value}>{children}</option>;
};

export const SelectTrigger = ({ children, className = '' }) => {
    return <div className={className}>{children}</div>;
};

export const SelectValue = ({ placeholder }) => {
    return <option value="" disabled>{placeholder}</option>;
};