import React from 'react';

export const Alert = ({ 
    children, 
    variant = 'info', 
    className = '', 
    ...props 
}) => {
    const variantClasses = {
        info: 'alert-info',
        success: 'alert-success',
        warning: 'alert-warning',
        danger: 'alert-danger'
    };

    const classes = [
        'alert',
        variantClasses[variant] || variantClasses.info,
        className
    ].filter(Boolean).join(' ');

    return (
        <div className={classes} {...props}>
            {children}
        </div>
    );
};

export const AlertDescription = ({ children, className = '' }) => {
    return (
        <div className={className}>
            {children}
        </div>
    );
};