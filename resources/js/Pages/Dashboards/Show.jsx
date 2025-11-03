import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { IconArrowLeft, IconEdit, IconDownload } from '@tabler/icons-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DashboardBuilder from '@/Components/Dashboards/DashboardBuilder';
import DashboardFilters from '@/Components/Dashboards/DashboardFilters';
import ExportModal from '@/Components/Dashboards/ExportModal';

export default function Show() {
    const { dashboard } = usePage().props;
    const [currentFilters, setCurrentFilters] = useState({});
    const [filterOptions, setFilterOptions] = useState({});
    const [isLoadingFilters, setIsLoadingFilters] = useState(false);
    const [showExportModal, setShowExportModal] = useState(false);
    const [filteredWidgetData, setFilteredWidgetData] = useState({});

    useEffect(() => {
        fetchFilterOptions();
    }, []);

    const fetchFilterOptions = async () => {
        try {
            const response = await fetch(`/api/dashboards/${dashboard.id}/filter-options`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                setFilterOptions(data.data || {});
            } else {
                console.error('Failed to fetch filter options:', response.status, response.statusText);
            }
        } catch (error) {
            console.error('Failed to fetch filter options:', error);
        }
    };

    const handleFiltersChange = (filters) => {
        setCurrentFilters(filters);
    };

    const handleApplyFilters = async (filters) => {
        setIsLoadingFilters(true);
        
        try {
            const response = await fetch(`/api/dashboards/${dashboard.id}/filters`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ filters })
            });
            
            if (response.ok) {
                const data = await response.json();
                setFilteredWidgetData(data.data || {});
                setCurrentFilters(data.applied_filters || filters);
            } else {
                console.error('Failed to apply filters:', response.status, response.statusText);
            }
        } catch (error) {
            console.error('Error applying filters:', error);
        } finally {
            setIsLoadingFilters(false);
        }
    };

    const handleExport = () => {
        setShowExportModal(true);
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Dashboard - ${dashboard.name}`} />
            
            <div className="page-wrapper">
                <div className="page-header d-print-none">
                    <div className="container-xl">
                        <div className="row g-2 align-items-center">
                            <div className="col">
                                <div className="page-pretitle">
                                    <Link href="/dashboards" className="text-muted">
                                        <IconArrowLeft size={16} className="me-1" />
                                        Back to Dashboards
                                    </Link>
                                </div>
                                <h2 className="page-title">{dashboard.name}</h2>
                                <div className="text-muted mt-1">
                                    {dashboard.widgets?.length || 0} widgets • 
                                    Created {new Date(dashboard.created_at).toLocaleDateString()}
                                </div>
                            </div>
                            <div className="col-auto ms-auto d-print-none">
                                <div className="btn-list">
                                    <button
                                        type="button"
                                        className="btn btn-outline-primary"
                                        onClick={handleExport}
                                    >
                                        <IconDownload size={16} className="me-1" />
                                        Export
                                    </button>
                                    <Link
                                        href={`/dashboards/${dashboard.id}/edit`}
                                        className="btn btn-primary"
                                    >
                                        <IconEdit size={16} className="me-1" />
                                        Edit Dashboard
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="page-body">
                    <div className="container-xl">
                        {dashboard.widgets && dashboard.widgets.length > 0 ? (
                            <>
                                <DashboardFilters
                                    filters={currentFilters}
                                    onFiltersChange={handleFiltersChange}
                                    onApplyFilters={handleApplyFilters}
                                    filterOptions={filterOptions}
                                    isLoading={isLoadingFilters}
                                    className="mb-4"
                                />
                                
                                <DashboardBuilder
                                    widgets={dashboard.widgets}
                                    onWidgetsChange={() => {}} // Read-only mode
                                    isEditing={false}
                                    dashboardId={dashboard.id}
                                    filteredData={filteredWidgetData}
                                    appliedFilters={currentFilters}
                                />
                            </>
                        ) : (
                            <div className="empty">
                                <div className="empty-img">
                                    <img
                                        src="/static/illustrations/undraw_printing_invoices_5r4r.svg"
                                        height="128"
                                        alt=""
                                    />
                                </div>
                                <p className="empty-title">No widgets in this dashboard</p>
                                <p className="empty-subtitle text-muted">
                                    This dashboard doesn't have any widgets yet. Edit the dashboard to add some visualizations.
                                </p>
                                <div className="empty-action">
                                    <Link
                                        href={`/dashboards/${dashboard.id}/edit`}
                                        className="btn btn-primary"
                                    >
                                        <IconEdit size={16} className="me-1" />
                                        Edit Dashboard
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
                
                <ExportModal
                    isOpen={showExportModal}
                    onClose={() => setShowExportModal(false)}
                    dashboardId={dashboard.id}
                    dashboardName={dashboard.name}
                    currentFilters={currentFilters}
                />
            </div>
        </AuthenticatedLayout>
    );
}