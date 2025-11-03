import React, { useState, useEffect } from 'react';
import { IconTrendingUp, IconTrendingDown, IconMinus } from '@tabler/icons-react';
import { useCurrency } from '@/hooks/useCurrency';

export default function KPIWidget({ widget, dashboardId, isEditing }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const { formatAmount } = useCurrency();

    useEffect(() => {
        if (!isEditing && dashboardId && widget.id) {
            fetchData();
        } else {
            // Show empty state in editing mode - no hardcoded data
            setData({
                value: 0,
                previousValue: 0,
                change: 0,
                trend: 'neutral'
            });
            setLoading(false);
        }
    }, [widget, dashboardId, isEditing]);

    const fetchData = async () => {
        try {
            setLoading(true);
            const response = await fetch(`/api/dashboards/${dashboardId}/widgets/${widget.id}/data`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`Failed to fetch widget data: ${response.status} ${response.statusText}`);
            }
            
            const result = await response.json();
            if (result.success) {
                setData(result.data);
            } else {
                throw new Error(result.message || 'Failed to load widget data');
            }
        } catch (err) {
            console.error('Widget data fetch error:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const formatValue = (value) => {
        // Get format from configuration (backend stores it directly in configuration)
        const format = widget.configuration.format || widget.configuration.chart_options?.format;
        const precision = widget.configuration.precision || 2;
        
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        
        let formattedValue = value;
        
        switch (format) {
            case 'currency':
                formattedValue = formatAmount(value);
                break;
            case 'percentage':
                // For percentages, value is already a decimal (0.05 = 5%)
                formattedValue = `${(value * 100).toFixed(precision)}%`;
                break;
            case 'number':
            default:
                if (typeof value === 'number') {
                    formattedValue = value.toLocaleString('en-US', {
                        minimumFractionDigits: precision,
                        maximumFractionDigits: precision
                    });
                } else {
                    formattedValue = value.toString();
                }
        }
        
        return formattedValue;
    };

    const getTrendIcon = (trend) => {
        switch (trend) {
            case 'up':
                return <IconTrendingUp size={20} className="text-success" />;
            case 'down':
                return <IconTrendingDown size={20} className="text-danger" />;
            default:
                return <IconMinus size={20} className="text-muted" />;
        }
    };

    const getTrendColor = (change) => {
        if (change > 0) return 'text-success';
        if (change < 0) return 'text-danger';
        return 'text-muted';
    };

    if (loading) {
        return (
            <div className="card h-100">
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="card h-100">
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="text-center text-muted">
                        <div className="mb-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="icon icon-lg"
                                width="48"
                                height="48"
                                viewBox="0 0 24 24"
                                strokeWidth="1"
                                stroke="currentColor"
                                fill="none"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="12" cy="12" r="9" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                        <div>Error loading data</div>
                        <small>{error}</small>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="card h-100">
            <div className="card-body">
                <div className="d-flex align-items-center justify-content-between mb-2">
                    <h3 className="card-title mb-0">
                        {widget.title}
                    </h3>
                    {data?.trend && getTrendIcon(data.trend)}
                </div>
                
                <div className="d-flex align-items-baseline">
                    <div className="h1 mb-0 me-2">
                        {formatValue(data?.value)}
                    </div>
                    {data?.change !== undefined && (
                        <div className={`${getTrendColor(data.change)} fw-bold`}>
                            {data.change > 0 ? '+' : ''}{data.change.toFixed(2)}%
                        </div>
                    )}
                </div>
                
                {data?.previousValue !== undefined && (
                    <div className="text-muted small mt-1">
                        Previous: {formatValue(data.previousValue)}
                    </div>
                )}
                
                {isEditing && (
                    <div className="mt-2">
                        <span className="badge bg-secondary-lt">
                            No Data Available
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}