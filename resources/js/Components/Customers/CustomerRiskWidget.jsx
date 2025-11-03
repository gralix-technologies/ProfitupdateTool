import React from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { 
    IconAlertTriangle, 
    IconShield, 
    IconTrendingUp, 
    IconClock, 
    IconCurrencyDollar 
} from '@tabler/icons-react';

export default function CustomerRiskWidget({ customer, nplExposure, profitability }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const getRiskColor = (riskLevel) => {
        switch (riskLevel) {
            case 'Low': return 'text-green-600';
            case 'Medium': return 'text-yellow-600';
            case 'High': return 'text-red-600';
            default: return 'text-gray-600';
        }
    };

    const getRiskBadgeColor = (riskLevel) => {
        switch (riskLevel) {
            case 'Low': return 'bg-success text-white';
            case 'Medium': return 'bg-warning text-white';
            case 'High': return 'bg-danger text-white';
            default: return 'bg-secondary text-white';
        }
    };

    // Risk metrics data for chart
    const riskMetrics = [
        {
            name: 'Total Loans',
            value: nplExposure.total_loans_outstanding,
            color: '#3385ff'
        },
        {
            name: 'NPL Exposure',
            value: nplExposure.npl_exposure,
            color: '#e75c2d'
        },
        {
            name: 'Performing Loans',
            value: nplExposure.performing_loans,
            color: '#10B981'
        }
    ];

    // Risk assessment levels
    const getRiskAssessment = (nplRatio) => {
        if (nplRatio < 2) return { level: 'Low', description: 'Excellent credit quality', icon: IconShield, color: 'green' };
        if (nplRatio < 5) return { level: 'Medium', description: 'Acceptable credit quality', icon: IconAlertTriangle, color: 'yellow' };
        return { level: 'High', description: 'Requires attention', icon: IconAlertTriangle, color: 'red' };
    };

    const riskAssessment = getRiskAssessment(nplExposure.npl_ratio);

    return (
        <div className="row row-deck row-cards">
            {/* Risk Overview Cards */}
            <div className="col-lg-3">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Risk Level</h3>
                    </div>
                    <div className="card-body">
                        <div className="d-flex align-items-center">
                            <div className="flex-shrink-0">
                                <riskAssessment.icon size={32} className={`text-${riskAssessment.color === 'green' ? 'success' : riskAssessment.color === 'yellow' ? 'warning' : 'danger'}`} />
                            </div>
                            <div className="ms-3">
                                <span className={`badge ${getRiskBadgeColor(nplExposure.risk_level)}`}>
                                    {nplExposure.risk_level}
                                </span>
                                <p className="text-xs text-muted mt-1 mb-0">
                                    {riskAssessment.description}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="col-lg-3">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">NPL Exposure</h3>
                    </div>
                    <div className="card-body">
                        <div className="d-flex align-items-center">
                            <div className="flex-shrink-0">
                                <IconAlertTriangle size={32} className="text-danger" />
                            </div>
                            <div className="ms-3">
                                <p className="text-2xl fw-semibold text-danger mb-0">
                                    {formatCurrency(nplExposure.npl_exposure)}
                                </p>
                                <p className="text-xs text-muted mb-0">
                                    {nplExposure.npl_product_count} products
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="col-lg-3">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">NPL Ratio</h3>
                    </div>
                    <div className="card-body">
                        <div className="d-flex align-items-center">
                            <div className="flex-shrink-0">
                                <IconTrendingUp size={32} className="text-warning" />
                            </div>
                            <div className="ms-3">
                                <p className={`text-2xl fw-semibold mb-0 ${
                                    nplExposure.risk_level === 'Low' ? 'text-success' : 
                                    nplExposure.risk_level === 'Medium' ? 'text-warning' : 'text-danger'
                                }`}>
                                    {nplExposure.npl_ratio.toFixed(2)}%
                                </p>
                                <p className="text-xs text-muted mb-0">
                                    Industry avg: 3.5%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="col-lg-3">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Performing Loans</h3>
                    </div>
                    <div className="card-body">
                        <div className="d-flex align-items-center">
                            <div className="flex-shrink-0">
                                <IconCurrencyDollar size={32} className="text-success" />
                            </div>
                            <div className="ms-3">
                                <p className="text-2xl fw-semibold text-success mb-0">
                                    {formatCurrency(nplExposure.performing_loans)}
                                </p>
                                <p className="text-xs text-muted mb-0">
                                    {((nplExposure.performing_loans / nplExposure.total_loans_outstanding) * 100).toFixed(1)}% of total
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Risk Analysis Charts */}
            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Loan Portfolio Risk Breakdown</h3>
                    </div>
                    <div className="card-body">
                        <div style={{ height: '300px' }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={riskMetrics}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis 
                                        dataKey="name" 
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

            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Risk Indicators</h3>
                    </div>
                    <div className="card-body">
                        <div className="row g-3">
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <span className="fw-medium text-dark">NPL Ratio</span>
                                    <div className="text-end">
                                        <span className={`fw-semibold ${
                                            nplExposure.risk_level === 'Low' ? 'text-success' : 
                                            nplExposure.risk_level === 'Medium' ? 'text-warning' : 'text-danger'
                                        }`}>
                                            {nplExposure.npl_ratio.toFixed(2)}%
                                        </span>
                                        <p className="text-xs text-muted mb-0">Target: &lt; 2%</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <span className="fw-medium text-dark">Coverage Ratio</span>
                                    <div className="text-end">
                                        <span className="fw-semibold text-primary">
                                            {nplExposure.total_loans_outstanding > 0 
                                                ? ((nplExposure.performing_loans / nplExposure.total_loans_outstanding) * 100).toFixed(1)
                                                : 0
                                            }%
                                        </span>
                                        <p className="text-xs text-muted mb-0">Performing loans</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="col-12">
                                <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                    <span className="fw-medium text-dark">Risk-Adjusted Return</span>
                                    <div className="text-end">
                                        <span className={`fw-semibold ${
                                            profitability.profitability >= 0 ? 'text-success' : 'text-danger'
                                        }`}>
                                            {nplExposure.total_loans_outstanding > 0 
                                                ? ((profitability.profitability / nplExposure.total_loans_outstanding) * 100).toFixed(2)
                                                : 0
                                            }%
                                        </span>
                                        <p className="text-xs text-muted mb-0">ROA adjusted</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* NPL Products Detail */}
            {nplExposure.npl_products && nplExposure.npl_products.length > 0 && (
                <div className="col-12">
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title text-dark fw-bold d-flex align-items-center">
                                <IconAlertTriangle size={20} className="text-danger me-2" />
                                Non-Performing Loans Detail
                            </h3>
                        </div>
                        <div className="card-body">
                            <div className="table-responsive">
                                <table className="table table-striped">
                                    <thead>
                                        <tr className="bg-dark">
                                            <th className="text-white">Product</th>
                                            <th className="text-white">Amount</th>
                                            <th className="text-white">Days Past Due</th>
                                            <th className="text-white">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {nplExposure.npl_products.map((product, index) => (
                                            <tr key={index}>
                                                <td className="fw-medium text-dark">
                                                    {product.product_name}
                                                </td>
                                                <td className="text-dark">
                                                    {formatCurrency(product.amount)}
                                                </td>
                                                <td className="text-dark">
                                                    <div className="d-flex align-items-center">
                                                        <IconClock size={16} className="text-warning me-1" />
                                                        {product.days_past_due} days
                                                    </div>
                                                </td>
                                                <td>
                                                    <span className="badge bg-danger text-white">
                                                        {product.status.toUpperCase()}
                                                    </span>
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

            {/* Risk Recommendations */}
            <div className="col-12">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Risk Management Recommendations</h3>
                    </div>
                    <div className="card-body">
                        <div className="row g-3">
                            {nplExposure.npl_ratio > 5 && (
                                <div className="col-12">
                                    <div className="alert alert-danger d-flex align-items-center">
                                        <IconAlertTriangle size={20} className="me-2" />
                                        <div>
                                            <strong>High Risk Alert:</strong> NPL ratio exceeds 5%. Consider immediate review of credit terms and collection procedures.
                                        </div>
                                    </div>
                                </div>
                            )}
                            
                            {nplExposure.npl_ratio > 2 && nplExposure.npl_ratio <= 5 && (
                                <div className="col-12">
                                    <div className="alert alert-warning d-flex align-items-center">
                                        <IconAlertTriangle size={20} className="me-2" />
                                        <div>
                                            <strong>Medium Risk:</strong> NPL ratio is above target. Monitor closely and consider enhanced collection efforts.
                                        </div>
                                    </div>
                                </div>
                            )}
                            
                            {nplExposure.npl_ratio <= 2 && (
                                <div className="col-12">
                                    <div className="alert alert-success d-flex align-items-center">
                                        <IconShield size={20} className="me-2" />
                                        <div>
                                            <strong>Low Risk:</strong> Customer maintains excellent credit quality. Consider for premium product offerings.
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="col-md-6">
                                <div className="p-3 bg-primary bg-opacity-10 rounded">
                                    <h4 className="fw-semibold text-primary mb-2">Recommended Actions</h4>
                                    <ul className="text-sm text-dark mb-0">
                                        {nplExposure.npl_ratio > 5 && (
                                            <>
                                                <li>• Immediate credit review</li>
                                                <li>• Enhanced monitoring</li>
                                                <li>• Collection acceleration</li>
                                            </>
                                        )}
                                        {nplExposure.npl_ratio > 2 && nplExposure.npl_ratio <= 5 && (
                                            <>
                                                <li>• Regular portfolio review</li>
                                                <li>• Proactive customer contact</li>
                                                <li>• Payment plan options</li>
                                            </>
                                        )}
                                        {nplExposure.npl_ratio <= 2 && (
                                            <>
                                                <li>• Maintain current terms</li>
                                                <li>• Consider credit increases</li>
                                                <li>• Cross-sell opportunities</li>
                                            </>
                                        )}
                                    </ul>
                                </div>
                            </div>
                            
                            <div className="col-md-6">
                                <div className="p-3 bg-info bg-opacity-10 rounded">
                                    <h4 className="fw-semibold text-info mb-2">Monitoring Metrics</h4>
                                    <ul className="text-sm text-dark mb-0">
                                        <li>• Monthly NPL ratio tracking</li>
                                        <li>• Payment behavior analysis</li>
                                        <li>• Early warning indicators</li>
                                        <li>• Profitability impact assessment</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}