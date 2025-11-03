import React from 'react';

export const Label = ({ 
    children, 
    htmlFor,
    className = '', 
    ...props 
}) => {
    return (
        <label 
            htmlFor={htmlFor}
            className={`form-label ${className}`} 
            {...props}
        >
            {children}
        </label>
    );
};