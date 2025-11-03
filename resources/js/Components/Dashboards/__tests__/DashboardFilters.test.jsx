import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import DashboardFilters from '../DashboardFilters';

// Mock icons
jest.mock('@tabler/icons-react', () => ({
    IconFilter: () => <div data-testid="filter-icon" />,
    IconX: () => <div data-testid="x-icon" />,
    IconCalendar: () => <div data-testid="calendar-icon" />,
    IconBuilding: () => <div data-testid="building-icon" />,
    IconCurrencyDollar: () => <div data-testid="currency-icon" />,
    IconUsers: () => <div data-testid="users-icon" />,
    IconCategory: () => <div data-testid="category-icon" />,
    IconRefresh: () => <div data-testid="refresh-icon" />
}));

describe('DashboardFilters', () => {
    const mockFilterOptions = {
        branches: ['MAIN', 'BRANCH1', 'BRANCH2'],
        currencies: ['ZMW', 'EUR', 'GBP'],
        demographics: ['Premium', 'Standard', 'Basic'],
        product_types: ['Loan', 'Account', 'Deposit'],
        date_range: {
            min: '2024-01-01',
            max: '2024-12-31'
        }
    };

    const defaultProps = {
        filters: {},
        onFiltersChange: jest.fn(),
        onApplyFilters: jest.fn(),
        filterOptions: mockFilterOptions,
        isLoading: false
    };

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders filter button with correct initial state', () => {
        render(<DashboardFilters {...defaultProps} />);
        
        expect(screen.getByText('Filters')).toBeInTheDocument();
        expect(screen.getByText('Apply Filters')).toBeInTheDocument();
    });

    it('expands filter panel when filter button is clicked', () => {
        render(<DashboardFilters {...defaultProps} />);
        
        const filterButton = screen.getByText('Filters');
        fireEvent.click(filterButton);
        
        expect(screen.getByText('Date Range')).toBeInTheDocument();
        expect(screen.getByText('Branch')).toBeInTheDocument();
        expect(screen.getByText('Currency')).toBeInTheDocument();
        expect(screen.getByText('Customer Segment')).toBeInTheDocument();
        expect(screen.getByText('Product Type')).toBeInTheDocument();
    });

    it('shows active filter count when filters are applied', () => {
        const filtersWithValues = {
            branch: 'MAIN',
            currency: 'ZMW',
            date_range: { start: '2024-01-01', end: '2024-12-31' }
        };

        render(<DashboardFilters {...defaultProps} filters={filtersWithValues} />);
        
        expect(screen.getByText('3')).toBeInTheDocument(); // Badge showing count
    });

    it('calls onFiltersChange when date range is modified', async () => {
        render(<DashboardFilters {...defaultProps} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        const startDateInput = screen.getByDisplayValue('');
        fireEvent.change(startDateInput, { target: { value: '2024-01-01' } });
        
        await waitFor(() => {
            expect(defaultProps.onFiltersChange).toHaveBeenCalledWith(
                expect.objectContaining({
                    date_range: expect.objectContaining({
                        start: '2024-01-01'
                    })
                })
            );
        });
    });

    it('calls onFiltersChange when branch filter is changed', async () => {
        render(<DashboardFilters {...defaultProps} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        const branchSelect = screen.getByDisplayValue('All Branches');
        fireEvent.change(branchSelect, { target: { value: 'MAIN' } });
        
        await waitFor(() => {
            expect(defaultProps.onFiltersChange).toHaveBeenCalledWith(
                expect.objectContaining({
                    branch: 'MAIN'
                })
            );
        });
    });

    it('calls onFiltersChange when currency filter is changed', async () => {
        render(<DashboardFilters {...defaultProps} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        const currencySelect = screen.getByDisplayValue('All Currencies');
        fireEvent.change(currencySelect, { target: { value: 'ZMW' } });
        
        await waitFor(() => {
            expect(defaultProps.onFiltersChange).toHaveBeenCalledWith(
                expect.objectContaining({
                    currency: 'ZMW'
                })
            );
        });
    });

    it('calls onApplyFilters when apply button is clicked', () => {
        const filters = { branch: 'MAIN' };
        render(<DashboardFilters {...defaultProps} filters={filters} />);
        
        const applyButton = screen.getByText('Apply Filters');
        fireEvent.click(applyButton);
        
        expect(defaultProps.onApplyFilters).toHaveBeenCalledWith(filters);
    });

    it('shows loading state when isLoading is true', () => {
        render(<DashboardFilters {...defaultProps} isLoading={true} />);
        
        expect(screen.getByRole('status')).toBeInTheDocument();
        expect(screen.getByText('Loading...')).toBeInTheDocument();
    });

    it('clears all filters when clear button is clicked', async () => {
        const filtersWithValues = {
            branch: 'MAIN',
            currency: 'USD'
        };

        render(<DashboardFilters {...defaultProps} filters={filtersWithValues} />);
        
        const clearButton = screen.getByTitle('Clear all filters');
        fireEvent.click(clearButton);
        
        await waitFor(() => {
            expect(defaultProps.onFiltersChange).toHaveBeenCalledWith({
                date_range: { start: '', end: '' },
                branch: '',
                currency: '',
                demographic: '',
                product_type: ''
            });
        });
        
        expect(defaultProps.onApplyFilters).toHaveBeenCalledWith({
            date_range: { start: '', end: '' },
            branch: '',
            currency: '',
            demographic: '',
            product_type: ''
        });
    });

    it('shows active filters summary when expanded', () => {
        const filtersWithValues = {
            branch: 'MAIN',
            currency: 'ZMW',
            date_range: { start: '2024-01-01', end: '2024-12-31' }
        };

        render(<DashboardFilters {...defaultProps} filters={filtersWithValues} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        expect(screen.getByText('Active filters:')).toBeInTheDocument();
        expect(screen.getByText('From: 2024-01-01')).toBeInTheDocument();
        expect(screen.getByText('To: 2024-12-31')).toBeInTheDocument();
        expect(screen.getByText('Branch: MAIN')).toBeInTheDocument();
        expect(screen.getByText('Currency: ZMW')).toBeInTheDocument();
    });

    it('populates select options from filterOptions prop', () => {
        render(<DashboardFilters {...defaultProps} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        // Check branch options
        const branchSelect = screen.getByDisplayValue('All Branches');
        expect(branchSelect).toBeInTheDocument();
        
        // Check currency options
        const currencySelect = screen.getByDisplayValue('All Currencies');
        expect(currencySelect).toBeInTheDocument();
    });

    it('validates date range constraints', () => {
        render(<DashboardFilters {...defaultProps} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        const startDateInput = screen.getAllByType('date')[0];
        const endDateInput = screen.getAllByType('date')[1];
        
        expect(startDateInput).toHaveAttribute('max', mockFilterOptions.date_range.max);
        expect(startDateInput).toHaveAttribute('min', mockFilterOptions.date_range.min);
        expect(endDateInput).toHaveAttribute('max', mockFilterOptions.date_range.max);
    });

    it('handles empty filter options gracefully', () => {
        const emptyFilterOptions = {
            branches: [],
            currencies: [],
            demographics: [],
            product_types: [],
            date_range: { min: '', max: '' }
        };

        render(<DashboardFilters {...defaultProps} filterOptions={emptyFilterOptions} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        // Should still render the form elements
        expect(screen.getByText('Date Range')).toBeInTheDocument();
        expect(screen.getByText('Branch')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        const { container } = render(
            <DashboardFilters {...defaultProps} className="custom-class" />
        );
        
        expect(container.firstChild).toHaveClass('dashboard-filters', 'custom-class');
    });

    it('handles demographic filter changes', async () => {
        render(<DashboardFilters {...defaultProps} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        const demographicSelect = screen.getByDisplayValue('All Segments');
        fireEvent.change(demographicSelect, { target: { value: 'Premium' } });
        
        await waitFor(() => {
            expect(defaultProps.onFiltersChange).toHaveBeenCalledWith(
                expect.objectContaining({
                    demographic: 'Premium'
                })
            );
        });
    });

    it('handles product type filter changes', async () => {
        render(<DashboardFilters {...defaultProps} />);
        
        // Expand filters
        fireEvent.click(screen.getByText('Filters'));
        
        const productTypeSelect = screen.getByDisplayValue('All Product Types');
        fireEvent.change(productTypeSelect, { target: { value: 'Loan' } });
        
        await waitFor(() => {
            expect(defaultProps.onFiltersChange).toHaveBeenCalledWith(
                expect.objectContaining({
                    product_type: 'Loan'
                })
            );
        });
    });
});