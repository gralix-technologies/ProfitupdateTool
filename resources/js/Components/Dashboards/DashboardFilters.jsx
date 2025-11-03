import React, { useState, useEffect } from 'react';
import { 
    IconFilter, 
    IconX, 
    IconCalendar, 
    IconBuilding, 
    IconCurrencyDollar,
    IconUsers,
    IconCategory,
    IconRefresh
} from '@tabler/icons-react';

export default function DashboardFilters({ 
    filters = {}, 
    onFiltersChange, 
    onApplyFilters,
    filterOptions = {},
    isLoading = false,
    className = '' 
}) {
    const [localFilters, setLocalFilters] = useState({
        date_range: { start: '', end: '' },
        branch: '',
        currency: '',
        demographic: '',
        product_type: '',
        ...filters
    });
    
    const [isExpanded, setIsExpanded] = useState(false);
    const [hasActiveFilters, setHasActiveFilters] = useState(false);

    useEffect(() => {
        setLocalFilters(prev => ({ ...prev, ...filters }));
    }, [filters]);

    useEffect(() => {
        const active = Object.entries(localFilters).some(([key, value]) => {
            if (key === 'date_range') {
                return value.start || value.end;
            }
            return value && value !== '';
        });
        setHasActiveFilters(active);
    }, [localFilters]);

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        onFiltersChange?.(newFilters);
    };

    const handleDateRangeChange = (field, value) => {
        const newDateRange = { ...localFilters.date_range, [field]: value };
        handleFilterChange('date_range', newDateRange);
    };

    const handleApplyFilters = () => {
        onApplyFilters?.(localFilters);
    };

    const handleClearFilters = () => {
        const clearedFilters = {
            date_range: { start: '', end: '' },
            branch: '',
            currency: '',
            demographic: '',
            product_type: ''
        };
        setLocalFilters(clearedFilters);
        onFiltersChange?.(clearedFilters);
        onApplyFilters?.(clearedFilters);
    };

    const getFilterCount = () => {
        let count = 0;
        Object.entries(localFilters).forEach(([key, value]) => {
            if (key === 'date_range') {
                if (value.start || value.end) count++;
            } else if (value && value !== '') {
                count++;
            }
        });
        return count;
    };

    return (
        <div className={`dashboard-filters ${className}`}>
            <div className="d-flex align-items-center gap-2 mb-3">
                <button
                    type="button"
                    className={`btn btn-outline-primary ${isExpanded ? 'active' : ''}`}
                    onClick={() => setIsExpanded(!isExpanded)}
                >
                    <IconFilter size={16} className="me-1" />
                    Filters
                    {hasActiveFilters && (
                        <span className="badge bg-primary ms-2">{getFilterCount()}</span>
                    )}
                </button>
                
                {hasActiveFilters && (
                    <button
                        type="button"
                        className="btn btn-outline-secondary btn-sm"
                        onClick={handleClearFilters}
                        title="Clear all filters"
                    >
                        <IconX size={14} />
                    </button>
                )}
                
                <button
                    type="button"
                    className="btn btn-primary"
                    onClick={handleApplyFilters}
                    disabled={isLoading}
                >
                    {isLoading ? (
                        <div className="spinner-border spinner-border-sm me-1" role="status">
                            <span className="visually-hidden">Loading...</span>
                        </div>
                    ) : (
                        <IconRefresh size={16} className="me-1" />
                    )}
                    Apply Filters
                </button>
            </div>

            {isExpanded && (
                <div className="card">
                    <div className="card-body">
                        <div className="row g-3">
                            {/* Date Range Filter */}
                            <div className="col-md-6">
                                <label className="form-label">
                                    <IconCalendar size={16} className="me-1" />
                                    Date Range
                                </label>
                                <div className="row g-2">
                                    <div className="col">
                                        <input
                                            type="date"
                                            className="form-control"
                                            placeholder="Start date"
                                            value={localFilters.date_range?.start || ''}
                                            max={filterOptions.date_range?.max}
                                            min={filterOptions.date_range?.min}
                                            onChange={(e) => handleDateRangeChange('start', e.target.value)}
                                        />
                                    </div>
                                    <div className="col">
                                        <input
                                            type="date"
                                            className="form-control"
                                            placeholder="End date"
                                            value={localFilters.date_range?.end || ''}
                                            max={filterOptions.date_range?.max}
                                            min={localFilters.date_range?.start || filterOptions.date_range?.min}
                                            onChange={(e) => handleDateRangeChange('end', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Branch Filter */}
                            <div className="col-md-3">
                                <label className="form-label">
                                    <IconBuilding size={16} className="me-1" />
                                    Branch
                                </label>
                                <select
                                    className="form-select"
                                    value={localFilters.branch || ''}
                                    onChange={(e) => handleFilterChange('branch', e.target.value)}
                                >
                                    <option value="">All Branches</option>
                                    {filterOptions.branches?.map(branch => (
                                        <option key={branch} value={branch}>
                                            {branch}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Currency Filter */}
                            <div className="col-md-3">
                                <label className="form-label">
                                    <IconCurrencyDollar size={16} className="me-1" />
                                    Currency
                                </label>
                                <select
                                    className="form-select"
                                    value={localFilters.currency || ''}
                                    onChange={(e) => handleFilterChange('currency', e.target.value)}
                                >
                                    <option value="">All Currencies</option>
                                    {filterOptions.currencies?.map(currency => (
                                        <option key={currency} value={currency}>
                                            {currency}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Demographic Filter */}
                            <div className="col-md-6">
                                <label className="form-label">
                                    <IconUsers size={16} className="me-1" />
                                    Customer Segment
                                </label>
                                <select
                                    className="form-select"
                                    value={localFilters.demographic || ''}
                                    onChange={(e) => handleFilterChange('demographic', e.target.value)}
                                >
                                    <option value="">All Segments</option>
                                    {filterOptions.demographics?.map(demographic => (
                                        <option key={demographic} value={demographic}>
                                            {demographic}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Product Type Filter */}
                            <div className="col-md-6">
                                <label className="form-label">
                                    <IconCategory size={16} className="me-1" />
                                    Product Type
                                </label>
                                <select
                                    className="form-select"
                                    value={localFilters.product_type || ''}
                                    onChange={(e) => handleFilterChange('product_type', e.target.value)}
                                >
                                    <option value="">All Product Types</option>
                                    {filterOptions.product_types?.map(type => (
                                        <option key={type} value={type}>
                                            {type}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Active Filters Summary */}
                        {hasActiveFilters && (
                            <div className="mt-3 pt-3 border-top">
                                <div className="d-flex flex-wrap gap-2">
                                    <small className="text-muted me-2">Active filters:</small>
                                    
                                    {localFilters.date_range?.start && (
                                        <span className="badge bg-light text-dark">
                                            From: {localFilters.date_range.start}
                                        </span>
                                    )}
                                    
                                    {localFilters.date_range?.end && (
                                        <span className="badge bg-light text-dark">
                                            To: {localFilters.date_range.end}
                                        </span>
                                    )}
                                    
                                    {localFilters.branch && (
                                        <span className="badge bg-light text-dark">
                                            Branch: {localFilters.branch}
                                        </span>
                                    )}
                                    
                                    {localFilters.currency && (
                                        <span className="badge bg-light text-dark">
                                            Currency: {localFilters.currency}
                                        </span>
                                    )}
                                    
                                    {localFilters.demographic && (
                                        <span className="badge bg-light text-dark">
                                            Segment: {localFilters.demographic}
                                        </span>
                                    )}
                                    
                                    {localFilters.product_type && (
                                        <span className="badge bg-light text-dark">
                                            Product: {localFilters.product_type}
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}