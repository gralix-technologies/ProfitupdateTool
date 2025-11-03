import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import WidgetPalette from '../WidgetPalette';

describe('WidgetPalette', () => {
    const mockOnSelectWidget = jest.fn();
    const mockOnClose = jest.fn();

    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders all widget types', () => {
        render(
            <WidgetPalette
                onSelectWidget={mockOnSelectWidget}
                onClose={mockOnClose}
            />
        );

        expect(screen.getByText('Add Widget')).toBeInTheDocument();
        expect(screen.getByText('KPI Card')).toBeInTheDocument();
        expect(screen.getByText('Data Table')).toBeInTheDocument();
        expect(screen.getByText('Bar Chart')).toBeInTheDocument();
        expect(screen.getByText('Line Chart')).toBeInTheDocument();
        expect(screen.getByText('Pie Chart')).toBeInTheDocument();
        expect(screen.getByText('Heatmap')).toBeInTheDocument();
    });

    test('calls onSelectWidget when widget type is clicked', async () => {
        const user = userEvent.setup();

        render(
            <WidgetPalette
                onSelectWidget={mockOnSelectWidget}
                onClose={mockOnClose}
            />
        );

        await user.click(screen.getByText('KPI Card'));

        expect(mockOnSelectWidget).toHaveBeenCalledWith('KPI');
    });

    test('calls onClose when close button is clicked', async () => {
        const user = userEvent.setup();

        render(
            <WidgetPalette
                onSelectWidget={mockOnSelectWidget}
                onClose={mockOnClose}
            />
        );

        await user.click(screen.getByRole('button', { name: /close/i }));

        expect(mockOnClose).toHaveBeenCalled();
    });

    test('calls onClose when cancel button is clicked', async () => {
        const user = userEvent.setup();

        render(
            <WidgetPalette
                onSelectWidget={mockOnSelectWidget}
                onClose={mockOnClose}
            />
        );

        await user.click(screen.getByText('Cancel'));

        expect(mockOnClose).toHaveBeenCalled();
    });

    test('displays widget descriptions', () => {
        render(
            <WidgetPalette
                onSelectWidget={mockOnSelectWidget}
                onClose={mockOnClose}
            />
        );

        expect(screen.getByText('Display key performance indicators with large numbers')).toBeInTheDocument();
        expect(screen.getByText('Show detailed data in tabular format')).toBeInTheDocument();
        expect(screen.getByText('Compare values across categories')).toBeInTheDocument();
        expect(screen.getByText('Show trends over time')).toBeInTheDocument();
        expect(screen.getByText('Display proportions and percentages')).toBeInTheDocument();
        expect(screen.getByText('Visualize data density and patterns')).toBeInTheDocument();
    });

    test('renders modal with backdrop', () => {
        render(
            <WidgetPalette
                onSelectWidget={mockOnSelectWidget}
                onClose={mockOnClose}
            />
        );

        expect(screen.getByRole('dialog', { hidden: true })).toBeInTheDocument();
        expect(document.querySelector('.modal-backdrop')).toBeInTheDocument();
    });
});