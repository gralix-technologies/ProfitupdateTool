import React from 'react';

export const Button = ({ 
    children, 
    variant = 'primary', 
    size = 'md', 
    disabled = false, 
    className = '', 
    onClick,
    type = 'button',
    ...props 
}) => {
    const baseClasses = 'btn';
    const variantClasses = {
        primary: 'btn-primary',
        secondary: 'btn-secondary',
        outline: 'btn-outline-primary',
        ghost: 'btn-ghost',
        danger: 'btn-danger'
    };
    const sizeClasses = {
        sm: 'btn-sm',
        md: '',
        lg: 'btn-lg'
    };

    const classes = [
        baseClasses,
        variantClasses[variant] || variantClasses.primary,
        sizeClasses[size],
        className
    ].filter(Boolean).join(' ');

    return (
        <button
            type={type}
            className={classes}
            disabled={disabled}
            onClick={onClick}
            {...props}
        >
            {children}
        </button>
    );
};