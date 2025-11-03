import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { IconArrowLeft, IconDownload, IconRefresh, IconFilter, IconTable, IconChartBar, IconChartPie, IconTrendingUp } from '@tabler/icons-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DashboardFilters from '@/Components/Dashboards/DashboardFilters';
import ExportModal from '@/Components/Dashboards/ExportModal';
import KPIWidget from '@/Components/Dashboards/Widgets/KPIWidget';
import TableWidget from '@/Components/Dashboards/Widgets/TableWidget';
import BarChartWidget from '@/Components/Dashboards/Widgets/BarChartWidget';
import PieChartWidget from '@/Components/Dashboards/Widgets/PieChartWidget';
import LineChartWidget from '@/Components/Dashboards/Widgets/LineChartWidget';
import HeatmapWidget from '@/Components/Dashboards/Widgets/HeatmapWidget';

export default function ConsolidatedShow() {
    const [allDashboards, setAllDashboards] = useState([]);
    const [allWidgets, setAllWidgets] = useState([]);
    const [currentFilters, setCurrentFilters] = useState({});
    const [filterOptions, setFilterOptions] = useState({});
    const [isLoadingFilters, setIsLoadingFilters] = useState(false);
    const [showExportModal, setShowExportModal] = useState(false);
    const [filteredWidgetData, setFilteredWidgetData] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchAllDashboards();
        fetchFilterOptions();
    }, []);

    const fetchAllDashboards = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/dashboards', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                setAllDashboards(data.data || []);
                
                // Collect all widgets from all dashboards
                const widgets = [];
                data.data?.forEach(dashboard => {
                    if (dashboard.widgets) {
                        dashboard.widgets.forEach(widget => {
                            widgets.push({
                                ...widget,
                                dashboard_id: dashboard.id,
                                dashboard_name: dashboard.name
                            });
                        });
                    }
                });
                setAllWidgets(widgets);
            } else {
                setError('Failed to fetch dashboards');
            }
        } catch (err) {
            setError('Error loading dashboards: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    const fetchFilterOptions = async () => {
        try {
            const response = await fetch('/api/dashboards/1/filter-options', {
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
            // Apply filters to all dashboards
            const filteredData = {};
            for (const dashboard of allDashboards) {
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
                    Object.assign(filteredData, data.data || {});
                }
            }
            
            setFilteredWidgetData(filteredData);
            setCurrentFilters(filters);
        } catch (error) {
            console.error('Error applying filters:', error);
        } finally {
            setIsLoadingFilters(false);
        }
    };

    const handleExport = () => {
        setShowExportModal(true);
    };

    const handleRefresh = () => {
        fetchAllDashboards();
    };

    const renderWidget = (widget) => {
        const widgetData = filteredWidgetData[widget.id];
        const commonProps = {
            widget,
            dashboardId: widget.dashboard_id,
            isEditing: false,
            filteredData: widgetData
        };

        switch (widget.type) {
            case 'KPI':
                return <KPIWidget key={widget.id} {...commonProps} />;
            case 'Table':
                return <TableWidget key={widget.id} {...commonProps} />;
            case 'BarChart':
                return <BarChartWidget key={widget.id} {...commonProps} />;
            case 'PieChart':
                return <PieChartWidget key={widget.id} {...commonProps} />;
            case 'LineChart':
                return <LineChartWidget key={widget.id} {...commonProps} />;
            case 'Heatmap':
                return <HeatmapWidget key={widget.id} {...commonProps} />;
            default:
                return null;
        }
    };

    const getWidgetsByType = (type) => {
        return allWidgets.filter(widget => widget.type === type);
    };

    const getWidgetsByDashboard = (dashboardId) => {
        return allWidgets.filter(widget => widget.dashboard_id === dashboardId);
    };

    if (loading) {
        return (
            <AuthenticatedLayout>
                <Head title="Consolidated Dashboard" />
                <div className="page-wrapper">
                    <div className="page-body">
                        <div className="container-xl">
                            <div className="d-flex align-items-center justify-content-center" style={{ minHeight: '400px' }}>
                                <div className="spinner-border text-primary" role="status">
                                    <span className="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    if (error) {
        return (
            <AuthenticatedLayout>
                <Head title="Consolidated Dashboard" />
                <div className="page-wrapper">
                    <div className="page-body">
                        <div className="container-xl">
                            <div className="alert alert-danger">
                                <h4>Error Loading Dashboard</h4>
                                <p>{error}</p>
                                <button className="btn btn-primary" onClick={handleRefresh}>
                                    <IconRefresh size={16} className="me-1" />
                                    Retry
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Consolidated Portfolio Analytics Dashboard" />
            
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
                                <h2 className="page-title">Consolidated Portfolio Analytics Dashboard</h2>
                                <div className="text-muted mt-1">
                                    {allWidgets.length} widgets from {allDashboards.length} dashboards • 
                                    Comprehensive portfolio overview
                                </div>
                            </div>
                            <div className="col-auto ms-auto d-print-none">
                                <div className="btn-list">
                                    <button
                                        type="button"
                                        className="btn btn-outline-secondary"
                                        onClick={handleRefresh}
                                        title="Refresh Data"
                                    >
                                        <IconRefresh size={16} className="me-1" />
                                        Refresh
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-outline-primary"
                                        onClick={handleExport}
                                    >
                                        <IconDownload size={16} className="me-1" />
                                        Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="page-body">
                    <div className="container-xl">
                        {/* Filters */}
                        <DashboardFilters
                            filters={currentFilters}
                            onFiltersChange={handleFiltersChange}
                            onApplyFilters={handleApplyFilters}
                            filterOptions={filterOptions}
                            isLoading={isLoadingFilters}
                            className="mb-4"
                        />

                        {/* Key Performance Indicators Section */}
                        <div className="row mb-4">
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <IconTrendingUp size={20} className="me-2" />
                                            Key Performance Indicators
                                        </h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="row g-3">
                                            {getWidgetsByType('KPI').map(widget => (
                                                <div key={widget.id} className="col-xl-3 col-lg-4 col-md-6">
                                                    {renderWidget(widget)}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Charts Section */}
                        <div className="row mb-4">
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <IconChartBar size={20} className="me-2" />
                                            Portfolio Analytics & Visualizations
                                        </h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="row g-3">
                                            {/* Bar Charts */}
                                            {getWidgetsByType('BarChart').map(widget => (
                                                <div key={widget.id} className="col-xl-6 col-lg-12">
                                                    {renderWidget(widget)}
                                                </div>
                                            ))}
                                            
                                            {/* Pie Charts */}
                                            {getWidgetsByType('PieChart').map(widget => (
                                                <div key={widget.id} className="col-xl-6 col-lg-12">
                                                    {renderWidget(widget)}
                                                </div>
                                            ))}
                                            
                                            {/* Line Charts */}
                                            {getWidgetsByType('LineChart').map(widget => (
                                                <div key={widget.id} className="col-xl-6 col-lg-12">
                                                    {renderWidget(widget)}
                                                </div>
                                            ))}
                                            
                                            {/* Heatmaps */}
                                            {getWidgetsByType('Heatmap').map(widget => (
                                                <div key={widget.id} className="col-xl-12">
                                                    {renderWidget(widget)}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Tables Section */}
                        <div className="row">
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <IconTable size={20} className="me-2" />
                                            Detailed Data Tables
                                        </h3>
                                        <div className="card-subtitle text-muted">
                                            Comprehensive data analysis and detailed breakdowns
                                        </div>
                                    </div>
                                    <div className="card-body">
                                        <div className="row g-4">
                                            {getWidgetsByType('Table').map(widget => (
                                                <div key={widget.id} className="col-12">
                                                    <div className="mb-4">
                                                        <h5 className="text-muted mb-3">
                                                            {widget.title}
                                                            <span className="badge bg-primary-lt ms-2">
                                                                {widget.dashboard_name}
                                                            </span>
                                                        </h5>
                                                        {renderWidget(widget)}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <ExportModal
                    isOpen={showExportModal}
                    onClose={() => setShowExportModal(false)}
                    dashboardId="consolidated"
                    dashboardName="Consolidated Portfolio Analytics Dashboard"
                    currentFilters={currentFilters}
                />
            </div>
        </AuthenticatedLayout>
    );
}
