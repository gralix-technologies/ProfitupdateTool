import React, { useState, useEffect } from 'react';

export default function HeatmapWidget({ widget, dashboardId, isEditing }) {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

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
            const response = await fetch(`/api/dashboards/${dashboardId}/widgets/${widget.id}/data`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch widget data');
            }
            
            const result = await response.json();
            if (result.success && result.data) {
                // Handle the correct data structure from the API
                const apiData = result.data.data || result.data || [];
                // Ensure we always have an array
                setData(Array.isArray(apiData) ? apiData : []);
            } else {
                setData([]);
            }
        } catch (err) {
            console.error('Widget data fetch error:', err);
            setError(err.message);
            setData([]); // Ensure data is always an array
        } finally {
            setLoading(false);
        }
    };

    const getUniqueValues = (key) => {
        if (!Array.isArray(data) || data.length === 0) {
            return [];
        }
        return [...new Set(data.map(item => item[key]))];
    };

    const getValueForCell = (x, y) => {
        if (!Array.isArray(data) || data.length === 0) {
            return 0;
        }
        const item = data.find(d => d.x === x && d.y === y);
        return item ? item.value : 0;
    };

    const getMinMaxValues = () => {
        if (!Array.isArray(data) || data.length === 0) {
            return { min: 0, max: 0 };
        }
        const values = data.map(d => d.value).filter(v => typeof v === 'number' && !isNaN(v));
        if (values.length === 0) {
            return { min: 0, max: 0 };
        }
        return {
            min: Math.min(...values),
            max: Math.max(...values)
        };
    };

    const getColorIntensity = (value) => {
        const { min, max } = getMinMaxValues();
        if (max === min) {
            return 0.5; // Default intensity when all values are the same
        }
        const normalized = (value - min) / (max - min);
        return Math.max(0.1, Math.min(1, normalized)); // Clamp between 0.1 and 1
    };

    const formatValue = (value) => {
        if (value >= 1000000) {
            return `${(value / 1000000).toFixed(1)}M`;
        } else if (value >= 1000) {
            return `${(value / 1000).toFixed(1)}K`;
        }
        return new Intl.NumberFormat('en-US').format(value);
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

    const xValues = getUniqueValues('x');
    const yValues = getUniqueValues('y');
    const { min, max } = getMinMaxValues();

    // Show empty state if no data
    if (!Array.isArray(data) || data.length === 0) {
        return (
            <div className="card h-100">
                <div className="card-header">
                    <h3 className="card-title">{widget.title}</h3>
                </div>
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="text-center text-muted">
                        <div>No data available</div>
                        <small>This widget requires data to display the heatmap</small>
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
            <div className="card-body">
                <div className="heatmap-container" style={{ height: '100%', overflow: 'auto' }}>
                    <table className="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                {xValues.map(x => (
                                    <th key={x} className="text-center">{x}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {yValues.map(y => (
                                <tr key={y}>
                                    <td className="fw-bold">{y}</td>
                                    {xValues.map(x => {
                                        const value = getValueForCell(x, y);
                                        const intensity = getColorIntensity(value);
                                        return (
                                            <td
                                                key={`${x}-${y}`}
                                                className="text-center position-relative"
                                                style={{
                                                    backgroundColor: `rgba(32, 107, 196, ${intensity})`,
                                                    color: intensity > 0.5 ? 'white' : 'black',
                                                    minWidth: '80px',
                                                    height: '40px',
                                                    verticalAlign: 'middle'
                                                }}
                                                title={`${y} - ${x}: ${formatValue(value)}`}
                                            >
                                                <div className="fw-bold">
                                                    {formatValue(value)}
                                                </div>
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                
                {/* Legend */}
                <div className="mt-3 d-flex align-items-center justify-content-center">
                    <div className="d-flex align-items-center">
                        <span className="small text-muted me-2">Low</span>
                        <div className="d-flex">
                            {Array.from({ length: 5 }, (_, i) => (
                                <div
                                    key={i}
                                    style={{
                                        width: '20px',
                                        height: '10px',
                                        backgroundColor: `rgba(32, 107, 196, ${0.2 + (i * 0.2)})`,
                                        marginRight: '1px'
                                    }}
                                />
                            ))}
                        </div>
                        <span className="small text-muted ms-2">High</span>
                    </div>
                    <div className="ms-4 small text-muted">
                        Range: {formatValue(min)} - {formatValue(max)}
                    </div>
                </div>
            </div>
        </div>
    );
}