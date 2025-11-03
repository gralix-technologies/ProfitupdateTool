import React, { useState, useRef, useEffect } from 'react';
import { useDrag } from 'react-dnd';
import { IconEdit, IconTrash, IconGripVertical, IconMaximize } from '@tabler/icons-react';
import KPIWidget from './Widgets/KPIWidget';
import TableWidget from './Widgets/TableWidget';
import PieChartWidget from './Widgets/PieChartWidget';
import BarChartWidget from './Widgets/BarChartWidget';
import LineChartWidget from './Widgets/LineChartWidget';
import HeatmapWidget from './Widgets/HeatmapWidget';

const WIDGET_COMPONENTS = {
    KPI: KPIWidget,
    Table: TableWidget,
    PieChart: PieChartWidget,
    BarChart: BarChartWidget,
    LineChart: LineChartWidget,
    Heatmap: HeatmapWidget
};

export default function DashboardWidget({
    widget,
    isEditing = false,
    onEdit,
    onDelete,
    onDragStart,
    onDragEnd,
    onResize,
    dashboardId,
    filteredData = null,
    appliedFilters = {}
}) {
    const [isResizing, setIsResizing] = useState(false);
    const [resizeDirection, setResizeDirection] = useState(null);
    const [startPos, setStartPos] = useState({ x: 0, y: 0 });
    const widgetRef = useRef(null);

    const [{ isDragging }, drag, preview] = useDrag({
        type: 'widget',
        item: () => {
            onDragStart && onDragStart();
            return {
                id: widget.id,
                position: widget.position
            };
        },
        end: () => {
            onDragEnd && onDragEnd();
        },
        collect: (monitor) => ({
            isDragging: monitor.isDragging()
        }),
        canDrag: isEditing
    });

    const WidgetComponent = WIDGET_COMPONENTS[widget.type];

    const handleResizeStart = (direction, e) => {
        if (!isEditing) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        setIsResizing(true);
        setResizeDirection(direction);
        setStartPos({ x: e.clientX, y: e.clientY });
        
        document.addEventListener('mousemove', handleResizeMove);
        document.addEventListener('mouseup', handleResizeEnd);
    };

    const handleResizeMove = (e) => {
        if (!isResizing || !resizeDirection) return;
        
        const deltaX = e.clientX - startPos.x;
        const deltaY = e.clientY - startPos.y;
        
        onResize && onResize(resizeDirection, { x: deltaX, y: deltaY });
    };

    const handleResizeEnd = () => {
        setIsResizing(false);
        setResizeDirection(null);
        
        document.removeEventListener('mousemove', handleResizeMove);
        document.removeEventListener('mouseup', handleResizeEnd);
    };

    useEffect(() => {
        return () => {
            document.removeEventListener('mousemove', handleResizeMove);
            document.removeEventListener('mouseup', handleResizeEnd);
        };
    }, []);

    if (!WidgetComponent) {
        return (
            <div className="card h-100">
                <div className="card-body d-flex align-items-center justify-content-center">
                    <div className="text-muted">
                        Unknown widget type: {widget.type}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div
            ref={(node) => {
                widgetRef.current = node;
                if (isEditing) {
                    drag(node);
                }
                preview(node);
            }}
            className={`dashboard-widget ${isDragging ? 'dragging' : ''} ${isResizing ? 'resizing' : ''}`}
            style={{
                opacity: isDragging ? 0.5 : 1,
                cursor: isEditing && !isResizing ? 'move' : 'default',
                height: '100%',
                position: 'relative'
            }}
        >
            {/* Widget Header (only in editing mode) */}
            {isEditing && (
                <div className="widget-header">
                    <div className="widget-controls">
                        <button
                            type="button"
                            className="btn btn-sm btn-ghost-secondary"
                            onClick={onEdit}
                            title="Edit Widget"
                        >
                            <IconEdit size={14} />
                        </button>
                        <button
                            type="button"
                            className="btn btn-sm btn-ghost-danger"
                            onClick={onDelete}
                            title="Delete Widget"
                        >
                            <IconTrash size={14} />
                        </button>
                        <div className="drag-handle" title="Drag to move">
                            <IconGripVertical size={14} />
                        </div>
                    </div>
                </div>
            )}

            {/* Widget Content */}
            <div className="widget-content h-100">
                <WidgetComponent
                    widget={widget}
                    dashboardId={dashboardId}
                    isEditing={isEditing}
                    filteredData={filteredData}
                    appliedFilters={appliedFilters}
                />
            </div>

            {/* Resize Handles (only in editing mode) */}
            {isEditing && (
                <>
                    <div
                        className="resize-handle resize-se"
                        onMouseDown={(e) => handleResizeStart('se', e)}
                        title="Resize"
                    >
                        <IconMaximize size={12} />
                    </div>
                    <div
                        className="resize-handle resize-sw"
                        onMouseDown={(e) => handleResizeStart('sw', e)}
                    />
                    <div
                        className="resize-handle resize-ne"
                        onMouseDown={(e) => handleResizeStart('ne', e)}
                    />
                    <div
                        className="resize-handle resize-nw"
                        onMouseDown={(e) => handleResizeStart('nw', e)}
                    />
                </>
            )}

            <style jsx>{`
                .dashboard-widget {
                    border-radius: 8px;
                    transition: box-shadow 0.2s ease;
                }

                .dashboard-widget:hover {
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                .dashboard-widget.dragging {
                    z-index: 1000;
                    transform: rotate(5deg);
                }

                .widget-header {
                    position: absolute;
                    top: -30px;
                    right: 0;
                    z-index: 10;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                }

                .dashboard-widget:hover .widget-header {
                    opacity: 1;
                }

                .widget-controls {
                    display: flex;
                    gap: 4px;
                    background: white;
                    border-radius: 4px;
                    padding: 4px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }

                .drag-handle {
                    display: flex;
                    align-items: center;
                    padding: 4px;
                    cursor: move;
                    color: #6c757d;
                }

                .resize-handle {
                    position: absolute;
                    background: #007bff;
                    border: 1px solid white;
                    border-radius: 2px;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                }

                .dashboard-widget:hover .resize-handle {
                    opacity: 1;
                }

                .resize-se {
                    bottom: -4px;
                    right: -4px;
                    width: 16px;
                    height: 16px;
                    cursor: se-resize;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                }

                .resize-sw {
                    bottom: -4px;
                    left: -4px;
                    width: 8px;
                    height: 8px;
                    cursor: sw-resize;
                }

                .resize-ne {
                    top: -4px;
                    right: -4px;
                    width: 8px;
                    height: 8px;
                    cursor: ne-resize;
                }

                .resize-nw {
                    top: -4px;
                    left: -4px;
                    width: 8px;
                    height: 8px;
                    cursor: nw-resize;
                }

                .widget-content {
                    height: 100%;
                    overflow: hidden;
                }
            `}</style>
        </div>
    );
}