import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DashboardWidget from '../DashboardWidget';

// Mock react-dnd
jest.mock('react-dnd', () => ({
    useDrag: () => [{ isDragging: false }, jest.fn(), jest.fn()]
}));

// Mock Recharts
jest.mock('recharts', () => ({
    ResponsiveContainer: ({ children }) => <div data-testid="responsive-container">{children}</div>,
    BarChart: ({ children }) => <div data-testid="bar-chart">{children}</div>,
    Bar: () => <div data-testid="bar" />,
    XAxis: () => <div data-testid="x-axis" />,
    YAxis: () => <div data-testid="y-axis" />,
    CartesianGrid: () => <div data-testid="cartesian-grid" />,
    Tooltip: () => <div data-testid="tooltip" />,
    Legend: () => <div data-testid="legend" />
}));

describe('DashboardWidget', () => {
    const mockWidget = {
        id: 'widget-1',
        type: 'KPI',
        position: { x: 0, y: 0, w: 4, h: 4 },
        configuration: {
            title: 'Test KPI',
            chart_options: {}
        }
    };

    const mockProps = {
        widget: mockWidget,
        isEditing: false,
        onEdit: jest.fn(),
        onDelete: jest.fn(),
        onDragStart: jest.fn(),
        onDragEnd: jest.fn(),
        onResize: jest.fn(),
        dashboardId: 1
    };

    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders KPI widget correctly', () => {
        render(<DashboardWidget {...mockProps} />);
        
        expect(screen.getByText('Test KPI')).toBeInTheDocument();
    });

    test('shows edit controls in editing mode', () => {
        render(<DashboardWidget {...mockProps} isEditing={true} />);
        
        const widget = screen.getByText('Test KPI').closest('.dashboard-widget');
        fireEvent.mouseEnter(widget);
        
        expect(screen.getByTitle('Edit Widget')).toBeInTheDocument();
        expect(screen.getByTitle('Delete Widget')).toBeInTheDocument();
        expect(screen.getByTitle('Drag to move')).toBeInTheDocument();
    });

    test('hides edit controls in view mode', () => {
        render(<DashboardWidget {...mockProps} isEditing={false} />);
        
        expect(screen.queryByTitle('Edit Widget')).not.toBeInTheDocument();
        expect(screen.queryByTitle('Delete Widget')).not.toBeInTheDocument();
    });

    test('calls onEdit when edit button is clicked', async () => {
        const user = userEvent.setup();
        
        render(<DashboardWidget {...mockProps} isEditing={true} />);
        
        const widget = screen.getByText('Test KPI').closest('.dashboard-widget');
        fireEvent.mouseEnter(widget);
        
        await user.click(screen.getByTitle('Edit Widget'));
        
        expect(mockProps.onEdit).toHaveBeenCalled();
    });

    test('calls onDelete when delete button is clicked', async () => {
        const user = userEvent.setup();
        
        render(<DashboardWidget {...mockProps} isEditing={true} />);
        
        const widget = screen.getByText('Test KPI').closest('.dashboard-widget');
        fireEvent.mouseEnter(widget);
        
        await user.click(screen.getByTitle('Delete Widget'));
        
        expect(mockProps.onDelete).toHaveBeenCalled();
    });

    test('shows resize handles in editing mode', () => {
        render(<DashboardWidget {...mockProps} isEditing={true} />);
        
        const widget = screen.getByText('Test KPI').closest('.dashboard-widget');
        fireEvent.mouseEnter(widget);
        
        expect(screen.getByTitle('Resize')).toBeInTheDocument();
    });

    test('handles unknown widget type gracefully', () => {
        const unknownWidget = {
            ...mockWidget,
            type: 'UnknownType'
        };
        
        render(<DashboardWidget {...mockProps} widget={unknownWidget} />);
        
        expect(screen.getByText('Unknown widget type: UnknownType')).toBeInTheDocument();
    });

    test('applies dragging styles when dragging', () => {
        // Mock useDrag to return isDragging: true
        jest.doMock('react-dnd', () => ({
            useDrag: () => [{ isDragging: true }, jest.fn(), jest.fn()]
        }));
        
        const { rerender } = render(<DashboardWidget {...mockProps} isEditing={true} />);
        
        // Re-render to apply the mock
        rerender(<DashboardWidget {...mockProps} isEditing={true} />);
        
        const widget = screen.getByText('Test KPI').closest('.dashboard-widget');
        expect(widget).toHaveClass('dragging');
    });

    test('handles resize start and end events', () => {
        render(<DashboardWidget {...mockProps} isEditing={true} />);
        
        const widget = screen.getByText('Test KPI').closest('.dashboard-widget');
        fireEvent.mouseEnter(widget);
        
        const resizeHandle = screen.getByTitle('Resize');
        
        // Start resize
        fireEvent.mouseDown(resizeHandle, { clientX: 100, clientY: 100 });
        
        expect(widget).toHaveClass('resizing');
        
        // End resize
        fireEvent.mouseUp(document);
        
        expect(widget).not.toHaveClass('resizing');
    });
});