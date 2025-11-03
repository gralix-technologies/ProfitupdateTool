import React, { useState, useRef, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FileUpload from '@/Components/DataIngestion/FileUpload';
import ProgressTracker from '@/Components/DataIngestion/ProgressTracker';
import ImportHistory from '@/Components/DataIngestion/ImportHistory';
import { IconUpload, IconHistory, IconDatabase } from '@tabler/icons-react';

export default function DataIngestionIndex({ auth, products }) {
    const [activeImportId, setActiveImportId] = useState(null);
    const [notification, setNotification] = useState(null);

    // Use useRef to store timeout IDs for cleanup
    const timeoutRefs = useRef([]);

    const clearAllTimeouts = () => {
        timeoutRefs.current.forEach(timeoutId => {
            if (timeoutId) clearTimeout(timeoutId);
        });
        timeoutRefs.current = [];
    };

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            clearAllTimeouts();
        };
    }, []);

    const handleUploadStart = (uploadData) => {
        setActiveImportId(uploadData.import_id);
        setNotification({
            type: 'success',
            message: 'File upload started successfully! You can monitor the progress below.'
        });
        
        // Clear notification after 5 seconds
        const timeoutId = setTimeout(() => setNotification(null), 5000);
        timeoutRefs.current.push(timeoutId);
    };

    const handleUploadError = (errorMessage) => {
        setNotification({
            type: 'error',
            message: errorMessage
        });
        
        // Clear notification after 10 seconds
        const timeoutId = setTimeout(() => setNotification(null), 10000);
        timeoutRefs.current.push(timeoutId);
    };

    const handleImportComplete = (progressData) => {
        setNotification({
            type: 'success',
            message: `Import completed successfully! Processed ${progressData.data?.total_rows || 0} rows.`
        });
        
        // Clear notification after 10 seconds
        const timeoutId = setTimeout(() => setNotification(null), 10000);
        timeoutRefs.current.push(timeoutId);
    };

    const handleImportError = (errorMessage) => {
        setNotification({
            type: 'error',
            message: `Import failed: ${errorMessage}`
        });
        
        // Clear notification after 10 seconds
        const timeoutId = setTimeout(() => setNotification(null), 10000);
        timeoutRefs.current.push(timeoutId);
    };

    const handleSelectImport = (importId) => {
        setActiveImportId(importId);
    };

    const clearNotification = () => {
        setNotification(null);
    };

    return (
        <AppLayout title="Data Ingestion">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Data Management
                            </div>
                            <h2 className="page-title">
                                Data Ingestion
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    {/* Notification */}
                    {notification && (
                        <div className="row">
                            <div className="col-12">
                                <div className={`alert alert-${notification.type === 'error' ? 'danger' : 'success'} alert-dismissible`}>
                                    <div className="d-flex">
                                        <div>
                                            {notification.type === 'error' ? (
                                                <svg xmlns="http://www.w3.org/2000/svg" className="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <circle cx="12" cy="12" r="9"/>
                                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                                </svg>
                                            ) : (
                                                <svg xmlns="http://www.w3.org/2000/svg" className="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M5 12l5 5l10 -10"/>
                                                </svg>
                                            )}
                                        </div>
                                        <div>
                                            {notification.message}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        className="btn-close"
                                        onClick={clearNotification}
                                        aria-label="Close"
                                    ></button>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="row row-deck row-cards">
                        {/* Left Column - File Upload */}
                        <div className="col-lg-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconUpload size={18} className="me-2" />
                                        Upload Data
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <FileUpload
                                        products={products}
                                        onUploadStart={handleUploadStart}
                                        onUploadComplete={handleImportComplete}
                                        onUploadError={handleUploadError}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Right Column - Progress Tracker */}
                        <div className="col-lg-6">
                            {activeImportId ? (
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <IconDatabase size={18} className="me-2" />
                                            Import Progress
                                        </h3>
                                    </div>
                                    <div className="card-body">
                                        <ProgressTracker
                                            importId={activeImportId}
                                            onComplete={handleImportComplete}
                                            onError={handleImportError}
                                        />
                                    </div>
                                </div>
                            ) : (
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <IconDatabase size={18} className="me-2" />
                                            Import Progress
                                        </h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="empty">
                                            <div className="empty-img">
                                                <IconUpload size={48} className="text-muted" />
                                            </div>
                                            <p className="empty-title">Upload a file to see progress</p>
                                            <p className="empty-subtitle text-muted">
                                                Select a product and CSV file to get started
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Import History */}
                    <div className="row row-deck row-cards">
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconHistory size={18} className="me-2" />
                                        Import History
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <ImportHistory onSelectImport={handleSelectImport} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}