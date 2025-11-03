import React, { useState, useEffect } from 'react';
import axios from 'axios';

const ProgressTracker = ({ importId, onComplete, onError }) => {
    const [progress, setProgress] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!importId) return;

        const fetchProgress = async () => {
            try {
                const response = await axios.get(`/api/data/progress/${importId}`);
                if (response.data.success) {
                    setProgress(response.data.progress);
                    setError(null);

                    // Check if import is complete
                    if (response.data.progress.status === 'completed') {
                        onComplete?.(response.data.progress);
                    } else if (response.data.progress.status === 'failed') {
                        onError?.(response.data.progress.message);
                    }
                }
            } catch (err) {
                const errorMessage = err.response?.data?.message || 'Failed to fetch progress';
                setError(errorMessage);
                onError?.(errorMessage);
            } finally {
                setIsLoading(false);
            }
        };

        // Initial fetch
        fetchProgress();

        // Set up polling for active imports
        let intervalId = null;
        if (progress?.status === 'processing' || progress?.status === 'queued') {
            intervalId = setInterval(() => {
                fetchProgress();
            }, 2000); // Poll every 2 seconds
        }

        return () => {
            if (intervalId) {
                clearInterval(intervalId);
            }
        };
    }, [importId, progress?.status]);

    const handleCancel = async () => {
        try {
            const response = await axios.post(`/api/data/cancel/${importId}`);
            if (response.data.success) {
                setProgress(prev => ({
                    ...prev,
                    status: 'cancelled',
                    message: 'Import cancelled by user'
                }));
            }
        } catch (err) {
            const errorMessage = err.response?.data?.message || 'Failed to cancel import';
            setError(errorMessage);
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'queued': return 'info';
            case 'processing': return 'primary';
            case 'completed': return 'success';
            case 'failed': return 'danger';
            case 'cancelled': return 'warning';
            default: return 'secondary';
        }
    };

    const getStatusIcon = (status) => {
        switch (status) {
            case 'queued':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="9"/>
                        <polyline points="12,7 12,12 15,15"/>
                    </svg>
                );
            case 'processing':
                return (
                    <div className="spinner-border spinner-border-sm" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                );
            case 'completed':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l5 5l10 -10"/>
                    </svg>
                );
            case 'failed':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M18 6l-12 12"/>
                        <path d="M6 6l12 12"/>
                    </svg>
                );
            case 'cancelled':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M9 12l6 0"/>
                    </svg>
                );
            default:
                return null;
        }
    };

    if (isLoading) {
        return (
            <div className="card">
                <div className="card-body text-center">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                    <div className="mt-2">Loading import progress...</div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="card">
                <div className="card-body">
                    <div className="alert alert-danger">
                        <div className="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" className="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            </div>
                            <div>
                                <h4 className="alert-title">Error</h4>
                                <div className="text-muted">{error}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (!progress) {
        return (
            <div className="card">
                <div className="card-body text-center text-muted">
                    No import progress found
                </div>
            </div>
        );
    }

    return (
        <div className="card">
            <div className="card-header">
                <h3 className="card-title">Import Progress</h3>
                <div className="card-actions">
                    <span className={`badge bg-${getStatusColor(progress.status)}`}>
                        {progress.status.toUpperCase()}
                    </span>
                </div>
            </div>
            <div className="card-body">
                {/* Progress Bar */}
                <div className="mb-3">
                    <div className="row align-items-center">
                        <div className="col">
                            <div className="progress">
                                <div
                                    className={`progress-bar bg-${getStatusColor(progress.status)}`}
                                    role="progressbar"
                                    style={{ width: `${progress.percent}%` }}
                                    aria-valuenow={progress.percent}
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    {progress.percent > 10 && `${Math.round(progress.percent)}%`}
                                </div>
                            </div>
                        </div>
                        <div className="col-auto">
                            <div className={`text-${getStatusColor(progress.status)}`}>
                                {getStatusIcon(progress.status)}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Status Message */}
                <div className="mb-3">
                    <div className="text-muted">{progress.message}</div>
                    <div className="small text-muted">
                        Last updated: {new Date(progress.updated_at).toLocaleString()}
                    </div>
                </div>

                {/* Import Details */}
                {progress.data && Object.keys(progress.data).length > 0 && (
                    <div className="mb-3">
                        <h4 className="card-title">Import Details</h4>
                        <div className="row">
                            {progress.data.total_rows && (
                                <div className="col-sm-6">
                                    <div className="mb-2">
                                        <strong>Total Rows:</strong> {progress.data.total_rows.toLocaleString()}
                                    </div>
                                </div>
                            )}
                            {progress.data.error_count !== undefined && (
                                <div className="col-sm-6">
                                    <div className="mb-2">
                                        <strong>Errors:</strong> 
                                        <span className={progress.data.error_count > 0 ? 'text-danger' : 'text-success'}>
                                            {progress.data.error_count.toLocaleString()}
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Error Details */}
                {progress.data?.errors && progress.data.errors.length > 0 && (
                    <div className="mb-3">
                        <h4 className="card-title">Import Errors</h4>
                        <div className="alert alert-warning">
                            <div className="small">
                                <strong>First 10 errors:</strong>
                                <ul className="mb-0 mt-1">
                                    {progress.data.errors.slice(0, 10).map((error, index) => (
                                        <li key={index}>{error}</li>
                                    ))}
                                </ul>
                                {progress.data.errors.length > 10 && (
                                    <div className="mt-1">
                                        <em>... and {progress.data.errors.length - 10} more errors</em>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Actions */}
                <div className="d-flex justify-content-end">
                    {(progress.status === 'processing' || progress.status === 'queued') && (
                        <button
                            type="button"
                            className="btn btn-outline-danger"
                            onClick={handleCancel}
                        >
                            Cancel Import
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ProgressTracker;