import React from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconUsers, IconCurrencyDollar, IconTrendingUp, IconAlertTriangle } from '@tabler/icons-react';
import { useCurrency } from '@/hooks/useCurrency';
import GuidedWorkflow from '@/Components/GuidedWorkflow';

export default function Dashboard({ stats = [], recentCustomers = [], riskAlerts = [], portfolioPerformance = [], productBreakdown = [] }) {
    const { formatAmountWithCode } = useCurrency();
    
    // Icon mapping for dynamic stats
    const iconMap = {
        'users': IconUsers,
        'currency-dollar': IconCurrencyDollar,
        'trending-up': IconTrendingUp,
        'alert-triangle': IconAlertTriangle
    };

    return (
        <AppLayout title="Dashboard">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Overview
                            </div>
                            <h2 className="page-title">
                                Dashboard
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    {/* Guided Workflow for New Users */}
                    {(!stats || stats.length === 0 || stats.every(stat => stat.value === '0' || stat.value === 0)) && (
                        <div className="mb-4">
                            <GuidedWorkflow />
                        </div>
                    )}

                    {/* Stats Cards */}
                    <div className="row row-deck row-cards">
                        {stats.map((stat, index) => {
                            const Icon = iconMap[stat.icon] || IconUsers;
                            
                            // Special handling for Portfolio Value to show breakdown
                            if (stat.title === 'Portfolio Value' && stat.metadata?.breakdown) {
                                const breakdown = stat.metadata.breakdown;
                                return (
                                    <div key={index} className="col-sm-12 col-lg-6">
                                        <div className="card">
                                            <div className="card-body">
                                                <div className="d-flex align-items-center">
                                                    <div className="subheader">{stat.title}</div>
                                                    <div className="ms-auto">
                                                        <div className={`bg-${stat.color} text-white avatar`}>
                                                            <Icon size={24} />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="h1 mb-3">{stat.value}</div>
                                                <div className="d-flex mb-2">
                                                    <div className="text-muted">{stat.description}</div>
                                                    <div className="ms-auto">
                                                        <span className={`text-${stat.changeType === 'positive' ? 'green' : 'red'} d-inline-flex align-items-center lh-1`}>
                                                            {stat.change}
                                                            {stat.changeType === 'positive' ? ' ↗' : ' ↘'}
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                {/* Portfolio Breakdown */}
                                                <div className="row g-2 mt-3">
                                                    <div className="col-6">
                                                        <div className="text-muted small">Active Portfolio</div>
                                                        <div className="text-success fw-bold">{formatAmountWithCode(breakdown.active)}</div>
                                                    </div>
                                                    <div className="col-6">
                                                        <div className="text-muted small">NPL Amount</div>
                                                        <div className="text-danger fw-bold">{formatAmountWithCode(breakdown.npl)}</div>
                                                    </div>
                                                </div>
                                                
                                                <div className="mt-2">
                                                    <div className="d-flex justify-content-between small text-muted">
                                                        {breakdown.category === 'Loan' ? (
                                                            <>
                                                                <span>NPL Ratio: {breakdown.npl_percentage?.toFixed(2)}%</span>
                                                                <span>{((breakdown.npl / breakdown.total) * 100).toFixed(1)}% of total</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <span>Active Accounts: {breakdown.active_accounts || breakdown.total_accounts}</span>
                                                                <span>{(((breakdown.active_accounts || breakdown.total_accounts) / breakdown.total) * 100).toFixed(1)}% of total</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            }
                            
                            // Regular stat card
                            return (
                                <div key={index} className="col-sm-6 col-lg-3">
                                    <div className="card">
                                        <div className="card-body">
                                            <div className="d-flex align-items-center">
                                                <div className="subheader">{stat.title}</div>
                                                <div className="ms-auto">
                                                    <div className={`bg-${stat.color} text-white avatar`}>
                                                        <Icon size={24} />
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="h1 mb-3">{stat.value}</div>
                                            <div className="d-flex mb-2">
                                                <div className="text-muted">{stat.description}</div>
                                                <div className="ms-auto">
                                                    <span className={`text-${stat.changeType === 'positive' ? 'green' : 'red'} d-inline-flex align-items-center lh-1`}>
                                                        {stat.change}
                                                        {stat.changeType === 'positive' ? ' ↗' : ' ↘'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Portfolio Performance Charts - Only show if data exists */}
                    {(portfolioPerformance && portfolioPerformance.length > 0) || (productBreakdown && productBreakdown.length > 0) ? (
                        <div className="row row-deck row-cards">
                            {portfolioPerformance.length > 0 && (
                                <div className="col-md-6">
                                    <div className="card">
                                        <div className="card-header">
                                            <h3 className="card-title text-dark fw-bold">Portfolio Performance by Product</h3>
                                        </div>
                                        <div className="card-body">
                                            <div className="table-responsive">
                                                <table className="table table-vcenter">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Value</th>
                                                            <th>Accounts</th>
                                                            <th>NPL Rate</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {portfolioPerformance.map((product, index) => (
                                                            <tr key={index}>
                                                                <td>
                                                                    <div className="d-flex align-items-center">
                                                                        <div className="flex-fill">
                                                                            <div className="font-weight-medium">{product.product_name}</div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div className="text-muted">{formatAmountWithCode(product.total_value)}</div>
                                                                </td>
                                                                <td>
                                                                    <div className="text-muted">{product.active_accounts}</div>
                                                                </td>
                                                                <td>
                                                                    {product.category === 'Loan' && product.npl_rate !== undefined ? (
                                                                        <span className={`badge ${product.npl_rate > 5 ? 'bg-red' : product.npl_rate > 2 ? 'bg-yellow' : 'bg-green'}`}>
                                                                            {product.npl_rate.toFixed(1)}%
                                                                        </span>
                                                                    ) : (
                                                                        <span className="text-muted">N/A</span>
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                            
                            {productBreakdown.length > 0 && (
                                <div className="col-md-6">
                                    <div className="card">
                                        <div className="card-header">
                                            <h3 className="card-title text-dark fw-bold">Product Breakdown</h3>
                                        </div>
                                        <div className="card-body">
                                            <div className="table-responsive">
                                                <table className="table table-vcenter">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Category</th>
                                                            <th>Total Value</th>
                                                            <th>Avg Value</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {productBreakdown.map((product, index) => (
                                                            <tr key={index}>
                                                                <td>
                                                                    <div className="d-flex align-items-center">
                                                                        <div className="flex-fill">
                                                                            <div className="font-weight-medium">{product.name}</div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span className="badge bg-blue-lt">{product.category}</span>
                                                                </td>
                                                                <td>
                                                                    <div className="text-muted">{formatAmountWithCode(product.total_value)}</div>
                                                                </td>
                                                                <td>
                                                                    <div className="text-muted">{formatAmountWithCode(product.average_value)}</div>
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : null}

                    {/* Recent Activity - Only show if data exists */}
                    {(recentCustomers.length > 0 || riskAlerts.length > 0) && (
                        <div className="row row-deck row-cards">
                            {recentCustomers.length > 0 && (
                                <div className="col-md-6">
                                    <div className="card">
                                        <div className="card-header">
                                            <h3 className="card-title text-dark fw-bold">Recent Customers</h3>
                                        </div>
                                        <div className="card-body p-0">
                                            <div className="list-group list-group-flush">
                                                {recentCustomers.map((customer, index) => (
                                                    <div key={index} className="list-group-item">
                                                        <div className="row align-items-center">
                                                            <div className="col-auto">
                                                                <span className="avatar bg-primary text-white">{customer.initials}</span>
                                                            </div>
                                                            <div className="col text-truncate">
                                                                <Link href={`/customers/${customer.id}`} className="text-body d-block">{customer.name}</Link>
                                                                <div className="d-block text-muted text-truncate mt-n1">
                                                                    {customer.segment} Customer • {formatAmountWithCode(customer.portfolio_value)} portfolio
                                                                </div>
                                                            </div>
                                                            <div className="col-auto">
                                                                <Link href={`/customers/${customer.id}`} className="btn btn-primary btn-sm">View</Link>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                            
                            {riskAlerts.length > 0 && (
                                <div className="col-md-6">
                                    <div className="card">
                                        <div className="card-header">
                                            <h3 className="card-title text-dark fw-bold">Risk Alerts</h3>
                                        </div>
                                        <div className="card-body p-0">
                                            <div className="list-group list-group-flush">
                                                {riskAlerts.map((alert, index) => (
                                                    <div key={index} className="list-group-item">
                                                        <div className="row align-items-center">
                                                            <div className="col-auto">
                                                                <span className={`status-dot status-dot-animated ${alert.severity === 'high' ? 'bg-red' : 'bg-yellow'} d-block`}></span>
                                                            </div>
                                                            <div className="col text-truncate">
                                                                <Link href={alert.customer_id ? `/customers/${alert.customer_id}` : '/dashboards'} className="text-body d-block">{alert.type}</Link>
                                                                <div className="d-block text-muted text-truncate mt-n1">
                                                                    {alert.description}
                                                                    {alert.percentage && ` (${alert.percentage}%)`}
                                                                </div>
                                                            </div>
                                                            <div className="col-auto">
                                                                <Link href={alert.customer_id ? `/customers/${alert.customer_id}` : '/dashboards'} className="btn btn-primary btn-sm">Review</Link>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}