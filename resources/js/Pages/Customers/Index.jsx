import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconSearch, IconFilter, IconEye, IconTrendingUp, IconTrendingDown, IconAlertTriangle, IconUsers, IconPlus, IconInfoCircle } from '@tabler/icons-react';

export default function Index({ customers, filters, branches, riskLevels }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedBranch, setSelectedBranch] = useState(filters.branch_code || '');
    const [selectedRiskLevel, setSelectedRiskLevel] = useState(filters.risk_level || '');
    const [activeFilter, setActiveFilter] = useState(filters.is_active || '');

    // Tooltip system
    useEffect(() => {
        let currentTooltip = null;

        const createTooltip = (text) => {
            if (currentTooltip) {
                document.body.removeChild(currentTooltip);
                currentTooltip = null;
            }

            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = text;
            tooltip.style.cssText = `
                position: fixed;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 99999;
                max-width: 350px;
                word-wrap: break-word;
                white-space: pre-line;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s ease;
            `;

            document.body.appendChild(tooltip);
            currentTooltip = tooltip;
            return tooltip;
        };

        const showTooltip = (element, text) => {
            const tooltip = createTooltip(text);
            const rect = element.getBoundingClientRect();
            
            setTimeout(() => {
                const tooltipRect = tooltip.getBoundingClientRect();
                let top = rect.top - tooltipRect.height - 8;
                let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                
                if (left < 8) left = 8;
                if (left + tooltipRect.width > window.innerWidth - 8) {
                    left = window.innerWidth - tooltipRect.width - 8;
                }
                if (top < 8) {
                    top = rect.bottom + 8;
                }

                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
                tooltip.style.opacity = '1';
            }, 10);
        };

        const hideTooltip = () => {
            if (currentTooltip) {
                currentTooltip.style.opacity = '0';
                setTimeout(() => {
                    if (currentTooltip && document.body.contains(currentTooltip)) {
                        document.body.removeChild(currentTooltip);
                        currentTooltip = null;
                    }
                }, 200);
            }
        };

        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                const text = e.target.getAttribute('data-tooltip');
                showTooltip(e.target, text);
            });
            element.addEventListener('mouseleave', hideTooltip);
            element.addEventListener('blur', hideTooltip);
        });

        document.addEventListener('mouseleave', hideTooltip);

        return () => {
            tooltipElements.forEach(element => {
                element.removeEventListener('mouseenter', showTooltip);
                element.removeEventListener('mouseleave', hideTooltip);
                element.removeEventListener('blur', hideTooltip);
            });
            document.removeEventListener('mouseleave', hideTooltip);
            
            if (currentTooltip && document.body.contains(currentTooltip)) {
                document.body.removeChild(currentTooltip);
                currentTooltip = null;
            }
        };
    }, [customers]);

    const handleSearch = () => {
        router.get('/customers', {
            search: searchTerm,
            branch_code: selectedBranch,
            risk_level: selectedRiskLevel,
            is_active: activeFilter,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedBranch('');
        setSelectedRiskLevel('');
        setActiveFilter('');
        router.get('/customers');
    };

    const getRiskBadgeColor = (riskLevel) => {
        switch (riskLevel) {
            case 'Low': return 'bg-success';
            case 'Medium': return 'bg-warning';
            case 'High': return 'bg-danger';
            default: return 'bg-secondary';
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    return (
        <AppLayout title="Customer 360">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Customer Management
                            </div>
                            <h2 className="page-title">
                                Customer 360
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/customers/create" className="btn btn-primary d-none d-sm-inline-block">
                                    <IconPlus size={16} className="me-1" />
                                    Add Customer
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    <div className="row row-deck row-cards">
                        {/* Search and Filters */}
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title text-dark fw-bold">
                                        <IconFilter size={18} className="me-2" />
                                        Search & Filter Customers
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="row g-3 align-items-end">
                                        <div className="col-md-3">
                                            <label className="form-label">Search</label>
                                            <div className="input-icon">
                                                <span className="input-icon-addon">
                                                    <IconSearch size={16} />
                                                </span>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    placeholder="Search customers..."
                                                    value={searchTerm}
                                                    onChange={(e) => setSearchTerm(e.target.value)}
                                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-2">
                                            <label className="form-label">Branch</label>
                                            <select 
                                                className="form-select" 
                                                value={selectedBranch} 
                                                onChange={(e) => setSelectedBranch(e.target.value)}
                                            >
                                                <option value="">All Branches</option>
                                                {branches.map((branch) => (
                                                    <option key={branch} value={branch}>
                                                        {branch}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-2">
                                            <label className="form-label">Risk Level</label>
                                            <select 
                                                className="form-select" 
                                                value={selectedRiskLevel} 
                                                onChange={(e) => setSelectedRiskLevel(e.target.value)}
                                            >
                                                <option value="">All Risk Levels</option>
                                                {riskLevels.map((level) => (
                                                    <option key={level} value={level}>
                                                        {level}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-2">
                                            <label className="form-label">Status</label>
                                            <select 
                                                className="form-select" 
                                                value={activeFilter} 
                                                onChange={(e) => setActiveFilter(e.target.value)}
                                            >
                                                <option value="">All Customers</option>
                                                <option value="1">Active Only</option>
                                                <option value="0">Inactive Only</option>
                                            </select>
                                        </div>
                                        <div className="col-md-3">
                                            <div className="btn-list">
                                                <button className="btn btn-primary" onClick={handleSearch}>
                                                    <IconSearch size={16} className="me-1" />
                                                    Search
                                                </button>
                                                <button className="btn btn-outline-secondary" onClick={clearFilters}>
                                                    Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Customer List */}
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title text-dark fw-bold">
                                        <IconUsers size={18} className="me-2" />
                                        Customers ({customers.total})
                                    </h3>
                                </div>
                                
                                {customers.data && customers.data.length > 0 ? (
                                    <div className="table-responsive">
                                        <table className="table table-vcenter card-table">
                                            <thead>
                                                <tr>
                                                    <th className="w-1">Customer</th>
                                                    <th className="w-1">Branch</th>
                                                    <th className="w-1">
                                                        <span className="d-flex align-items-center">
                                                            Risk Level
                                                            <IconInfoCircle 
                                                                size={12} 
                                                                className="ms-1 text-muted" 
                                                                data-tooltip="Risk assessment based on NPL ratio:\n• Low: <2% NPL ratio\n• Medium: 2-5% NPL ratio\n• High: >5% NPL ratio\n\nCalculated from customer's loan portfolio performance"
                                                            />
                                                        </span>
                                                    </th>
                                                    <th className="w-1">
                                                        <span className="d-flex align-items-center">
                                                            Profitability
                                                            <IconInfoCircle 
                                                                size={12} 
                                                                className="ms-1 text-muted" 
                                                                data-tooltip="Net annual profitability calculated as:\nInterest Earned - Interest Paid - Operational Costs\n\n• Interest Earned: Loan amount × Interest rate (annual)\n• Interest Paid: Deposit balance × Interest rate (annual, default 2%)\n• Operational Costs: Base cost (50 per product) + loan management (150) + deposit maintenance (25) + NPL risk costs (0.1% of NPL exposure)"
                                                            />
                                                        </span>
                                                    </th>
                                                    <th className="w-1">
                                                        <span className="d-flex align-items-center">
                                                            Loans Outstanding
                                                            <IconInfoCircle 
                                                                size={12} 
                                                                className="ms-1 text-muted" 
                                                                data-tooltip="Total outstanding loan balances:\nSum of 'amount' or 'outstanding_balance' fields from all products with category 'Loan'\n\nIncludes all active loans across the customer's portfolio"
                                                            />
                                                        </span>
                                                    </th>
                                                    <th className="w-1">
                                                        <span className="d-flex align-items-center">
                                                            Total Deposits
                                                            <IconInfoCircle 
                                                                size={12} 
                                                                className="ms-1 text-muted" 
                                                                data-tooltip="Total deposit account balances:\nSum of 'amount' or 'balance' fields from all products with category 'Deposit' or 'Account'\n\nIncludes all active deposit accounts"
                                                            />
                                                        </span>
                                                    </th>
                                                    <th className="w-1">Status</th>
                                                    <th className="w-1">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {customers.data.map((customer) => (
                                                    <tr key={customer.id}>
                                                        <td>
                                                            <div className="d-flex align-items-center">
                                                                <span className="avatar me-3 bg-primary text-white">
                                                                    {customer.name.charAt(0).toUpperCase()}
                                                                </span>
                                                                <div className="flex-fill">
                                                                    <div className="font-weight-medium text-dark">{customer.name}</div>
                                                                    <div className="text-muted small">{customer.customer_id}</div>
                                                                    {customer.email && (
                                                                        <div className="text-muted small">{customer.email}</div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="text-muted">
                                                                {customer.branch_code || 'N/A'}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span className={`badge ${getRiskBadgeColor(customer.risk_level)} text-white`}>
                                                                {customer.risk_level}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div className="d-flex align-items-center">
                                                                {customer.profitability >= 0 ? (
                                                                    <IconTrendingUp size={16} className="text-success me-1" />
                                                                ) : (
                                                                    <IconTrendingDown size={16} className="text-danger me-1" />
                                                                )}
                                                                <span className={`fw-medium ${customer.profitability >= 0 ? 'text-success' : 'text-danger'}`}>
                                                                    {formatCurrency(customer.profitability)}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="text-muted fw-medium">
                                                                {formatCurrency(customer.total_loans_outstanding)}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="text-muted fw-medium">
                                                                {formatCurrency(customer.total_deposits)}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span className={`badge ${customer.is_active ? 'bg-success' : 'bg-secondary'} text-white`}>
                                                                {customer.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <Link href={`/customers/${customer.id}`} className="btn btn-primary btn-sm">
                                                                <IconEye size={16} />
                                                            </Link>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="card-body">
                                        <div className="empty">
                                            <div className="empty-img">
                                                <IconUsers size={48} className="text-muted" />
                                            </div>
                                            <p className="empty-title">No customers found</p>
                                            <p className="empty-subtitle text-muted">
                                                Try adjusting your search criteria or add new customers
                                            </p>
                                            <div className="empty-action">
                                                <Link href="/customers/create" className="btn btn-primary">
                                                    <IconPlus size={16} className="me-1" />
                                                    Add Customer
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Pagination */}
                                {customers.links && customers.data && customers.data.length > 0 && (
                                    <div className="card-footer d-flex align-items-center">
                                        <p className="m-0 text-muted">
                                            Showing <span>{customers.from}</span> to <span>{customers.to}</span> of <span>{customers.total}</span> entries
                                        </p>
                                        <ul className="pagination m-0 ms-auto">
                                            {customers.links.map((link, index) => (
                                                <li key={index} className={`page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`}>
                                                    {link.url ? (
                                                        <Link href={link.url} className="page-link" dangerouslySetInnerHTML={{ __html: link.label }} />
                                                    ) : (
                                                        <span className="page-link" dangerouslySetInnerHTML={{ __html: link.label }} />
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}