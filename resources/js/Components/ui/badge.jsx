import React from 'react';

export const Badge = ({ 
    children, 
    variant = 'primary', 
    className = '', 
    ...props 
}) => {
    const baseClasses = 'badge';
    const variantClasses = {
        primary: 'bg-primary',
        secondary: 'bg-secondary',
        success: 'bg-success',
        danger: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };

    const classes = [
        baseClasses,
        variantClasses[variant] || variantClasses.primary,
        className
    ].filter(Boolean).join(' ');

    return (
        <span className={classes} {...props}>
            {children}
        </span>
    );
};