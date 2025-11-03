import React, { useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';
import { IconInfoCircle } from '@tabler/icons-react';

export default function CustomerProfitabilityWidget({ customer, profitability }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    // Ensure tooltips are initialized when component mounts
    useEffect(() => {
        // Trigger tooltip initialization by dispatching a custom event
        // The main Show component will handle the tooltip setup
        const event = new CustomEvent('tooltipReinitialize');
        document.dispatchEvent(event);
    }, []);


    const profitabilityBreakdown = [
        {
            name: 'Interest Earned',
            value: profitability.interest_earned,
            color: '#10B981'
        },
        {
            name: 'Interest Paid',
            value: -profitability.interest_paid,
            color: '#EF4444'
        },
        {
            name: 'Operational Costs',
            value: -profitability.operational_costs,
            color: '#F59E0B'
        },
        {
            name: 'Net Profitability',
            value: profitability.profitability,
            color: profitability.profitability >= 0 ? '#10B981' : '#EF4444'
        }
    ];

    const revenueBreakdown = [
        {
            name: 'Interest Earned',
            value: profitability.interest_earned,
            percentage: ((profitability.interest_earned / (profitability.interest_earned + profitability.interest_paid + profitability.operational_costs)) * 100).toFixed(1)
        },
        {
            name: 'Interest Paid',
            value: profitability.interest_paid,
            percentage: ((profitability.interest_paid / (profitability.interest_earned + profitability.interest_paid + profitability.operational_costs)) * 100).toFixed(1)
        },
        {
            name: 'Operational Costs',
            value: profitability.operational_costs,
            percentage: ((profitability.operational_costs / (profitability.interest_earned + profitability.interest_paid + profitability.operational_costs)) * 100).toFixed(1)
        }
    ];

    const COLORS = ['#10B981', '#EF4444', '#F59E0B'];

    return (
        <div className="row row-deck row-cards">
            {/* Profitability Breakdown Chart */}
            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold d-flex align-items-center">
                            Profitability Breakdown
                            <IconInfoCircle 
                                size={16} 
                                className="ms-2 text-muted" 
                                data-tooltip="Shows the breakdown of revenue and costs that contribute to net profitability:\n\n• Interest Earned: Loan amount × Interest rate (annual)\n• Interest Paid: Deposit balance × Interest rate (annual, default 2%)\n• Operational Costs: Base cost (50 per product) + loan management (150) + deposit maintenance (25) + NPL risk costs (0.1% of NPL exposure)\n• Net Profitability: Interest Earned - Interest Paid - Operational Costs"
                            />
                        </h3>
                    </div>
                    <div className="card-body">
                        <div style={{ height: '300px' }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={profitabilityBreakdown}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis 
                                        dataKey="name" 
                                        angle={-45}
                                        textAnchor="end"
                                        height={80}
                                        fontSize={12}
                                        tick={{ fill: '#212529', fontSize: 11 }}
                                    />
                                    <YAxis 
                                        tickFormatter={(value) => formatCurrency(value)}
                                        fontSize={12}
                                        tick={{ fill: '#212529', fontSize: 11 }}
                                    />
                                    <Tooltip 
                                        formatter={(value) => [formatCurrency(value), 'Amount']}
                                        labelStyle={{ color: '#212529', fontWeight: '600' }}
                                        contentStyle={{ 
                                            backgroundColor: '#ffffff', 
                                            border: '1px solid #dee2e6',
                                            borderRadius: '6px',
                                            color: '#212529'
                                        }}
                                    />
                                    <Bar 
                                        dataKey="value" 
                                        fill={(entry) => entry.color}
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>
            </div>

            {/* Revenue Distribution Pie Chart */}
            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold d-flex align-items-center">
                            Revenue & Cost Distribution
                            <IconInfoCircle 
                                size={16} 
                                className="ms-2 text-muted" 
                                data-tooltip="Shows the percentage distribution of total revenue and costs. Interest Earned represents revenue from loans, Interest Paid represents costs for deposits, and Operational Costs represent other business expenses."
                            />
                        </h3>
                    </div>
                    <div className="card-body">
                        <div style={{ height: '300px', padding: '10px' }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart margin={{ top: 10, right: 20, bottom: 70, left: 20 }}>
                                    <Pie
                                        data={revenueBreakdown}
                                        cx="50%"
                                        cy="40%"
                                        labelLine={false}
                                        label={(entry) => {
                                            const total = revenueBreakdown.reduce((sum, item) => sum + item.value, 0);
                                            const percentage = ((entry.value / total) * 100).toFixed(1);
                                            // Only show label if percentage is >= 5% to avoid overlapping
                                            return parseFloat(percentage) >= 5 ? `${entry.name}: ${percentage}%` : '';
                                        }}
                                        outerRadius={65}
                                        fill="#8884d8"
                                        dataKey="value"
                                        labelStyle={{ 
                                            fill: '#212529', 
                                            fontSize: '11px', 
                                            fontWeight: '600' 
                                        }}
                                    >
                                        {revenueBreakdown.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip 
                                        formatter={(value, name) => {
                                            const total = revenueBreakdown.reduce((sum, item) => sum + item.value, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return [`${formatCurrency(value)} (${percentage}%)`, 'Amount'];
                                        }}
                                        labelStyle={{ color: '#212529', fontWeight: '600' }}
                                        contentStyle={{ 
                                            backgroundColor: '#ffffff', 
                                            border: '1px solid #dee2e6',
                                            borderRadius: '6px',
                                            color: '#212529'
                                        }}
                                    />
                                    <Legend 
                                        verticalAlign="bottom" 
                                        height={60}
                                        iconType="circle"
                                        formatter={(value, entry) => {
                                            const item = revenueBreakdown.find(r => r.name === value);
                                            if (item) {
                                                const total = revenueBreakdown.reduce((sum, i) => sum + i.value, 0);
                                                const percentage = ((item.value / total) * 100).toFixed(1);
                                                return `${value} (${percentage}%)`;
                                            }
                                            return value;
                                        }}
                                        wrapperStyle={{
                                            paddingTop: '10px',
                                            fontSize: '12px',
                                            fontWeight: '500'
                                        }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>
            </div>

            {/* Profitability Metrics */}
            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold d-flex align-items-center">
                            Key Profitability Metrics
                            <IconInfoCircle 
                                size={16} 
                                className="ms-2 text-muted" 
                                data-tooltip="Detailed breakdown of profitability components: Interest Earned (revenue from loans), Interest Paid (costs for deposits), Operational Costs (other expenses), Net Profitability (final result), and Profitability Margin (profit as percentage of total revenue)."
                            />
                        </h3>
                    </div>
                    <div className="card-body">
                        <div className="row g-3">
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <span className="fw-medium text-dark d-flex align-items-center">
                                        Interest Earned
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Annual interest revenue: Sum of (Loan amount × Interest rate) for all loan products"
                                        />
                                    </span>
                                    <span className="text-success fw-semibold">
                                        {formatCurrency(profitability.interest_earned)}
                                    </span>
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <span className="fw-medium text-dark d-flex align-items-center">
                                        Interest Paid
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Annual interest expense: Sum of (Deposit balance × Interest rate) for all deposit accounts"
                                        />
                                    </span>
                                    <span className="text-danger fw-semibold">
                                        -{formatCurrency(profitability.interest_paid)}
                                    </span>
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <span className="fw-medium text-dark d-flex align-items-center">
                                        Operational Costs
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Annual operational costs: Base cost (50 per product) + loan management (150) + deposit maintenance (25) + NPL risk costs (0.1% of NPL exposure)"
                                        />
                                    </span>
                                    <span className="text-warning fw-semibold">
                                        -{formatCurrency(profitability.operational_costs)}
                                    </span>
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-primary bg-opacity-10 rounded border border-primary">
                                    <span className="fw-bold text-dark d-flex align-items-center">
                                        Net Profitability
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Final profit/loss calculation: Interest Earned - Interest Paid - Operational Costs"
                                        />
                                    </span>
                                    <span className={`fw-bold fs-5 ${
                                        profitability.profitability >= 0 ? 'text-success' : 'text-danger'
                                    }`}>
                                        {formatCurrency(profitability.profitability)}
                                    </span>
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <span className="fw-medium text-dark d-flex align-items-center">
                                        Profitability Margin
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Profitability margin: (Net Profitability / Interest Earned) × 100 - shows profit as percentage of interest revenue"
                                        />
                                    </span>
                                    <span className={`fw-semibold ${
                                        profitability.profitability_margin >= 0 ? 'text-success' : 'text-danger'
                                    }`}>
                                        {profitability.profitability_margin.toFixed(2)}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Customer Financial Summary */}
            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold d-flex align-items-center">
                            Financial Summary
                            <IconInfoCircle 
                                size={16} 
                                className="ms-2 text-muted" 
                                data-tooltip="Overview of customer's financial position: Total Loans (outstanding loan balances), Total Deposits (deposit account balances), NPL Exposure (non-performing loan amounts), NPL Ratio (percentage of loans that are non-performing), and Risk Level (overall risk assessment)."
                            />
                        </h3>
                    </div>
                    <div className="card-body">
                        <div className="row g-3">
                            <div className="col-6">
                                <div className="text-center p-3 bg-primary bg-opacity-10 rounded">
                                    <p className="text-muted mb-1 d-flex align-items-center justify-content-center">
                                        Total Loans
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Total outstanding loan balances: Sum of 'amount' or 'outstanding_balance' fields from all products with category 'Loan'"
                                        />
                                    </p>
                                    <p className="h4 fw-bold text-primary mb-0">
                                        {formatCurrency(profitability.total_loans_outstanding)}
                                    </p>
                                </div>
                            </div>
                            <div className="col-6">
                                <div className="text-center p-3 bg-info bg-opacity-10 rounded">
                                    <p className="text-muted mb-1 d-flex align-items-center justify-content-center">
                                        Total Deposits
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Total deposit balances: Sum of 'amount' or 'balance' fields from all products with category 'Deposit' or 'Account'"
                                        />
                                    </p>
                                    <p className="h4 fw-bold text-info mb-0">
                                        {formatCurrency(profitability.total_deposits)}
                                    </p>
                                </div>
                            </div>
                            <div className="col-6">
                                <div className="text-center p-3 bg-danger bg-opacity-10 rounded">
                                    <p className="text-muted mb-1 d-flex align-items-center justify-content-center">
                                        NPL Exposure
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Total amount of non-performing loans: Loans with status 'npl' or 'non_performing', or loans with 90+ days past due"
                                        />
                                    </p>
                                    <p className="h4 fw-bold text-dark mb-0">
                                        {formatCurrency(profitability.npl_exposure)}
                                    </p>
                                </div>
                            </div>
                            <div className="col-6">
                                <div className="text-center p-3 bg-warning bg-opacity-10 rounded">
                                    <p className="text-muted mb-1 d-flex align-items-center justify-content-center">
                                        NPL Ratio
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="NPL Ratio: (NPL Exposure / Total Loans Outstanding) × 100 - indicates the percentage of loan portfolio that is non-performing"
                                        />
                                    </p>
                                    <p className="h4 fw-bold text-dark mb-0">
                                        {profitability.npl_ratio.toFixed(2)}%
                                    </p>
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="text-center p-3 bg-success bg-opacity-10 rounded">
                                    <p className="text-muted mb-1 d-flex align-items-center justify-content-center">
                                        Risk Level
                                        <IconInfoCircle 
                                            size={12} 
                                            className="ms-1 text-muted" 
                                            data-tooltip="Risk assessment based on NPL ratio: Low (<2%), Medium (2-5%), High (>5%)"
                                        />
                                    </p>
                                    <p className={`h4 fw-bold mb-0 ${
                                        profitability.risk_level === 'Low' ? 'text-dark' :
                                        profitability.risk_level === 'Medium' ? 'text-dark' : 'text-dark'
                                    }`}>
                                        {profitability.risk_level}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}