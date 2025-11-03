import React, { useState, useCallback } from 'react';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { IconPlus } from '@tabler/icons-react';
import DashboardGrid from './DashboardGrid';
import WidgetPalette from './WidgetPalette';
import WidgetConfigModal from './WidgetConfigModal';

export default function DashboardBuilder({ 
    widgets = [], 
    onWidgetsChange, 
    isEditing = false,
    dashboardId = null,
    filteredData = {},
    appliedFilters = {},
    formulas = [],
    products = [],
    customers = [],
    widgetTypes = {}
}) {
    const [showWidgetPalette, setShowWidgetPalette] = useState(false);
    const [selectedWidget, setSelectedWidget] = useState(null);
    const [showConfigModal, setShowConfigModal] = useState(false);

    const handleAddWidget = useCallback((widgetType) => {
        const newWidget = {
            id: `widget-${Date.now()}`,
            type: widgetType,
            position: {
                x: 0,
                y: 0,
                width: 4,
                height: 3
            },
            configuration: {
                title: `New ${widgetType}`,
                data_source: '',
                chart_options: {}
            }
        };

        setSelectedWidget(newWidget);
        setShowConfigModal(true);
        setShowWidgetPalette(false);
    }, []);

    const handleWidgetConfigSave = useCallback((widgetConfig) => {
        if (selectedWidget) {
            const updatedWidget = {
                ...selectedWidget,
                title: widgetConfig.title || selectedWidget.title,
                type: selectedWidget.type,
                configuration: {
                    ...selectedWidget.configuration,
                    ...widgetConfig
                },
                data_source: widgetConfig.data_source || selectedWidget.data_source,
                formula_id: widgetConfig.formula_id || selectedWidget.formula_id,
                product_id: widgetConfig.product_id || selectedWidget.product_id,
                position: selectedWidget.position || { x: 0, y: 0, width: 4, height: 3 },
                is_active: true
            };

            const existingIndex = widgets.findIndex(w => w.id === selectedWidget.id);
            let newWidgets;

            if (existingIndex >= 0) {
                // Update existing widget
                newWidgets = [...widgets];
                newWidgets[existingIndex] = updatedWidget;
            } else {
                // Add new widget
                newWidgets = [...widgets, updatedWidget];
            }

            onWidgetsChange(newWidgets);
        }

        setShowConfigModal(false);
        setSelectedWidget(null);
    }, [selectedWidget, widgets, onWidgetsChange]);

    const handleWidgetEdit = useCallback((widget) => {
        setSelectedWidget(widget);
        setShowConfigModal(true);
    }, []);

    const handleWidgetDelete = useCallback((widgetId) => {
        if (confirm('Are you sure you want to delete this widget?')) {
            const newWidgets = widgets.filter(w => w.id !== widgetId);
            onWidgetsChange(newWidgets);
        }
    }, [widgets, onWidgetsChange]);

    const handleWidgetMove = useCallback((widgetId, newPosition) => {
        const newWidgets = widgets.map(widget => 
            widget.id === widgetId 
                ? { ...widget, position: newPosition }
                : widget
        );
        onWidgetsChange(newWidgets);
    }, [widgets, onWidgetsChange]);

    const handleWidgetResize = useCallback((widgetId, newSize) => {
        const newWidgets = widgets.map(widget => 
            widget.id === widgetId 
                ? { ...widget, position: { ...widget.position, ...newSize } }
                : widget
        );
        onWidgetsChange(newWidgets);
    }, [widgets, onWidgetsChange]);

    return (
        <DndProvider backend={HTML5Backend}>
            <div className="dashboard-builder">
                {isEditing && (
                    <div className="mb-3">
                        <button
                            type="button"
                            className="btn btn-outline-primary"
                            onClick={() => setShowWidgetPalette(true)}
                        >
                            <IconPlus size={16} className="me-1" />
                            Add Widget
                        </button>
                    </div>
                )}

                <DashboardGrid
                    widgets={widgets}
                    isEditing={isEditing}
                    onWidgetEdit={handleWidgetEdit}
                    onWidgetDelete={handleWidgetDelete}
                    onWidgetMove={handleWidgetMove}
                    onWidgetResize={handleWidgetResize}
                    dashboardId={dashboardId}
                    filteredData={filteredData}
                    appliedFilters={appliedFilters}
                />

                {/* Widget Palette Modal */}
                {showWidgetPalette && (
                    <WidgetPalette
                        onSelectWidget={handleAddWidget}
                        onClose={() => setShowWidgetPalette(false)}
                    />
                )}

                {/* Widget Configuration Modal */}
                {showConfigModal && selectedWidget && (
                    <WidgetConfigModal
                        widget={selectedWidget}
                        onSave={handleWidgetConfigSave}
                        onClose={() => {
                            setShowConfigModal(false);
                            setSelectedWidget(null);
                        }}
                        formulas={formulas}
                        products={products}
                        customers={customers}
                        widgetTypes={widgetTypes}
                    />
                )}
            </div>
        </DndProvider>
    );
}