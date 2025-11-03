import React, { useState, useEffect } from 'react';
import axios from 'axios';

const ImportHistory = ({ onSelectImport }) => {
    const [imports, setImports] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchImports();
    }, []);

    const fetchImports = async () => {
        try {
            setIsLoading(true);
            const response = await axios.get('/api/data/imports');
            if (response.data.success) {
                setImports(response.data.imports);
                setError(null);
            }
        } catch (err) {
            const errorMessage = err.response?.data?.message || 'Failed to fetch import history';
            setError(errorMessage);
        } finally {
            setIsLoading(false);
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
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
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
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l5 5l10 -10"/>
                    </svg>
                );
            case 'failed':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M18 6l-12 12"/>
                        <path d="M6 6l12 12"/>
                    </svg>
                );
            case 'cancelled':
                return (
                    <svg xmlns="http://www.w3.org/2000/svg" className="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
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
                <div className="card-header">
                    <h3 className="card-title">Import History</h3>
                </div>
                <div className="card-body text-center">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                    <div className="mt-2">Loading import history...</div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="card">
                <div className="card-header">
                    <h3 className="card-title">Import History</h3>
                </div>
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
                    <button className="btn btn-outline-primary" onClick={fetchImports}>
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="card">
            <div className="card-header">
                <h3 className="card-title">Import History</h3>
                <div className="card-actions">
                    <button className="btn btn-outline-primary btn-sm" onClick={fetchImports}>
                        <svg xmlns="http://www.w3.org/2000/svg" className="icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
            <div className="card-body">
                {imports.length === 0 ? (
                    <div className="text-center text-muted py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" className="icon icon-lg mb-2" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                        </svg>
                        <div>No imports found</div>
                        <div className="small">Upload your first CSV file to get started</div>
                    </div>
                ) : (
                    <div className="list-group list-group-flush">
                        {imports.map((importItem) => (
                            <div
                                key={importItem.import_id}
                                className="list-group-item list-group-item-action cursor-pointer"
                                onClick={() => onSelectImport?.(importItem.import_id)}
                            >
                                <div className="row align-items-center">
                                    <div className="col-auto">
                                        <span className={`badge bg-${getStatusColor(importItem.status)}`}>
                                            {getStatusIcon(importItem.status)}
                                        </span>
                                    </div>
                                    <div className="col">
                                        <div className="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div className="font-weight-medium">
                                                    Import #{importItem.import_id.slice(0, 8)}...
                                                </div>
                                                <div className="text-muted small">
                                                    Product ID: {importItem.product_id}
                                                </div>
                                                <div className="text-muted small">
                                                    {new Date(importItem.updated_at).toLocaleString()}
                                                </div>
                                            </div>
                                            <div className="text-end">
                                                <div className={`badge bg-${getStatusColor(importItem.status)}`}>
                                                    {importItem.status.toUpperCase()}
                                                </div>
                                                {importItem.percent > 0 && (
                                                    <div className="small text-muted mt-1">
                                                        {Math.round(importItem.percent)}%
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        <div className="text-muted small mt-1">
                                            {importItem.message}
                                        </div>
                                        {importItem.data?.total_rows && (
                                            <div className="small text-muted">
                                                Rows: {importItem.data.total_rows.toLocaleString()}
                                                {importItem.data.error_count > 0 && (
                                                    <span className="text-danger">
                                                        {' '}({importItem.data.error_count} errors)
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default ImportHistory;