import React from 'react';
import { RadarChart, PolarGrid, PolarAngleAxis, PolarRadiusAxis, Radar, ResponsiveContainer } from 'recharts';
import { 
    IconTrendingUp, 
    IconTrendingDown, 
    IconTarget, 
    IconAward, 
    IconAlertTriangle, 
    IconBulb 
} from '@tabler/icons-react';

export default function CustomerInsightsWidget({ customer, profitability, nplExposure }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    // Customer scoring metrics (0-100 scale) - Dynamic based on actual data
    const getCustomerScore = () => {
        // Calculate profitability score based on actual profitability data
        const profitabilityScore = profitability.profitability > 0 
            ? Math.min(100, Math.max(0, (profitability.profitability / Math.max(profitability.profitability, 10000)) * 100))
            : 0;
        
        // Risk score based on actual risk level
        const riskScore = profitability.risk_level === 'Low' ? 90 : profitability.risk_level === 'Medium' ? 60 : 30;
        
        // Portfolio score based on actual portfolio size
        const totalPortfolio = profitability.total_loans_outstanding + profitability.total_deposits;
        const portfolioScore = totalPortfolio > 0 
            ? Math.min(100, (totalPortfolio / Math.max(totalPortfolio, 10000)) * 100)
            : 0;
        
        // Loyalty score based on customer activity and tenure
        const loyaltyScore = customer.is_active ? 
            (customer.tenure_years > 5 ? 90 : customer.tenure_years > 2 ? 70 : 50) : 20;
        
        return {
            profitability: profitabilityScore,
            risk: riskScore,
            portfolio: portfolioScore,
            loyalty: loyaltyScore,
            overall: (profitabilityScore + riskScore + portfolioScore + loyaltyScore) / 4
        };
    };

    const customerScore = getCustomerScore();

    // Radar chart data
    const radarData = [
        { subject: 'Profitability', A: customerScore.profitability, fullMark: 100 },
        { subject: 'Risk Management', A: customerScore.risk, fullMark: 100 },
        { subject: 'Portfolio Size', A: customerScore.portfolio, fullMark: 100 },
        { subject: 'Loyalty', A: customerScore.loyalty, fullMark: 100 },
    ];

    // Generate insights based on customer data
    const generateInsights = () => {
        const insights = [];

        // Profitability insights - Dynamic thresholds based on actual data
        const profitabilityThreshold = Math.max(5000, profitability.profitability * 0.8); // 80% of current profitability or ZMW5000 minimum
        
        if (profitability.profitability > profitabilityThreshold) {
            insights.push({
                type: 'positive',
                icon: IconAward,
                title: 'High Value Customer',
                description: `This customer generates ${formatCurrency(profitability.profitability)} in annual profitability, placing them in the top tier.`,
                recommendation: 'Consider offering premium services and exclusive benefits to retain this valuable relationship.'
            });
        } else if (profitability.profitability < 0) {
            insights.push({
                type: 'negative',
                icon: IconAlertTriangle,
                title: 'Unprofitable Relationship',
                description: `Customer is currently generating a loss of ${formatCurrency(Math.abs(profitability.profitability))}.`,
                recommendation: 'Review pricing structure and consider fee adjustments or service optimization.'
            });
        } else if (profitability.profitability < (profitabilityThreshold * 0.3)) {
            insights.push({
                type: 'warning',
                icon: IconAlertTriangle,
                title: 'Low Profitability',
                description: `Customer profitability of ${formatCurrency(profitability.profitability)} is below optimal levels.`,
                recommendation: 'Consider upselling additional services or reviewing fee structure to improve profitability.'
            });
        }

        // Risk insights
        if (profitability.risk_level === 'High') {
            insights.push({
                type: 'warning',
                icon: IconAlertTriangle,
                title: 'High Risk Profile',
                description: `NPL ratio of ${profitability.npl_ratio.toFixed(2)}% indicates elevated credit risk.`,
                recommendation: 'Implement enhanced monitoring and consider risk mitigation strategies.'
            });
        } else if (profitability.risk_level === 'Low') {
            insights.push({
                type: 'positive',
                icon: IconTarget,
                title: 'Excellent Credit Quality',
                description: `Low NPL ratio of ${profitability.npl_ratio.toFixed(2)}% demonstrates strong creditworthiness.`,
                recommendation: 'Ideal candidate for credit limit increases and new product offerings.'
            });
        }

        // Portfolio insights
        const loanToDepositRatio = profitability.total_deposits > 0 
            ? (profitability.total_loans_outstanding / profitability.total_deposits) * 100 
            : 0;

        if (loanToDepositRatio > 200) {
            insights.push({
                type: 'opportunity',
                icon: IconBulb,
                title: 'Deposit Growth Opportunity',
                description: `High loan-to-deposit ratio of ${loanToDepositRatio.toFixed(0)}% suggests potential for deposit growth.`,
                recommendation: 'Target with deposit products and savings incentives to improve funding mix.'
            });
        } else if (loanToDepositRatio < 50 && profitability.total_deposits > 10000) {
            insights.push({
                type: 'opportunity',
                icon: IconBulb,
                title: 'Lending Opportunity',
                description: `Strong deposit base with low lending utilization presents growth opportunity.`,
                recommendation: 'Proactively offer loan products and credit facilities.'
            });
        }

        // Profitability margin insights
        if (profitability.profitability_margin > 15) {
            insights.push({
                type: 'positive',
                icon: IconTrendingUp,
                title: 'High Margin Customer',
                description: `Profitability margin of ${profitability.profitability_margin.toFixed(2)}% exceeds industry benchmarks.`,
                recommendation: 'Maintain current service levels and explore additional revenue opportunities.'
            });
        }

        return insights.slice(0, 4); // Limit to 4 insights
    };

    const insights = generateInsights();

    const getInsightBadgeColor = (type) => {
        switch (type) {
            case 'positive': return 'alert-success';
            case 'negative': return 'alert-danger';
            case 'warning': return 'alert-warning';
            case 'opportunity': return 'alert-info';
            default: return 'alert-secondary';
        }
    };

    const getInsightIconColor = (type) => {
        switch (type) {
            case 'positive': return 'text-success';
            case 'negative': return 'text-danger';
            case 'warning': return 'text-warning';
            case 'opportunity': return 'text-info';
            default: return 'text-secondary';
        }
    };

    return (
        <div className="row row-deck row-cards">
            {/* Customer Score Overview */}
            <div className="col-lg-2">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Overall Score</h3>
                    </div>
                    <div className="card-body text-center">
                        <div className="text-3xl fw-bold text-primary mb-2">
                            {customerScore.overall.toFixed(0)}
                        </div>
                        <span className={`badge ${
                            customerScore.overall >= 80 ? 'bg-success text-white' :
                            customerScore.overall >= 60 ? 'bg-warning text-white' :
                            'bg-danger text-white'
                        }`}>
                            {customerScore.overall >= 80 ? 'Excellent' :
                             customerScore.overall >= 60 ? 'Good' : 'Needs Attention'}
                        </span>
                    </div>
                </div>
            </div>

            <div className="col-lg-2">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Profitability</h3>
                    </div>
                    <div className="card-body text-center">
                        <div className="text-2xl fw-bold text-success mb-0">
                            {customerScore.profitability.toFixed(0)}
                        </div>
                    </div>
                </div>
            </div>

            <div className="col-lg-2">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Risk Score</h3>
                    </div>
                    <div className="card-body text-center">
                        <div className="text-2xl fw-bold text-primary mb-0">
                            {customerScore.risk.toFixed(0)}
                        </div>
                    </div>
                </div>
            </div>

            <div className="col-lg-3">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Portfolio</h3>
                    </div>
                    <div className="card-body text-center">
                        <div className="text-2xl fw-bold text-info mb-0">
                            {customerScore.portfolio.toFixed(0)}
                        </div>
                    </div>
                </div>
            </div>

            <div className="col-lg-3">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Loyalty</h3>
                    </div>
                    <div className="card-body text-center">
                        <div className="text-2xl fw-bold text-warning mb-0">
                            {customerScore.loyalty.toFixed(0)}
                        </div>
                    </div>
                </div>
            </div>

            {/* Customer Performance Radar */}
            <div className="col-12">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Customer Performance Profile</h3>
                    </div>
                    <div className="card-body">
                        <div style={{ height: '400px' }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <RadarChart data={radarData}>
                                    <PolarGrid />
                                    <PolarAngleAxis dataKey="subject" />
                                    <PolarRadiusAxis angle={90} domain={[0, 100]} />
                                    <Radar
                                        name="Customer Score"
                                        dataKey="A"
                                        stroke="#3B82F6"
                                        fill="#3B82F6"
                                        fillOpacity={0.3}
                                        strokeWidth={2}
                                    />
                                </RadarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>
            </div>

            {/* Key Insights */}
            <div className="col-12">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold d-flex align-items-center">
                            <IconBulb size={20} className="text-warning me-2" />
                            Key Insights & Recommendations
                        </h3>
                    </div>
                    <div className="card-body">
                        <div className="row g-3">
                            {insights.map((insight, index) => {
                                const IconComponent = insight.icon;
                                return (
                                    <div key={index} className="col-md-6">
                                        <div className={`alert ${getInsightBadgeColor(insight.type)} d-flex align-items-start`}>
                                            <IconComponent size={20} className={`me-2 mt-1 ${getInsightIconColor(insight.type)}`} />
                                            <div className="flex-grow-1">
                                                <h4 className={`fw-semibold mb-2 ${getInsightIconColor(insight.type)}`}>
                                                    {insight.title}
                                                </h4>
                                                <p className="text-sm mb-3 text-dark">
                                                    {insight.description}
                                                </p>
                                                <div className="bg-light bg-opacity-75 p-2 rounded text-xs">
                                                    <strong className="text-dark">Recommendation:</strong> <span className="text-dark">{insight.recommendation}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>

            {/* Performance Metrics Summary */}
            <div className="col-md-4">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Revenue Impact</h3>
                    </div>
                    <div className="card-body text-center">
                        <IconTrendingUp size={48} className="text-success mb-3" />
                        <h3 className="fw-semibold text-success mb-1">Revenue Impact</h3>
                        <p className="text-2xl fw-bold text-success mb-0">
                            {formatCurrency(profitability.interest_earned)}
                        </p>
                        <p className="text-sm text-muted mb-0">Annual interest earned</p>
                    </div>
                </div>
            </div>

            <div className="col-md-4">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Efficiency Ratio</h3>
                    </div>
                    <div className="card-body text-center">
                        <IconTarget size={48} className="text-primary mb-3" />
                        <h3 className="fw-semibold text-primary mb-1">Efficiency Ratio</h3>
                        <p className="text-2xl fw-bold text-primary mb-0">
                            {profitability.interest_earned > 0 
                                ? ((profitability.operational_costs / profitability.interest_earned) * 100).toFixed(1)
                                : 0
                            }%
                        </p>
                        <p className="text-sm text-muted mb-0">Cost-to-income ratio</p>
                    </div>
                </div>
            </div>

            <div className="col-md-4">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Customer Value</h3>
                    </div>
                    <div className="card-body text-center">
                        <IconAward size={48} className="text-info mb-3" />
                        <h3 className="fw-semibold text-info mb-1">Customer Value</h3>
                        <p className="text-2xl fw-bold text-info mb-0">
                            {formatCurrency(profitability.total_loans_outstanding + profitability.total_deposits)}
                        </p>
                        <p className="text-sm text-muted mb-0">Total relationship value</p>
                    </div>
                </div>
            </div>
        </div>
    );
}