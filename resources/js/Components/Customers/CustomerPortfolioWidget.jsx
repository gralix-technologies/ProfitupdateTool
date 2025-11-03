import React from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid } from 'recharts';
import { 
    IconCreditCard, 
    IconPigMoney, 
    IconWallet, 
    IconArrowsExchange, 
    IconPackage 
} from '@tabler/icons-react';

export default function CustomerPortfolioWidget({ customer, productsByCategory }) {
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'ZMW',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const categoryIcons = {
        'Loan': IconCreditCard,
        'Deposit': IconPigMoney,
        'Account': IconWallet,
        'Transaction': IconArrowsExchange,
        'Other': IconPackage
    };

    const categoryColors = {
        'Loan': '#3B82F6',
        'Deposit': '#8B5CF6',
        'Account': '#10B981',
        'Transaction': '#F59E0B',
        'Other': '#6B7280'
    };

    // Prepare data for charts
    const portfolioData = Object.entries(productsByCategory)
        .filter(([category, data]) => data.count > 0)
        .map(([category, data]) => ({
            name: category,
            count: data.count,
            value: data.total_value,
            color: categoryColors[category]
        }));

    const totalPortfolioValue = portfolioData.reduce((sum, item) => sum + item.value, 0);
    const totalProductCount = portfolioData.reduce((sum, item) => sum + item.count, 0);

    return (
        <div className="row row-deck row-cards">
            {/* Portfolio Overview Cards */}
            <div className="col-lg-4">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Total Products</h3>
                    </div>
                    <div className="card-body text-center">
                        <IconPackage size={48} className="text-primary mb-3" />
                        <p className="text-3xl font-bold text-dark mb-0">{totalProductCount}</p>
                    </div>
                </div>
            </div>
            
            <div className="col-lg-4">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Portfolio Value</h3>
                    </div>
                    <div className="card-body text-center">
                        <IconPigMoney size={48} className="text-success mb-3" />
                        <p className="text-3xl font-bold text-dark mb-0">
                            {formatCurrency(totalPortfolioValue)}
                        </p>
                    </div>
                </div>
            </div>
            
            <div className="col-lg-4">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Categories</h3>
                    </div>
                    <div className="card-body text-center">
                        <IconArrowsExchange size={48} className="text-info mb-3" />
                        <p className="text-3xl font-bold text-dark mb-0">{portfolioData.length}</p>
                    </div>
                </div>
            </div>

            {/* Portfolio Distribution Chart */}
            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Portfolio Distribution by Value</h3>
                    </div>
                    <div className="card-body">
                        <div style={{ height: '300px' }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={portfolioData}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, value }) => `${name}: ${formatCurrency(value)}`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="value"
                                        labelStyle={{ 
                                            fill: '#212529', 
                                            fontSize: '12px', 
                                            fontWeight: '600' 
                                        }}
                                    >
                                        {portfolioData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip 
                                        formatter={(value) => [formatCurrency(value), 'Value']}
                                        labelStyle={{ color: '#212529', fontWeight: '600' }}
                                        contentStyle={{ 
                                            backgroundColor: '#ffffff', 
                                            border: '1px solid #dee2e6',
                                            borderRadius: '6px',
                                            color: '#212529'
                                        }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>
            </div>

            {/* Product Count Chart */}
            <div className="col-lg-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title text-dark fw-bold">Product Count by Category</h3>
                    </div>
                    <div className="card-body">
                        <div style={{ height: '300px' }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={portfolioData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis 
                                        dataKey="name" 
                                        fontSize={12}
                                        tick={{ fill: '#212529', fontSize: 11 }}
                                    />
                                    <YAxis 
                                        fontSize={12}
                                        tick={{ fill: '#212529', fontSize: 11 }}
                                    />
                                    <Tooltip 
                                        labelStyle={{ color: '#212529', fontWeight: '600' }}
                                        contentStyle={{ 
                                            backgroundColor: '#ffffff', 
                                            border: '1px solid #dee2e6',
                                            borderRadius: '6px',
                                            color: '#212529'
                                        }}
                                    />
                                    <Bar 
                                        dataKey="count" 
                                        fill="#3B82F6" 
                                        radius={[4, 4, 0, 0]} 
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>
            </div>

            {/* Detailed Product Categories */}
            {Object.entries(productsByCategory).map(([category, data]) => {
                const IconComponent = categoryIcons[category];
                const color = categoryColors[category];
                
                return (
                    <div key={category} className="col-lg-4">
                        <div className={`card ${data.count === 0 ? 'opacity-50' : ''}`}>
                            <div className="card-header">
                                <h3 className="card-title text-dark fw-bold d-flex align-items-center">
                                    <IconComponent size={20} className="me-2" style={{ color }} />
                                    {category}
                                    <span className="badge bg-secondary ms-auto">{data.count}</span>
                                </h3>
                            </div>
                            <div className="card-body">
                                <div className="row g-3">
                                    <div className="col-12">
                                        <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                            <span className="fw-medium text-dark">Total Value</span>
                                            <span className="fw-semibold text-dark">
                                                {formatCurrency(data.total_value)}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    {data.count > 0 && (
                                        <div className="col-12">
                                            <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                                <span className="fw-medium text-dark">Avg. Value</span>
                                                <span className="fw-semibold text-dark">
                                                    {formatCurrency(data.total_value / data.count)}
                                                </span>
                                            </div>
                                        </div>
                                    )}
                                    
                                    <div className="col-12">
                                        <div className="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                            <span className="fw-medium text-dark">Portfolio %</span>
                                            <span className="fw-semibold text-dark">
                                                {totalPortfolioValue > 0 
                                                    ? ((data.total_value / totalPortfolioValue) * 100).toFixed(1)
                                                    : 0
                                                }%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                {data.products && data.products.length > 0 && (
                                    <div className="mt-3 pt-3 border-top">
                                        <p className="text-sm fw-medium text-dark mb-2">Recent Products:</p>
                                        <div className="space-y-1">
                                            {data.products.slice(0, 3).map((product, index) => (
                                                <div key={index} className="text-xs text-muted d-flex justify-content-between">
                                                    <span className="text-truncate me-2">
                                                        {product.product?.name || 'Unknown Product'}
                                                    </span>
                                                    <span className="fw-medium text-dark">
                                                        {formatCurrency(
                                                            product.data?.amount || 
                                                            product.data?.balance || 
                                                            0
                                                        )}
                                                    </span>
                                                </div>
                                            ))}
                                            {data.products.length > 3 && (
                                                <p className="text-xs text-muted fst-italic">
                                                    +{data.products.length - 3} more products
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}