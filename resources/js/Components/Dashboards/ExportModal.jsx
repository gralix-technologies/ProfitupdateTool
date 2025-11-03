import React, { useState, useEffect } from 'react';
import { 
    IconDownload, 
    IconX, 
    IconFileTypePdf, 
    IconFileTypeCsv,
    IconCheck,
    IconAlertCircle
} from '@tabler/icons-react';
import { router } from '@inertiajs/react';

export default function ExportModal({ 
    isOpen, 
    onClose, 
    dashboardId, 
    dashboardName,
    currentFilters = {},
    className = '' 
}) {
    const [selectedFormat, setSelectedFormat] = useState('pdf');
    const [isExporting, setIsExporting] = useState(false);
    const [exportFormats, setExportFormats] = useState({});
    const [includeFilters, setIncludeFilters] = useState(true);
    const [exportStatus, setExportStatus] = useState(null);

    useEffect(() => {
        if (isOpen) {
            fetchExportFormats();
            setExportStatus(null);
        }
    }, [isOpen]);

    const fetchExportFormats = async () => {
        try {
            const response = await fetch('/api/dashboards/export/formats', {
                headers: {
                    'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')}`,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                setExportFormats(data.data || {});
            }
        } catch (error) {
            console.error('Failed to fetch export formats:', error);
        }
    };

    const handleExport = async () => {
        if (!dashboardId || !selectedFormat) return;

        setIsExporting(true);
        setExportStatus(null);

        try {
            const params = new URLSearchParams();
            
            if (includeFilters && currentFilters) {
                params.append('filters', JSON.stringify(currentFilters));
            }

            const url = `/api/dashboards/${dashboardId}/export/${selectedFormat}?${params.toString()}`;
            
            // Create a temporary link to trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = `${dashboardName}_export.${selectedFormat}`;
            
            // Add authorization header by using fetch first
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')}`,
                    'Accept': selectedFormat === 'pdf' ? 'application/pdf' : 'text/csv'
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const downloadUrl = window.URL.createObjectURL(blob);
                
                link.href = downloadUrl;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                window.URL.revokeObjectURL(downloadUrl);
                
                setExportStatus({
                    type: 'success',
                    message: `Dashboard exported successfully as ${selectedFormat.toUpperCase()}`
                });
                
                setTimeout(() => {
                    onClose();
                }, 2000);
            } else {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Export failed');
            }
        } catch (error) {
            console.error('Export failed:', error);
            setExportStatus({
                type: 'error',
                message: error.message || 'Failed to export dashboard'
            });
        } finally {
            setIsExporting(false);
        }
    };

    const hasActiveFilters = () => {
        return Object.entries(currentFilters || {}).some(([key, value]) => {
            if (key === 'date_range') {
                return value?.start || value?.end;
            }
            return value && value !== '';
        });
    };

    const getActiveFiltersCount = () => {
        let count = 0;
        Object.entries(currentFilters || {}).forEach(([key, value]) => {
            if (key === 'date_range') {
                if (value?.start || value?.end) count++;
            } else if (value && value !== '') {
                count++;
            }
        });
        return count;
    };

    if (!isOpen) return null;

    const handleBackdropClick = (e) => {
        if (e.target === e.currentTarget && !isExporting) {
            onClose();
        }
    };

    return (
        <div 
            className={`modal modal-blur fade show ${className}`} 
            style={{ display: 'block', zIndex: 1050 }}
            onClick={handleBackdropClick}
        >
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                    <div className="modal-header">
                        <h5 className="modal-title">
                            <IconDownload size={20} className="me-2" />
                            Export Dashboard
                        </h5>
                        <button
                            type="button"
                            className="btn-close"
                            onClick={onClose}
                            disabled={isExporting}
                            aria-label="Close"
                        />
                    </div>
                    
                    <div className="modal-body">
                        <div className="mb-3">
                            <h6 className="mb-2">Dashboard: {dashboardName}</h6>
                            <p className="text-muted small mb-0">
                                Choose your preferred export format and options below.
                            </p>
                        </div>

                        {/* Export Format Selection */}
                        <div className="mb-4">
                            <label className="form-label">Export Format</label>
                            <div className="row g-2">
                                {Object.entries(exportFormats).map(([format, info]) => (
                                    <div key={format} className="col-6">
                                        <label className="form-selectgroup-item">
                                            <input
                                                type="radio"
                                                name="export-format"
                                                value={format}
                                                className="form-selectgroup-input"
                                                checked={selectedFormat === format}
                                                onChange={(e) => setSelectedFormat(e.target.value)}
                                                disabled={isExporting}
                                            />
                                            <span className="form-selectgroup-label d-flex align-items-center p-3">
                                                {format === 'pdf' ? (
                                                    <IconFileTypePdf size={24} className="me-2 text-danger" />
                                                ) : (
                                                    <IconFileTypeCsv size={24} className="me-2 text-success" />
                                                )}
                                                <div>
                                                    <div className="fw-bold">{info.name}</div>
                                                    <div className="text-muted small">{info.description}</div>
                                                </div>
                                            </span>
                                        </label>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Filter Options */}
                        <div className="mb-4">
                            <label className="form-label">Filter Options</label>
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    id="include-filters"
                                    checked={includeFilters}
                                    onChange={(e) => setIncludeFilters(e.target.checked)}
                                    disabled={isExporting}
                                />
                                <label className="form-check-label" htmlFor="include-filters">
                                    Apply current filters to export
                                    {hasActiveFilters() && (
                                        <span className="badge bg-primary ms-2">
                                            {getActiveFiltersCount()} active
                                        </span>
                                    )}
                                </label>
                            </div>
                            
                            {includeFilters && hasActiveFilters() && (
                                <div className="mt-2 p-2 bg-light rounded">
                                    <small className="text-muted">Active filters will be applied:</small>
                                    <div className="d-flex flex-wrap gap-1 mt-1">
                                        {Object.entries(currentFilters).map(([key, value]) => {
                                            if (key === 'date_range' && (value?.start || value?.end)) {
                                                return (
                                                    <span key={key} className="badge bg-secondary">
                                                        Date: {value.start || 'Start'} - {value.end || 'End'}
                                                    </span>
                                                );
                                            } else if (value && value !== '' && key !== 'date_range') {
                                                return (
                                                    <span key={key} className="badge bg-secondary">
                                                        {key.replace('_', ' ')}: {value}
                                                    </span>
                                                );
                                            }
                                            return null;
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Export Status */}
                        {exportStatus && (
                            <div className={`alert ${exportStatus.type === 'success' ? 'alert-success' : 'alert-danger'} d-flex align-items-center`}>
                                {exportStatus.type === 'success' ? (
                                    <IconCheck size={20} className="me-2" />
                                ) : (
                                    <IconAlertCircle size={20} className="me-2" />
                                )}
                                {exportStatus.message}
                            </div>
                        )}

                        {/* Format-specific Information */}
                        {selectedFormat === 'pdf' && (
                            <div className="alert alert-info">
                                <strong>PDF Export:</strong> Charts will be displayed as static images with data tables. 
                                Interactive features will not be available in the PDF.
                            </div>
                        )}
                        
                        {selectedFormat === 'csv' && (
                            <div className="alert alert-info">
                                <strong>CSV Export:</strong> Raw data from all widgets will be exported in separate sheets. 
                                Chart visualizations will be converted to tabular data.
                            </div>
                        )}
                    </div>
                    
                    <div className="modal-footer">
                        <button
                            type="button"
                            className="btn btn-secondary"
                            onClick={onClose}
                            disabled={isExporting}
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={handleExport}
                            disabled={isExporting || !selectedFormat}
                        >
                            {isExporting ? (
                                <>
                                    <div className="spinner-border spinner-border-sm me-2" role="status">
                                        <span className="visually-hidden">Loading...</span>
                                    </div>
                                    Exporting...
                                </>
                            ) : (
                                <>
                                    <IconDownload size={16} className="me-1" />
                                    Export {selectedFormat?.toUpperCase()}
                                </>
                            )}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}