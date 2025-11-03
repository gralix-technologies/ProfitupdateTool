import React from 'react';

export const Switch = React.forwardRef(({ className = '', checked, onCheckedChange, disabled, ...props }, ref) => {
    return (
        <label className={`form-check form-switch ${className}`}>
            <input
                ref={ref}
                type="checkbox"
                className="form-check-input"
                checked={checked}
                onChange={(e) => onCheckedChange?.(e.target.checked)}
                disabled={disabled}
                {...props}
            />
        </label>
    );
});

Switch.displayName = 'Switch';