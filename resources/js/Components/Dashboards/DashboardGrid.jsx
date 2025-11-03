import React, { useState, useCallback } from 'react';
import { useDrop } from 'react-dnd';
import DashboardWidget from './DashboardWidget';

const GRID_COLS = 12;
const GRID_ROW_HEIGHT = 120; // Increased from 60px to 120px for better chart visibility
const GRID_MARGIN = [10, 10];

export default function DashboardGrid({
    widgets = [],
    isEditing = false,
    onWidgetEdit,
    onWidgetDelete,
    onWidgetMove,
    onWidgetResize,
    dashboardId,
    filteredData = {},
    appliedFilters = {}
}) {
    const [draggedWidget, setDraggedWidget] = useState(null);

    const [{ isOver }, drop] = useDrop({
        accept: 'widget',
        drop: (item, monitor) => {
            if (!monitor.didDrop()) {
                const delta = monitor.getDifferenceFromInitialOffset();
                const gridX = Math.round(delta.x / (100 / GRID_COLS));
                const gridY = Math.round(delta.y / GRID_ROW_HEIGHT);
                
                const newPosition = {
                    ...item.position,
                    x: Math.max(0, Math.min(GRID_COLS - item.position.w, item.position.x + gridX)),
                    y: Math.max(0, item.position.y + gridY)
                };

                if (onWidgetMove) {
                    onWidgetMove(item.id, newPosition);
                }
            }
        },
        collect: (monitor) => ({
            isOver: monitor.isOver()
        })
    });

    const calculateGridHeight = useCallback(() => {
        if (widgets.length === 0) return 400;
        
        const maxY = Math.max(...widgets.map(w => w.position.y + w.position.h));
        return Math.max(400, (maxY + 2) * (GRID_ROW_HEIGHT + GRID_MARGIN[1]));
    }, [widgets]);

    const getWidgetStyle = useCallback((widget) => {
        const { x, y, w, h } = widget.position;
        const colWidth = 100 / GRID_COLS;
        
        return {
            position: 'absolute',
            left: `${x * colWidth}%`,
            top: `${y * (GRID_ROW_HEIGHT + GRID_MARGIN[1])}px`,
            width: `calc(${w * colWidth}% - ${GRID_MARGIN[0]}px)`,
            height: `${h * GRID_ROW_HEIGHT + (h - 1) * GRID_MARGIN[1]}px`,
            zIndex: draggedWidget === widget.id ? 1000 : 1
        };
    }, [draggedWidget]);

    const handleWidgetDragStart = useCallback((widgetId) => {
        setDraggedWidget(widgetId);
    }, []);

    const handleWidgetDragEnd = useCallback(() => {
        setDraggedWidget(null);
    }, []);

    const handleWidgetResize = useCallback((widgetId, direction, delta) => {
        const widget = widgets.find(w => w.id === widgetId);
        if (!widget || !onWidgetResize) return;

        const gridDeltaX = Math.round(delta.x / (100 / GRID_COLS));
        const gridDeltaY = Math.round(delta.y / GRID_ROW_HEIGHT);

        let newSize = { ...widget.position };

        switch (direction) {
            case 'se': // Southeast (bottom-right)
                newSize.w = Math.max(1, Math.min(GRID_COLS - newSize.x, newSize.w + gridDeltaX));
                newSize.h = Math.max(1, newSize.h + gridDeltaY);
                break;
            case 'sw': // Southwest (bottom-left)
                const newW = Math.max(1, newSize.w - gridDeltaX);
                const newX = Math.max(0, newSize.x + newSize.w - newW);
                newSize.x = newX;
                newSize.w = newW;
                newSize.h = Math.max(1, newSize.h + gridDeltaY);
                break;
            case 'ne': // Northeast (top-right)
                newSize.w = Math.max(1, Math.min(GRID_COLS - newSize.x, newSize.w + gridDeltaX));
                const newH = Math.max(1, newSize.h - gridDeltaY);
                const newY = Math.max(0, newSize.y + newSize.h - newH);
                newSize.y = newY;
                newSize.h = newH;
                break;
            case 'nw': // Northwest (top-left)
                const newWNW = Math.max(1, newSize.w - gridDeltaX);
                const newXNW = Math.max(0, newSize.x + newSize.w - newWNW);
                const newHNW = Math.max(1, newSize.h - gridDeltaY);
                const newYNW = Math.max(0, newSize.y + newSize.h - newHNW);
                newSize.x = newXNW;
                newSize.y = newYNW;
                newSize.w = newWNW;
                newSize.h = newHNW;
                break;
        }

        onWidgetResize(widgetId, newSize);
    }, [widgets, onWidgetResize]);

    return (
        <div
            ref={drop}
            className={`dashboard-grid ${isOver ? 'drag-over' : ''}`}
            style={{
                position: 'relative',
                height: `${calculateGridHeight()}px`,
                backgroundColor: isOver ? '#f8f9fa' : 'transparent',
                border: isEditing ? '2px dashed #dee2e6' : 'none',
                borderRadius: '8px',
                minHeight: '400px'
            }}
        >
            {widgets.length === 0 && isEditing && (
                <div className="empty-grid-message">
                    <div className="text-center text-muted py-5">
                        <div className="mb-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="icon icon-lg text-muted"
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
                                <rect x="4" y="4" width="6" height="6" rx="1" />
                                <rect x="14" y="4" width="6" height="6" rx="1" />
                                <rect x="4" y="14" width="6" height="6" rx="1" />
                                <rect x="14" y="14" width="6" height="6" rx="1" />
                            </svg>
                        </div>
                        <h3>Start building your dashboard</h3>
                        <p>Click "Add Widget" to add your first visualization</p>
                    </div>
                </div>
            )}

            {/* Organize widgets by type: KPIs first, then charts, then tables at bottom */}
            {(() => {
                // Sort widgets by type: KPI -> BarChart -> PieChart -> LineChart -> Heatmap -> Table
                const typeOrder = ['KPI', 'BarChart', 'PieChart', 'LineChart', 'Heatmap', 'Table'];
                const sortedWidgets = [...widgets].sort((a, b) => {
                    const aIndex = typeOrder.indexOf(a.type);
                    const bIndex = typeOrder.indexOf(b.type);
                    return aIndex - bIndex;
                });

                return sortedWidgets.map((widget, index) => {
                    // Calculate position based on type and index
                    let position = { ...widget.position };
                    
                    if (widget.type === 'KPI') {
                        // KPIs in a grid at the top
                        const kpiIndex = sortedWidgets.filter(w => w.type === 'KPI').indexOf(widget);
                        position = {
                            x: (kpiIndex % 4) * 3, // 4 KPIs per row, each 3 columns wide
                            y: Math.floor(kpiIndex / 4) * 2, // 2 rows high
                            w: 3,
                            h: 2
                        };
                    } else if (widget.type === 'Table') {
                        // Tables at the bottom, full width
                        const tableIndex = sortedWidgets.filter(w => w.type === 'Table').indexOf(widget);
                        const kpiRows = Math.ceil(sortedWidgets.filter(w => w.type === 'KPI').length / 4) * 2;
                        const chartRows = Math.ceil(sortedWidgets.filter(w => ['BarChart', 'PieChart', 'LineChart', 'Heatmap'].includes(w.type)).length / 2) * 4;
                        position = {
                            x: 0,
                            y: kpiRows + chartRows + (tableIndex * 6), // 6 rows per table
                            w: 12,
                            h: 6
                        };
                    } else {
                        // Charts in the middle, 2 per row
                        const chartIndex = sortedWidgets.filter(w => ['BarChart', 'PieChart', 'LineChart', 'Heatmap'].includes(w.type)).indexOf(widget);
                        const kpiRows = Math.ceil(sortedWidgets.filter(w => w.type === 'KPI').length / 4) * 2;
                        position = {
                            x: (chartIndex % 2) * 6, // 2 charts per row, each 6 columns wide
                            y: kpiRows + Math.floor(chartIndex / 2) * 4, // 4 rows high
                            w: 6,
                            h: 4
                        };
                    }

                    return (
                        <div
                            key={widget.id}
                            style={getWidgetStyle({ ...widget, position })}
                            className="dashboard-widget-container"
                        >
                            <DashboardWidget
                                widget={widget}
                                isEditing={isEditing}
                                onEdit={() => onWidgetEdit && onWidgetEdit(widget)}
                                onDelete={() => onWidgetDelete && onWidgetDelete(widget.id)}
                                onDragStart={() => handleWidgetDragStart(widget.id)}
                                onDragEnd={handleWidgetDragEnd}
                                onResize={(direction, delta) => handleWidgetResize(widget.id, direction, delta)}
                                dashboardId={dashboardId}
                                filteredData={filteredData[widget.id]}
                                appliedFilters={appliedFilters}
                            />
                        </div>
                    );
                });
            })()}

            {/* Grid lines for editing mode */}
            {isEditing && (
                <div className="grid-lines">
                    {Array.from({ length: GRID_COLS + 1 }, (_, i) => (
                        <div
                            key={`col-${i}`}
                            className="grid-line-vertical"
                            style={{
                                position: 'absolute',
                                left: `${(i * 100) / GRID_COLS}%`,
                                top: 0,
                                bottom: 0,
                                width: '1px',
                                backgroundColor: '#e9ecef',
                                opacity: 0.5
                            }}
                        />
                    ))}
                    {Array.from({ length: Math.ceil(calculateGridHeight() / (GRID_ROW_HEIGHT + GRID_MARGIN[1])) }, (_, i) => (
                        <div
                            key={`row-${i}`}
                            className="grid-line-horizontal"
                            style={{
                                position: 'absolute',
                                top: `${i * (GRID_ROW_HEIGHT + GRID_MARGIN[1])}px`,
                                left: 0,
                                right: 0,
                                height: '1px',
                                backgroundColor: '#e9ecef',
                                opacity: 0.5
                            }}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}