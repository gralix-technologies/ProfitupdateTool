import React, { useEffect, useState } from 'react';
import { IconCheck, IconX, IconAlertTriangle, IconInfoCircle } from '@tabler/icons-react';

const Toast = ({ 
    id, 
    type = 'success', 
    title, 
    message, 
    duration = 5000, 
    onClose 
}) => {
    const [isVisible, setIsVisible] = useState(true);
    const [isLeaving, setIsLeaving] = useState(false);

    useEffect(() => {
        if (duration > 0) {
            const timer = setTimeout(() => {
                handleClose();
            }, duration);

            return () => clearTimeout(timer);
        }
    }, [duration]);

    const handleClose = () => {
        setIsLeaving(true);
        setTimeout(() => {
            setIsVisible(false);
            onClose(id);
        }, 300);
    };

    if (!isVisible) return null;

    const getToastStyles = () => {
        const baseStyles = "relative flex items-start p-4 mb-3 rounded-lg shadow-lg border-l-4 transition-all duration-300 ease-in-out transform";
        const visibilityStyles = isLeaving ? "opacity-0 translate-x-full" : "opacity-100 translate-x-0";
        
        switch (type) {
            case 'success':
                return `${baseStyles} ${visibilityStyles} bg-green-50 border-green-400 text-green-800`;
            case 'error':
                return `${baseStyles} ${visibilityStyles} bg-red-50 border-red-400 text-red-800`;
            case 'warning':
                return `${baseStyles} ${visibilityStyles} bg-yellow-50 border-yellow-400 text-yellow-800`;
            case 'info':
                return `${baseStyles} ${visibilityStyles} bg-blue-50 border-blue-400 text-blue-800`;
            default:
                return `${baseStyles} ${visibilityStyles} bg-gray-50 border-gray-400 text-gray-800`;
        }
    };

    const getIcon = () => {
        const iconClass = "flex-shrink-0 w-5 h-5 mt-0.5";
        switch (type) {
            case 'success':
                return <IconCheck className={`${iconClass} text-green-500`} />;
            case 'error':
                return <IconX className={`${iconClass} text-red-500`} />;
            case 'warning':
                return <IconAlertTriangle className={`${iconClass} text-yellow-500`} />;
            case 'info':
                return <IconInfoCircle className={`${iconClass} text-blue-500`} />;
            default:
                return <IconInfoCircle className={`${iconClass} text-gray-500`} />;
        }
    };

    return (
        <div className={getToastStyles()}>
            <div className="flex">
                {getIcon()}
                <div className="ml-3 flex-1">
                    {title && (
                        <h4 className="text-sm font-medium mb-1">
                            {title}
                        </h4>
                    )}
                    <p className="text-sm">
                        {message}
                    </p>
                </div>
                <button
                    onClick={handleClose}
                    className="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                >
                    <IconX className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
};

export default Toast;
