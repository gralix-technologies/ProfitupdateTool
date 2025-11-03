import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DashboardBuilder from '../DashboardBuilder';

// Mock react-dnd
jest.mock('react-dnd', () => ({
    DndProvider: ({ children }) => <div data-testid="dnd-provider">{children}</div>,
    useDrag: () => [{ isDragging: false }, jest.fn(), jest.fn()],
    useDrop: () => [{ isOver: false }, jest.fn()]
}));

jest.mock('react-dnd-html5-backend', () => ({
    HTML5Backend: 'HTML5Backend'
}));

// Mock Recharts
jest.mock('recharts', () => ({
    ResponsiveContainer: ({ children }) => <div data-testid="responsive-container">{children}</div>,
    BarChart: ({ children }) => <div data-testid="bar-chart">{children}</div>,
    LineChart: ({ children }) => <div data-testid="line-chart">{children}</div>,
    PieChart: ({ children }) => <div data-testid="pie-chart">{children}</div>,
    Bar: () => <div data-testid="bar" />,
    Line: () => <div data-testid="line" />,
    Pie: () => <div data-testid="pie" />,
    Cell: () => <div data-testid="cell" />,
    XAxis: () => <div data-testid="x-axis" />,
    YAxis: () => <div data-testid="y-axis" />,
    CartesianGrid: () => <div data-testid="cartesian-grid" />,
    Tooltip: () => <div data-testid="tooltip" />,
    Legend: () => <div data-testid="legend" />
}));

describe('DashboardBuilder', () => {
    const mockOnWidgetsChange = jest.fn();

    beforeEach(() => {
        mockOnWidgetsChange.mockClear();
    });

    test('renders empty dashboard in editing mode', () => {
        render(
            <DashboardBuilder
                widgets={[]}
                onWidgetsChange={mockOnWidgetsChange}
                isEditing={true}
            />
        );

        expect(screen.getByText('Add Widget')).toBeInTheDocument();
        expect(screen.getByText('Start building your dashboard')).toBeInTheDocument();
    });

    test('renders widgets in view mode', () => {
        const widgets = [
            {
                id: 'widget-1',
                type: 'KPI',
                position: { x: 0, y: 0, w: 4, h: 4 },
                configuration: { title: 'Test KPI' }
            }
        ];

        render(
            <DashboardBuilder
                widgets={widgets}
                onWidgetsChange={mockOnWidgetsChange}
                isEditing={false}
            />
        );

        expect(screen.queryByText('Add Widget')).not.toBeInTheDocument();
        expect(screen.getByText('Test KPI')).toBeInTheDocument();
    });

    test('opens widget palette when Add Widget is clicked', async () => {
        const user = userEvent.setup();

        render(
            <DashboardBuilder
                widgets={[]}
                onWidgetsChange={mockOnWidgetsChange}
                isEditing={true}
            />
        );

        await user.click(screen.getByText('Add Widget'));

        expect(screen.getByText('Add Widget')).toBeInTheDocument();
        expect(screen.getByText('KPI Card')).toBeInTheDocument();
        expect(screen.getByText('Data Table')).toBeInTheDocument();
        expect(screen.getByText('Bar Chart')).toBeInTheDocument();
    });

    test('adds new widget when selected from palette', async () => {
        const user = userEvent.setup();

        render(
            <DashboardBuilder
                widgets={[]}
                onWidgetsChange={mockOnWidgetsChange}
                isEditing={true}
            />
        );

        // Open widget palette
        await user.click(screen.getByText('Add Widget'));

        // Select KPI widget
        await user.click(screen.getByText('KPI Card'));

        // Widget configuration modal should open
        expect(screen.getByText('Configure KPI Widget')).toBeInTheDocument();
    });

    test('saves widget configuration', async () => {
        const user = userEvent.setup();

        render(
            <DashboardBuilder
                widgets={[]}
                onWidgetsChange={mockOnWidgetsChange}
                isEditing={true}
            />
        );

        // Open widget palette and select KPI
        await user.click(screen.getByText('Add Widget'));
        await user.click(screen.getByText('KPI Card'));

        // Fill in widget title
        const titleInput = screen.getByLabelText('Widget Title');
        await user.clear(titleInput);
        await user.type(titleInput, 'My Test KPI');

        // Save widget
        await user.click(screen.getByText('Save Widget'));

        // Verify onWidgetsChange was called
        expect(mockOnWidgetsChange).toHaveBeenCalledWith(
            expect.arrayContaining([
                expect.objectContaining({
                    type: 'KPI',
                    configuration: expect.objectContaining({
                        title: 'My Test KPI'
                    })
                })
            ])
        );
    });

    test('deletes widget when delete button is clicked', async () => {
        const user = userEvent.setup();
        const widgets = [
            {
                id: 'widget-1',
                type: 'KPI',
                position: { x: 0, y: 0, w: 4, h: 4 },
                configuration: { title: 'Test KPI' }
            }
        ];

        // Mock window.confirm
        window.confirm = jest.fn(() => true);

        render(
            <DashboardBuilder
                widgets={widgets}
                onWidgetsChange={mockOnWidgetsChange}
                isEditing={true}
            />
        );

        // Hover over widget to show controls
        const widget = screen.getByText('Test KPI').closest('.dashboard-widget-container');
        fireEvent.mouseEnter(widget);

        // Click delete button
        const deleteButton = screen.getByTitle('Delete Widget');
        await user.click(deleteButton);

        // Verify onWidgetsChange was called with empty array
        expect(mockOnWidgetsChange).toHaveBeenCalledWith([]);
    });

    test('updates widget position on move', () => {
        const widgets = [
            {
                id: 'widget-1',
                type: 'KPI',
                position: { x: 0, y: 0, w: 4, h: 4 },
                configuration: { title: 'Test KPI' }
            }
        ];

        const { rerender } = render(
            <DashboardBuilder
                widgets={widgets}
                onWidgetsChange={mockOnWidgetsChange}
                isEditing={true}
            />
        );

        // Simulate widget move
        const dashboardBuilder = screen.getByTestId('dnd-provider').firstChild;
        const newPosition = { x: 2, y: 1, w: 4, h: 4 };

        // Trigger move handler directly (since we can't easily simulate drag-drop)
        const moveHandler = dashboardBuilder.props.children.props.onWidgetMove;
        if (moveHandler) {
            moveHandler('widget-1', newPosition);
        }

        // Verify onWidgetsChange was called with updated position
        expect(mockOnWidgetsChange).toHaveBeenCalledWith([
            expect.objectContaining({
                id: 'widget-1',
                position: newPosition
            })
        ]);
    });
});