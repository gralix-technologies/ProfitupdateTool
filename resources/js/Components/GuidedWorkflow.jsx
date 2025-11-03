import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { 
    IconUsers, 
    IconBox, 
    IconMath, 
    IconDashboard, 
    IconUpload, 
    IconEye,
    IconCheck,
    IconArrowRight,
    IconInfoCircle
} from '@tabler/icons-react';

export default function GuidedWorkflow({ onStartWorkflow }) {
    const [currentStep, setCurrentStep] = useState(0);

    const workflowSteps = [
        {
            id: 'customers',
            title: 'Create Customer Listings',
            description: 'Set up your customer base with demographic and financial information',
            icon: IconUsers,
            route: '/customers/create',
            color: 'primary',
            details: [
                'Add customer basic information (name, email, phone)',
                'Set demographic data (age, gender, occupation, location)',
                'Configure financial metrics (loans, deposits, risk levels)',
                'Enable bulk upload for multiple customers'
            ]
        },
        {
            id: 'products',
            title: 'Create Product Listings',
            description: 'Define your financial products and their data fields',
            icon: IconBox,
            route: '/products/create',
            color: 'success',
            details: [
                'Define product categories (Loans, Deposits, Insurance, etc.)',
                'Create custom data fields for each product',
                'Set up field types and validation rules',
                'Configure portfolio value calculations'
            ]
        },
        {
            id: 'formulas',
            title: 'Create Product-Specific Formulas',
            description: 'Build calculation formulas based on your product data fields',
            icon: IconMath,
            route: '/formulas/create',
            color: 'warning',
            details: [
                'Link formulas to specific products',
                'Use available data fields in calculations',
                'Set return types (number, currency, percentage)',
                'Create templates for common calculations'
            ]
        },
        {
            id: 'dashboard',
            title: 'Create Dashboard',
            description: 'Build your analytics dashboard with widgets',
            icon: IconDashboard,
            route: '/dashboards/create',
            color: 'info',
            details: [
                'Choose from available widget types (KPI, Charts, Tables)',
                'Configure widgets using your formulas',
                'Set up data sources and formatting',
                'Arrange widgets in a responsive layout'
            ]
        },
        {
            id: 'ingest',
            title: 'Ingest Data',
            description: 'Upload your actual product data to populate the dashboard',
            icon: IconUpload,
            route: '/data-import',
            color: 'secondary',
            details: [
                'Upload CSV files with product data',
                'Map data fields to product definitions',
                'Validate data integrity and completeness',
                'Process and store data for analytics'
            ]
        },
        {
            id: 'view',
            title: 'View Dashboard',
            description: 'Analyze your data through interactive dashboards',
            icon: IconEye,
            route: '/dashboards',
            color: 'danger',
            details: [
                'View real-time analytics and KPIs',
                'Filter data by various criteria',
                'Export reports in multiple formats',
                'Monitor trends and performance metrics'
            ]
        }
    ];

    const getStepIcon = (step, index) => {
        const IconComponent = step.icon;
        const isCompleted = index < currentStep;
        const isCurrent = index === currentStep;
        
        return (
            <div className={`step-icon ${isCompleted ? 'completed' : isCurrent ? 'current' : 'pending'}`}>
                {isCompleted ? <IconCheck size={20} /> : <IconComponent size={20} />}
            </div>
        );
    };

    const handleStepClick = (index) => {
        setCurrentStep(index);
    };

    const handleStartWorkflow = () => {
        if (onStartWorkflow) {
            onStartWorkflow();
        }
        // Redirect to first step
        window.location.href = workflowSteps[0].route;
    };

    return (
        <div className="guided-workflow">
            <div className="card">
                <div className="card-header">
                    <h3 className="card-title">
                        <IconInfoCircle size={24} className="me-2" />
                        Portfolio Analytics Setup Guide
                    </h3>
                    <p className="text-muted mb-0">
                        Follow these steps to set up your complete analytics platform
                    </p>
                </div>
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-8">
                            <div className="workflow-steps">
                                {workflowSteps.map((step, index) => (
                                    <div 
                                        key={step.id}
                                        className={`workflow-step ${index === currentStep ? 'active' : ''}`}
                                        onClick={() => handleStepClick(index)}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="step-content">
                                            <div className="d-flex align-items-start">
                                                {getStepIcon(step, index)}
                                                <div className="step-details ms-3">
                                                    <h5 className="step-title">
                                                        {step.title}
                                                        <span className={`badge bg-${step.color} ms-2`}>
                                                            Step {index + 1}
                                                        </span>
                                                    </h5>
                                                    <p className="step-description">{step.description}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="col-md-4">
                            <div className="workflow-details">
                                <h6 className="mb-3">Current Step Details</h6>
                                <div className="current-step-info">
                                    <h5 className="text-primary mb-2">
                                        {workflowSteps[currentStep].title}
                                    </h5>
                                    <p className="text-muted mb-3">
                                        {workflowSteps[currentStep].description}
                                    </p>
                                    <ul className="list-unstyled">
                                        {workflowSteps[currentStep].details.map((detail, index) => (
                                            <li key={index} className="mb-2">
                                                <IconCheck size={16} className="text-success me-2" />
                                                {detail}
                                            </li>
                                        ))}
                                    </ul>
                                    <div className="mt-4">
                                        <Link 
                                            href={workflowSteps[currentStep].route}
                                            className="btn btn-primary w-100"
                                        >
                                            Start {workflowSteps[currentStep].title}
                                            <IconArrowRight size={16} className="ms-2" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="mt-4 text-center">
                        <button 
                            className="btn btn-success btn-lg"
                            onClick={handleStartWorkflow}
                        >
                            Start Complete Workflow
                            <IconArrowRight size={20} className="ms-2" />
                        </button>
                        <p className="text-muted mt-2 mb-0">
                            This will guide you through all steps in sequence
                        </p>
                    </div>
                </div>
            </div>
            
            <style jsx>{`
                .workflow-step {
                    padding: 1rem;
                    border-left: 3px solid #e9ecef;
                    margin-bottom: 1rem;
                    transition: all 0.3s ease;
                    border-radius: 0.375rem;
                }
                
                .workflow-step:hover {
                    background-color: #f8f9fa;
                    border-left-color: #0d6efd;
                }
                
                .workflow-step.active {
                    background-color: #e7f3ff;
                    border-left-color: #0d6efd;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .step-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                
                .step-icon.pending {
                    background-color: #e9ecef;
                    color: #6c757d;
                }
                
                .step-icon.current {
                    background-color: #0d6efd;
                    color: white;
                }
                
                .step-icon.completed {
                    background-color: #198754;
                    color: white;
                }
                
                .step-title {
                    margin-bottom: 0.5rem;
                    font-weight: 600;
                }
                
                .step-description {
                    color: #6c757d;
                    margin-bottom: 0;
                }
                
                .workflow-details {
                    background-color: #f8f9fa;
                    padding: 1.5rem;
                    border-radius: 0.375rem;
                    border: 1px solid #e9ecef;
                }
            `}</style>
        </div>
    );
}
