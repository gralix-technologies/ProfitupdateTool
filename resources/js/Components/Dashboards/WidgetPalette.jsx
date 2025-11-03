import React from 'react';
import { 
    IconChartBar, 
    IconChartPie, 
    IconChartLine, 
    IconTable, 
    IconDashboard,
    IconChartDots,
    IconX
} from '@tabler/icons-react';

const WIDGET_TYPES = [
    {
        type: 'KPI',
        name: 'KPI Card',
        description: 'Display key performance indicators with large numbers',
        icon: IconDashboard,
        color: 'primary'
    },
    {
        type: 'Table',
        name: 'Data Table',
        description: 'Show detailed data in tabular format',
        icon: IconTable,
        color: 'secondary'
    },
    {
        type: 'BarChart',
        name: 'Bar Chart',
        description: 'Compare values across categories',
        icon: IconChartBar,
        color: 'success'
    },
    {
        type: 'LineChart',
        name: 'Line Chart',
        description: 'Show trends over time',
        icon: IconChartLine,
        color: 'info'
    },
    {
        type: 'PieChart',
        name: 'Pie Chart',
        description: 'Display proportions and percentages',
        icon: IconChartPie,
        color: 'warning'
    },
    {
        type: 'Heatmap',
        name: 'Heatmap',
        description: 'Visualize data density and patterns',
        icon: IconChartDots,
        color: 'danger'
    }
];

export default function WidgetPalette({ onSelectWidget, onClose }) {
    const handleBackdropClick = (e) => {
        if (e.target === e.currentTarget) {
            onClose();
        }
    };

    const handleWidgetSelect = (widgetType) => {
        onSelectWidget(widgetType);
    };

    return (
        <div 
            className="modal modal-blur fade show" 
            style={{ display: 'block', zIndex: 1050 }}
            onClick={handleBackdropClick}
        >
            <div className="modal-dialog modal-lg modal-dialog-centered">
                <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                    <div className="modal-header">
                        <h5 className="modal-title">Add Widget</h5>
                        <button
                            type="button"
                            className="btn-close"
                            onClick={onClose}
                            aria-label="Close"
                        >
                            <IconX size={16} />
                        </button>
                    </div>
                    <div className="modal-body">
                        <div className="row g-3">
                            {WIDGET_TYPES.map((widgetType) => {
                                const IconComponent = widgetType.icon;
                                return (
                                    <div key={widgetType.type} className="col-md-6">
                                        <div
                                            className="card card-link cursor-pointer h-100"
                                            onClick={() => handleWidgetSelect(widgetType.type)}
                                            style={{ cursor: 'pointer' }}
                                        >
                                            <div className="card-body text-center">
                                                <div className="mb-3">
                                                    <span className={`avatar avatar-lg bg-${widgetType.color}-lt`}>
                                                        <IconComponent size={32} />
                                                    </span>
                                                </div>
                                                <h3 className="card-title mb-2">
                                                    {widgetType.name}
                                                </h3>
                                                <p className="text-muted small mb-0">
                                                    {widgetType.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button
                            type="button"
                            className="btn btn-secondary"
                            onClick={onClose}
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}