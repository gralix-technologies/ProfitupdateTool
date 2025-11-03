import React, { useState, useEffect } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';

const SimpleShow = ({ dashboard }) => {
    const [kpiData, setKpiData] = useState({});
    const [chartData, setChartData] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadDashboardData();
    }, []);

    const loadDashboardData = async () => {
        try {
            setLoading(true);
            setError(null);

            // Load dashboard data with all widgets
            const response = await axios.get(`/api/dashboards/${dashboard.id}/data`);
            
            if (!response.data.success) {
                throw new Error(response.data.message || 'Failed to load dashboard data');
            }

            const { dashboard: dashboardData, widget_data } = response.data.data;
            
            // Process widget data into KPI and chart data
            const kpiDataMap = {};
            const chartDataMap = {};
            
            dashboardData.widgets.forEach(widget => {
                const widgetData = widget_data[widget.id];
                
                if (widgetData && !widgetData.error) {
                    if (widget.type === 'KPI') {
                        // Map KPI widgets to our display format
                        const kpiKey = getKpiKeyFromWidget(widget);
                        if (kpiKey) {
                            kpiDataMap[kpiKey] = {
                                value: widgetData.value,
                                format: widgetData.format,
                                color: widgetData.color
                            };
                        }
                    } else if (widget.type === 'PieChart' || widget.type === 'BarChart') {
                        // Map chart widgets to our display format
                        const chartKey = getChartKeyFromWidget(widget);
                        if (chartKey && widgetData.data) {
                            chartDataMap[chartKey] = widgetData.data;
                        }
                    }
                }
            });
            
            setKpiData(kpiDataMap);
            setChartData(chartDataMap);
            
        } catch (err) {
            console.error('Error loading dashboard data:', err);
            setError('Failed to load dashboard data: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    // Helper function to map widget to KPI key
    const getKpiKeyFromWidget = (widget) => {
        const config = widget.configuration || {};
        const metric = config.metric || '';
        
        if (metric.includes('SUM(outstanding_balance)') && !metric.includes('days_past_due')) {
            return 'total_portfolio';
        } else if (metric.includes('days_past_due >= 90')) {
            return 'npl_ratio';
        } else if (metric.includes('pd * lgd * ead')) {
            return 'expected_loss';
        } else if (metric.includes('interest_rate_annual')) {
            return 'avg_interest_rate';
        }
        return null;
    };

    // Helper function to map widget to chart key
    const getChartKeyFromWidget = (widget) => {
        const config = widget.configuration || {};
        
        if (widget.type === 'PieChart' && config.group_by === 'sector') {
            return 'sector_pie';
        } else if (widget.type === 'BarChart' && config.x_axis === 'credit_rating') {
            return 'rating_bar';
        }
        return null;
    };

    const formatValue = (value, format) => {
        if (!value && value !== 0) return 'N/A';
        
        switch (format) {
            case 'currency':
                return 'ZMW' + new Intl.NumberFormat('en-US', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                }).format(value);
            case 'percentage':
                return value.toFixed(2) + '%';
            case 'number':
                return new Intl.NumberFormat('en-US').format(value);
            default:
                return new Intl.NumberFormat('en-US').format(value);
        }
    };

    const getKpiColor = (type) => {
        const colors = {
            total_portfolio: 'primary',
            npl_ratio: 'danger',
            expected_loss: 'warning',
            avg_interest_rate: 'success'
        };
        return colors[type] || 'primary';
    };

    const getKpiTitle = (type) => {
        const titles = {
            total_portfolio: 'Total Portfolio Value',
            npl_ratio: 'NPL Ratio (PAR90+)',
            expected_loss: 'Expected Loss',
            avg_interest_rate: 'Avg Interest Rate'
        };
        return titles[type] || type;
    };

    if (loading) {
        return (
            <AppLayout>
                <Head title={`Dashboard - ${dashboard.name}`} />
                <div className="page-header">
                    <div className="container-xl">
                        <div className="row g-2 align-items-center">
                            <div className="col">
                                <h2 className="page-title">{dashboard.name}</h2>
                                <div className="text-muted mt-1">{dashboard.description}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="page-body">
                    <div className="container-xl">
                        <div className="text-center py-5">
                            <div className="spinner-border" role="status">
                                <span className="visually-hidden">Loading...</span>
                            </div>
                            <div className="mt-2">Loading dashboard data...</div>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    if (error) {
        return (
            <AppLayout>
                <Head title={`Dashboard - ${dashboard.name}`} />
                <div className="page-header">
                    <div className="container-xl">
                        <div className="row g-2 align-items-center">
                            <div className="col">
                                <h2 className="page-title">{dashboard.name}</h2>
                                <div className="text-muted mt-1">{dashboard.description}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="page-body">
                    <div className="container-xl">
                        <div className="alert alert-danger">
                            <h4>Error Loading Dashboard</h4>
                            <p>{error}</p>
                            <button 
                                className="btn btn-outline-danger"
                                onClick={loadDashboardData}
                            >
                                Retry
                            </button>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`Dashboard - ${dashboard.name}`} />
            
            <div className="page-header">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <h2 className="page-title">{dashboard.name}</h2>
                            <div className="text-muted mt-1">{dashboard.description}</div>
                        </div>
                        <div className="col-auto">
                            <button 
                                className="btn btn-outline-primary"
                                onClick={loadDashboardData}
                            >
                                <svg className="icon icon-tabler me-1" width="24" height="24" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="m0 0h24v24H0z" fill="none"></path>
                                    <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path>
                                    <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path>
                                </svg>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    {/* KPI Cards */}
                    <div className="row row-deck row-cards mb-4">
                        {Object.entries(kpiData).map(([type, data]) => (
                            <div key={type} className="col-sm-6 col-lg-3">
                                <div className="card">
                                    <div className="card-body">
                                        <div className="d-flex align-items-center">
                                            <div className="subheader">{getKpiTitle(type)}</div>
                                        </div>
                                        <div className="h1 mb-3">
                                            <span className={`text-${getKpiColor(type)}`}>
                                                {formatValue(data.value, data.format)}
                                            </span>
                                        </div>
                                        <div className="d-flex mb-2">
                                            <div className="flex-fill">
                                                <div className="progress progress-sm">
                                                    <div 
                                                        className={`progress-bar bg-${getKpiColor(type)}`} 
                                                        style={{ width: `${Math.min(100, Math.max(0, (data.value / (data.target || data.value * 1.2)) * 100))}%` }}
                                                    ></div>
                                                </div>
                                            </div>
                                            <span className="text-muted ms-2">
                                                {Math.min(100, Math.max(0, (data.value / (data.target || data.value * 1.2)) * 100)).toFixed(1)}%
                                            </span>
                                        </div>
                                        <div className="text-muted">
                                            {data.target ? `Target: ${formatValue(data.target, data.format)}` : 'Real-time data'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Charts */}
                    <div className="row row-deck row-cards">
                        {/* Sector Distribution */}
                        {chartData.sector_pie && (
                            <div className="col-md-6">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">Exposure by Sector</h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="table-responsive">
                                            <table className="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Sector</th>
                                                        <th className="text-end">Amount</th>
                                                        <th className="text-end">%</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {chartData.sector_pie.map((item, index) => {
                                                        const total = chartData.sector_pie.reduce((sum, i) => sum + i.value, 0);
                                                        const percentage = ((item.value / total) * 100).toFixed(1);
                                                        return (
                                                            <tr key={index}>
                                                                <td>{item.label}</td>
                                                                <td className="text-end">
                                                                    ZMW{new Intl.NumberFormat().format(item.value)}
                                                                </td>
                                                                <td className="text-end">{percentage}%</td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Credit Rating Distribution */}
                        {chartData.rating_bar && (
                            <div className="col-md-6">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">Credit Rating Distribution</h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="table-responsive">
                                            <table className="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Rating</th>
                                                        <th className="text-end">Count</th>
                                                        <th className="text-end">%</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {chartData.rating_bar.map((item, index) => {
                                                        const total = chartData.rating_bar.reduce((sum, i) => sum + i.value, 0);
                                                        const percentage = ((item.value / total) * 100).toFixed(1);
                                                        return (
                                                            <tr key={index}>
                                                                <td>
                                                                    <span className="badge bg-primary">{item.label}</span>
                                                                </td>
                                                                <td className="text-end">{item.value}</td>
                                                                <td className="text-end">{percentage}%</td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
};

export default SimpleShow;