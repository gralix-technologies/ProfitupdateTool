import React, { useState, useEffect } from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend } from 'recharts';

const COLORS = ['#206bc4', '#79a6dc', '#a8cc8c', '#ffa94d', '#fd7e14', '#e74c3c', '#9b59b6', '#1abc9c'];

export default function PieChartWidget({ widget, dashboardId, isEditing }) {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const {
        showLabels = true,
        showPercentages = true
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
                // Normalize data format and handle blanks
                const normalizedData = rawData
                    .map(item => ({
                        name: item.name || item.label || 'Unknown',
                        value: item.value || 0
                    }))
                    .filter(item => {
                        // Filter out items with zero/negative values and empty names
                        return item.value > 0 && item.name && item.name.trim() !== '';
                    })
                    .map(item => ({
                        ...item,
                        name: item.name.trim() || 'Unspecified'
                    }));
                setData(normalizedData);
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

    const formatTooltipValue = (value, name) => {
        if (!Array.isArray(data) || data.length === 0) {
            return [value, name];
        }
        
        const total = data.reduce((sum, item) => sum + (item.value || 0), 0);
        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
        return [
            `ZMW${new Intl.NumberFormat('en-US').format(value)} (${percentage}%)`,
            name
        ];
    };

    const renderCustomLabel = (entry) => {
        if (!showLabels || !Array.isArray(data) || data.length === 0) return null;
        
        const total = data.reduce((sum, item) => sum + (item.value || 0), 0);
        const percentage = total > 0 ? ((entry.value / total) * 100).toFixed(1) : 0;
        
        if (showPercentages) {
            return `${entry.name}: ${percentage}%`;
        }
        return entry.name;
    };

    if (loading) {
        return (
            <div className="card h-100">
                <div className="card-header">
                    <h3 className="card-title">{widget.title}</h3>
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
                    <h3 className="card-title">{widget.title}</h3>
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

    // Handle empty data state
    if (!data || data.length === 0) {
        return (
            <div className="card h-100">
                <div className="card-header">
                    <h3 className="card-title">{widget.title}</h3>
                </div>
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="text-center text-muted">
                        <div>No data available</div>
                        <small>This widget will show data when available</small>
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
                        {widget.title}
                    </h3>
                    {isEditing && (
                        <span className="badge bg-secondary-lt">
                            No Data Available
                        </span>
                    )}
                </div>
            </div>
            <div className="card-body" style={{ padding: '20px', minHeight: '400px', display: 'flex', flexDirection: 'column' }}>
                <ResponsiveContainer width="100%" height="100%">
            <PieChart margin={{ top: 20, right: 20, bottom: 80, left: 20 }}>
                <Pie
                    data={data}
                    cx="50%"
                    cy="45%"
                    labelLine={false}
                    label={showLabels ? renderCustomLabel : false}
                    outerRadius={100}
                    innerRadius={20}
                    fill="#8884d8"
                    dataKey="value"
                >
                    {data.map((entry, index) => (
                        <Cell 
                            key={`cell-${index}`} 
                            fill={COLORS[index % COLORS.length]} 
                        />
                    ))}
                </Pie>
                <Tooltip 
                    formatter={formatTooltipValue}
                    contentStyle={{
                        backgroundColor: '#fff',
                        border: '1px solid #dee2e6',
                        borderRadius: '4px',
                        fontSize: '12px'
                    }}
                />
                <Legend 
                    verticalAlign="bottom" 
                    height={80}
                    iconType="circle"
                    formatter={(value, entry) => {
                        if (!Array.isArray(data) || data.length === 0) return value;
                        const total = data.reduce((sum, item) => sum + (item.value || 0), 0);
                        const percentage = total > 0 ? ((entry.value / total) * 100).toFixed(1) : 0;
                        return `${value} (${percentage}%)`;
                    }}
                    wrapperStyle={{
                        paddingTop: '30px',
                        fontSize: '13px',
                        fontWeight: '500',
                        lineHeight: '1.6'
                    }}
                />
            </PieChart>
        </ResponsiveContainer>
    </div>
        </div>
    );
}