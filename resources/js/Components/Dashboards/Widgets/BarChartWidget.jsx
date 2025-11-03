import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export default function BarChartWidget({ widget, dashboardId, isEditing }) {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const {
        xAxisLabel = '',
        yAxisLabel = '',
        showLegend = true,
        showGrid = true
    } = widget.configuration.chart_options || {};

    useEffect(() => {
        if (!isEditing && dashboardId && widget.id) {
            fetchData();
        } else {
            // Show empty state in editing mode - no hardcoded data
            setData([]);
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
                const rawData = result.data?.data || result.data || [];
                // Ensure we always have an array and validate data
                const validatedData = Array.isArray(rawData) ? rawData : [];
                setData(validatedData);
            } else {
                throw new Error(result.message || 'Failed to load widget data');
            }
        } catch (err) {
            console.error('Widget data fetch error:', err);
            setError(err.message);
            setData([]); // Ensure data is always an array
        } finally {
            setLoading(false);
        }
    };

    const formatTooltipValue = (value, name) => {
        return [new Intl.NumberFormat('en-US').format(value), name];
    };

    const formatAxisValue = (value) => {
        if (typeof value === 'number') {
            if (value >= 1000000) {
                return `${(value / 1000000).toFixed(1)}M`;
            } else if (value >= 1000) {
                return `${(value / 1000).toFixed(1)}K`;
            }
        }
        return value;
    };

    if (loading) {
        return (
            <div className="card h-100">
                <div className="card-header">
                    <h3 className="card-title">{widget.configuration.title}</h3>
                </div>
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
                <div className="card-header">
                    <h3 className="card-title">{widget.configuration.title}</h3>
                </div>
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="text-center text-muted">
                        <div>Error loading data</div>
                        <small>{error}</small>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="card h-100">
            <div className="card-header">
                <div className="d-flex align-items-center justify-content-between">
                    <h3 className="card-title mb-0">
                        {widget.configuration.title}
                    </h3>
                    {isEditing && (
                        <span className="badge bg-secondary-lt">
                            No Data Available
                        </span>
                    )}
                </div>
            </div>
            <div className="card-body" style={{ padding: '20px', minHeight: '350px' }}>
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart
                        data={data}
                        margin={{
                            top: 20,
                            right: 30,
                            left: 20,
                            bottom: 5,
                        }}
                    >
                        {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#e9ecef" />}
                        <XAxis 
                            dataKey="name" 
                            tick={{ fontSize: 12 }}
                            label={xAxisLabel ? { value: xAxisLabel, position: 'insideBottom', offset: -5 } : undefined}
                        />
                        <YAxis 
                            tick={{ fontSize: 12 }}
                            tickFormatter={formatAxisValue}
                            label={yAxisLabel ? { value: yAxisLabel, angle: -90, position: 'insideLeft' } : undefined}
                        />
                        <Tooltip 
                            formatter={formatTooltipValue}
                            contentStyle={{
                                backgroundColor: '#fff',
                                border: '1px solid #dee2e6',
                                borderRadius: '4px',
                                fontSize: '12px'
                            }}
                        />
                        {showLegend && <Legend />}
                        <Bar 
                            dataKey="value" 
                            fill="#222551" 
                            name="Actual"
                            radius={[2, 2, 0, 0]}
                        />
                        {Array.isArray(data) && data.length > 0 && data.some(item => item.target !== undefined) && (
                            <Bar 
                                dataKey="target" 
                                fill="#66a3ff" 
                                name="Target"
                                radius={[2, 2, 0, 0]}
                            />
                        )}
                    </BarChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}