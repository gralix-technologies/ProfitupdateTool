import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    IconArrowLeft, 
    IconTrendingUp, 
    IconTrendingDown, 
    IconAlertTriangle, 
    IconCurrencyDollar, 
    IconCreditCard, 
    IconPigMoney,
    IconRefresh,
    IconChartBar,
    IconChartPie,
    IconInfoCircle
} from '@tabler/icons-react';
import CustomerProfitabilityWidget from '@/Components/Customers/CustomerProfitabilityWidget';
import CustomerPortfolioWidget from '@/Components/Customers/CustomerPortfolioWidget';
import CustomerRiskWidget from '@/Components/Customers/CustomerRiskWidget';
import CustomerInsightsWidget from '@/Components/Customers/CustomerInsightsWidget';

export default function Show({ customer, profitability, nplExposure, productsByCategory }) {
    const [isUpdatingMetrics, setIsUpdatingMetrics] = useState(false);
    const [currentProfitability, setCurrentProfitability] = useState(profitability);

    // Function to generate detailed tooltip content based on actual data
    const generateTooltipContent = (type) => {
        if (!productsByCategory) return '';
        
        let content = '';
        
        try {
            switch (type) {
                case 'loans':
                    const loanProducts = Object.entries(productsByCategory)
                        .filter(([category, products]) => 
                            Array.isArray(products) && products.some(p => p.product && p.product.category === 'Loan')
                        );
                    
                    if (loanProducts.length > 0) {
                        content = 'Aggregated from:\n';
                        loanProducts.forEach(([category, products]) => {
                            const loanProducts = products.filter(p => p.product && p.product.category === 'Loan');
                            loanProducts.forEach(product => {
                                content += `• ${product.product.name}: ${formatCurrency(product.amount)} (${product.status})\n`;
                            });
                        });
                    } else {
                        content = 'No loan products found';
                    }
                    break;
                    
                case 'deposits':
                    const depositProducts = Object.entries(productsByCategory)
                        .filter(([category, products]) => 
                            Array.isArray(products) && products.some(p => p.product && p.product.category === 'Deposit')
                        );
                    
                    if (depositProducts.length > 0) {
                        content = 'Aggregated from:\n';
                        depositProducts.forEach(([category, products]) => {
                            const depositProducts = products.filter(p => p.product && p.product.category === 'Deposit');
                            depositProducts.forEach(product => {
                                content += `• ${product.product.name}: ${formatCurrency(product.amount)} (${product.status})\n`;
                            });
                        });
                    } else {
                        content = 'No deposit products found';
                    }
                    break;
                    
                case 'npl':
                    const nplProducts = Object.entries(productsByCategory)
                        .flatMap(([category, products]) => Array.isArray(products) ? products : [])
                        .filter(product => product.status === 'npl');
                    
                    if (nplProducts.length > 0) {
                        content = 'NPL Products:\n';
                        nplProducts.forEach(product => {
                            content += `• ${product.product ? product.product.name : 'Unknown Product'}: ${formatCurrency(product.amount)}\n`;
                        });
                    } else {
                        content = 'No NPL products found';
                    }
                    break;
                    
                default:
                    content = '';
            }
        } catch (error) {
            console.warn('Error generating tooltip content:', error);
            content = 'Data not available';
        }
        
        return content.trim();
    };

    // Custom tooltip implementation
    useEffect(() => {
        let currentTooltip = null;
        
        const showTooltip = (e) => {
            const element = e.target;
            const text = element.getAttribute('data-tooltip');
            if (!text) return;

            // Remove any existing tooltip
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

            // Fade in
            setTimeout(() => {
                if (currentTooltip === tooltip) {
                    tooltip.style.opacity = '1';
                }
            }, 10);

            const rect = element.getBoundingClientRect();
            
            // Position tooltip directly above the icon with small gap
            let top = rect.top - 8;
            let left = rect.left + (rect.width / 2);
            
            // Wait for tooltip to be rendered to get its dimensions
            setTimeout(() => {
                const tooltipRect = tooltip.getBoundingClientRect();
                
                // Adjust horizontal position to center above the icon
                left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                
                // Adjust if tooltip goes off screen horizontally
                if (left < 8) left = 8;
                if (left + tooltipRect.width > window.innerWidth - 8) {
                    left = window.innerWidth - tooltipRect.width - 8;
                }
                
                // Position vertically above the icon
                top = rect.top - tooltipRect.height - 8;
                
                // If tooltip would go above screen, position below the icon
                if (top < 8) {
                    top = rect.bottom + 8;
                }

                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
            }, 10);
        };

        const hideTooltip = () => {
            if (currentTooltip) {
                currentTooltip.style.opacity = '0';
                setTimeout(() => {
                    if (currentTooltip && document.body.contains(currentTooltip)) {
                        document.body.removeChild(currentTooltip);
                    }
                    currentTooltip = null;
                }, 200);
            }
        };

        const handleMouseEnter = (e) => {
            showTooltip(e);
        };

        const handleMouseLeave = () => {
            hideTooltip();
        };

        // Add event listeners to all tooltip elements
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', handleMouseEnter);
            element.addEventListener('mouseleave', handleMouseLeave);
            element.addEventListener('blur', handleMouseLeave);
        });

        // Global mouse leave handler to ensure tooltips are hidden
        document.addEventListener('mouseleave', hideTooltip);

        // Listen for tooltip reinitialize events from child components
        const handleTooltipReinitialize = () => {
            // Re-run tooltip setup for any new elements
            const newTooltipElements = document.querySelectorAll('[data-tooltip]');
            newTooltipElements.forEach(element => {
                if (!element.hasAttribute('data-tooltip-initialized')) {
                    element.addEventListener('mouseenter', handleMouseEnter);
                    element.addEventListener('mouseleave', handleMouseLeave);
                    element.addEventListener('blur', handleMouseLeave);
                    element.setAttribute('data-tooltip-initialized', 'true');
                }
            });
        };

        document.addEventListener('tooltipReinitialize', handleTooltipReinitialize);

        return () => {
            tooltipElements.forEach(element => {
                element.removeEventListener('mouseenter', handleMouseEnter);
                element.removeEventListener('mouseleave', handleMouseLeave);
                element.removeEventListener('blur', handleMouseLeave);
            });
            document.removeEventListener('mouseleave', hideTooltip);
            document.removeEventListener('tooltipReinitialize', handleTooltipReinitialize);
            
            // Clean up any remaining tooltip
            if (currentTooltip && document.body.contains(currentTooltip)) {
                document.body.removeChild(currentTooltip);
                currentTooltip = null;
            }
        };
    }, []);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const formatPercentage = (value) => {
        return `${value.toFixed(2)}%`;
    };

    const getRiskBadgeColor = (riskLevel) => {
        switch (riskLevel) {
            case 'Low': return 'bg-success';
            case 'Medium': return 'bg-warning';
            case 'High': return 'bg-danger';
            default: return 'bg-secondary';
        }
    };

    const updateMetrics = async () => {
        setIsUpdatingMetrics(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(`/customers/${customer.id}/update-metrics`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
            
            if (response.ok) {
                // Refresh profitability data
                const profitabilityResponse = await fetch(`/customers/${customer.id}/profitability`);
                const profitabilityData = await profitabilityResponse.json();
                setCurrentProfitability(profitabilityData.data);
            }
        } catch (error) {
            console.error('Error updating metrics:', error);
        } finally {
            setIsUpdatingMetrics(false);
        }
    };

    return (
        <AppLayout title={`Customer 360: ${customer.name}`}>
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/customers">Customers</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">{customer.name}</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Customer 360 View
                            </div>
                            <h2 className="page-title">
                                {customer.name}
                            </h2>
                            <div className="text-muted">
                                {customer.customer_id} • {customer.branch_code || 'No Branch'}
                            </div>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/customers" className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Customers
                                </Link>
                                <button 
                                    className="btn btn-primary"
                                    onClick={updateMetrics} 
                                    disabled={isUpdatingMetrics}
                                >
                                    <IconRefresh size={16} className={`me-1 ${isUpdatingMetrics ? 'spin' : ''}`} />
                                    {isUpdatingMetrics ? 'Updating...' : 'Update Metrics'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    {/* Customer Information */}
                    <div className="row row-deck row-cards mb-4">
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">Customer Information</h3>
                                </div>
                                <div className="card-body">
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="row">
                                                <div className="col-6">
                                                    <div className="mb-3">
                                                        <label className="form-label text-dark">Customer ID</label>
                                                        <div className="fw-bold text-dark">{customer.customer_id}</div>
                                                    </div>
                                                </div>
                                                <div className="col-6">
                                                    <div className="mb-3">
                                                        <label className="form-label text-dark d-flex align-items-center">
                                                            Branch
                                                            <IconInfoCircle 
                                                                size={12} 
                                                                className="ms-1 text-muted" 
                                                                data-tooltip="Branch code where this customer is primarily served"
                                                            />
                                                        </label>
                                                        <div className="fw-bold text-dark">{customer.branch_code || 'Not specified'}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="row">
                                                <div className="col-6">
                                                    <div className="mb-3">
                                                        <label className="form-label text-dark">Email</label>
                                                        <div className="fw-bold text-dark">{customer.email || 'Not provided'}</div>
                                                    </div>
                                                </div>
                                                <div className="col-6">
                                                    <div className="mb-3">
                                                        <label className="form-label text-dark">Phone</label>
                                                        <div className="fw-bold text-dark">{customer.phone || 'Not provided'}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            {customer.demographics && (
                                                <div>
                                                    <h5 className="mb-3 d-flex align-items-center">
                                                        Demographics
                                                        <IconInfoCircle 
                                                            size={14} 
                                                            className="ms-2 text-muted" 
                                                            data-tooltip="Customer demographic information collected during onboarding or profile updates"
                                                        />
                                                    </h5>
                                                    <div className="row">
                                                        {customer.demographics.age && (
                                                            <div className="col-6">
                                                                <div className="mb-2">
                                                                    <label className="form-label text-dark">Age</label>
                                                                    <div className="fw-bold text-dark">{customer.demographics.age}</div>
                                                                </div>
                                                            </div>
                                                        )}
                                                        {customer.demographics.gender && (
                                                            <div className="col-6">
                                                                <div className="mb-2">
                                                                    <label className="form-label text-dark">Gender</label>
                                                                    <div className="fw-bold text-dark">{customer.demographics.gender}</div>
                                                                </div>
                                                            </div>
                                                        )}
                                                        {customer.demographics.occupation && (
                                                            <div className="col-6">
                                                                <div className="mb-2">
                                                                    <label className="form-label text-dark">Occupation</label>
                                                                    <div className="fw-bold text-dark">{customer.demographics.occupation}</div>
                                                                </div>
                                                            </div>
                                                        )}
                                                        {customer.demographics.city && (
                                                            <div className="col-6">
                                                                <div className="mb-2">
                                                                    <label className="form-label text-dark">City</label>
                                                                    <div className="fw-bold text-dark">{customer.demographics.city}</div>
                                                                </div>
                                                            </div>
                                                        )}
                                                        {customer.demographics.address && (
                                                            <div className="col-12">
                                                                <div className="mb-2">
                                                                    <label className="form-label text-dark">Address</label>
                                                                    <div className="fw-bold text-dark">{customer.demographics.address}</div>
                                                                </div>
                                                            </div>
                                                        )}
                                                        {customer.demographics.country && (
                                                            <div className="col-6">
                                                                <div className="mb-2">
                                                                    <label className="form-label text-dark">Country</label>
                                                                    <div className="fw-bold text-dark">{customer.demographics.country}</div>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Customer Overview Cards */}
                    <div className="row row-deck row-cards mb-4">
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader d-flex align-items-center">
                                            Profitability
                                            <IconInfoCircle 
                                                size={14} 
                                                className="ms-1 text-muted" 
                                                data-tooltip={`Net profit calculated as: Interest Earned from Loans - Interest Paid on Deposits - Operating Costs\n\nInterest Earned: Loan amount × Interest rate (annual)\nInterest Paid: Deposit balance × Interest rate (annual)\nOperational Costs: Base cost per product + loan/deposit management + NPL risk costs\n\n${generateTooltipContent('loans')}\n\n${generateTooltipContent('deposits')}`}
                                            />
                                        </div>
                                        <div className="ms-auto">
                                            <div className="bg-green text-white avatar">
                                                <IconCurrencyDollar size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="d-flex align-items-baseline">
                                        <div className="h1 mb-0 me-2">
                                            {formatCurrency(currentProfitability.profitability)}
                                        </div>
                                        {currentProfitability.profitability >= 0 ? (
                                            <IconTrendingUp size={16} className="text-success" />
                                        ) : (
                                            <IconTrendingDown size={16} className="text-danger" />
                                        )}
                                    </div>
                                    <div className="text-muted">
                                        Margin: {formatPercentage(currentProfitability.profitability_margin)}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader d-flex align-items-center">
                                            Loans Outstanding
                                            <IconInfoCircle 
                                                size={14} 
                                                className="ms-1 text-muted" 
                                                data-tooltip={`Total amount of active loans currently owed by this customer across all loan products\n\n${generateTooltipContent('loans')}`}
                                            />
                                        </div>
                                        <div className="ms-auto">
                                            <div className="bg-blue text-white avatar">
                                                <IconCreditCard size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        {formatCurrency(currentProfitability.total_loans_outstanding)}
                                    </div>
                                    <div className="text-muted">
                                        NPL: {formatCurrency(currentProfitability.npl_exposure)}
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip={`Non-Performing Loans: Loans that are 90+ days past due or in default\n\n${generateTooltipContent('npl')}`}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader d-flex align-items-center">
                                            Total Deposits
                                            <IconInfoCircle 
                                                size={14} 
                                                className="ms-1 text-muted" 
                                                data-tooltip={`Total amount of deposits held by this customer across all deposit products (savings, current accounts, etc.)\n\n${generateTooltipContent('deposits')}`}
                                            />
                                        </div>
                                        <div className="ms-auto">
                                            <div className="bg-purple text-white avatar">
                                                <IconPigMoney size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        {formatCurrency(currentProfitability.total_deposits)}
                                    </div>
                                    <div className="text-muted">
                                        Interest Paid: {formatCurrency(currentProfitability.interest_paid)}
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Total interest paid to customer on their deposit balances"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader d-flex align-items-center">
                                            Risk Level
                                            <IconInfoCircle 
                                                size={14} 
                                                className="ms-1 text-muted" 
                                                data-tooltip="Risk assessment based on NPL ratio: Low (<2%), Medium (2-5%), High (>5%)"
                                            />
                                        </div>
                                        <div className="ms-auto">
                                            <div className="bg-orange text-white avatar">
                                                <IconAlertTriangle size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        <span className={`badge ${getRiskBadgeColor(currentProfitability.risk_level)} text-dark`}>
                                            {currentProfitability.risk_level}
                                        </span>
                                    </div>
                                    <div className="text-muted">
                                        NPL Ratio: {formatPercentage(currentProfitability.npl_ratio)}
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Non-Performing Loans as percentage of total loans outstanding"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Detailed Analysis Tabs */}
                    <div className="row row-deck row-cards">
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <ul className="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                        <li className="nav-item">
                                            <a href="#profitability" className="nav-link active" data-bs-toggle="tab">
                                                <IconChartBar size={16} className="me-1" />
                                                Profitability
                                            </a>
                                        </li>
                                        <li className="nav-item">
                                            <a href="#portfolio" className="nav-link" data-bs-toggle="tab">
                                                <IconChartPie size={16} className="me-1" />
                                                Portfolio
                                            </a>
                                        </li>
                                        <li className="nav-item">
                                            <a href="#risk" className="nav-link" data-bs-toggle="tab">
                                                <IconAlertTriangle size={16} className="me-1" />
                                                Risk Analysis
                                            </a>
                                        </li>
                                        <li className="nav-item">
                                            <a href="#insights" className="nav-link" data-bs-toggle="tab">
                                                <IconTrendingUp size={16} className="me-1" />
                                                Insights
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div className="card-body">
                                    <div className="tab-content">
                                        <div className="tab-pane active show" id="profitability">
                                            <CustomerProfitabilityWidget 
                                                customer={customer}
                                                profitability={currentProfitability}
                                            />
                                        </div>
                                        <div className="tab-pane" id="portfolio">
                                            <CustomerPortfolioWidget 
                                                customer={customer}
                                                productsByCategory={productsByCategory}
                                            />
                                        </div>
                                        <div className="tab-pane" id="risk">
                                            <CustomerRiskWidget 
                                                customer={customer}
                                                nplExposure={nplExposure}
                                                profitability={currentProfitability}
                                            />
                                        </div>
                                        <div className="tab-pane" id="insights">
                                            <CustomerInsightsWidget 
                                                customer={customer}
                                                profitability={currentProfitability}
                                                nplExposure={nplExposure}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}