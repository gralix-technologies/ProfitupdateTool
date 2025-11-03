import React, { useState } from 'react';

export const Tabs = ({ children, defaultValue, className = '' }) => {
    const [activeTab, setActiveTab] = useState(defaultValue);

    return (
        <div className={`tabs ${className}`}>
            {React.Children.map(children, child => 
                React.cloneElement(child, { activeTab, setActiveTab })
            )}
        </div>
    );
};

export const TabsList = ({ children, activeTab, setActiveTab, className = '' }) => {
    return (
        <ul className={`nav nav-tabs ${className}`}>
            {React.Children.map(children, child => 
                React.cloneElement(child, { activeTab, setActiveTab })
            )}
        </ul>
    );
};

export const TabsTrigger = ({ value, children, activeTab, setActiveTab, className = '' }) => {
    return (
        <li className="nav-item">
            <button
                className={`nav-link ${activeTab === value ? 'active' : ''} ${className}`}
                onClick={() => setActiveTab(value)}
            >
                {children}
            </button>
        </li>
    );
};

export const TabsContent = ({ value, children, activeTab }) => {
    if (activeTab !== value) return null;
    
    return (
        <div className="tab-content">
            <div className="tab-pane active">
                {children}
            </div>
        </div>
    );
};