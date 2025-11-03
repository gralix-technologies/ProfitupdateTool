import React from 'react';
import Toast from './Toast';

const ToastContainer = ({ toasts, onRemoveToast }) => {
    return (
        <div className="fixed top-4 right-4 z-50 w-96 max-w-sm">
            {toasts.map((toast) => (
                <Toast
                    key={toast.id}
                    {...toast}
                    onClose={onRemoveToast}
                />
            ))}
        </div>
    );
};

export default ToastContainer;
