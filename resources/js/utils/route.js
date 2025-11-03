// Simple route helper for client-side routing
export const route = (name, params = null) => {
    const routes = {
        'login': '/login',
        'register': '/register',
        'dashboard': '/',
        'dashboard.index': '/',
        'products.index': '/products',
        'products.create': '/products/create',
        'products.show': (id) => `/products/${id}`,
        'products.edit': (id) => `/products/${id}/edit`,
        'customers.index': '/customers',
        'customers.show': (id) => `/customers/${id}`,
        'customers.profitability': (id) => `/api/customers/${id}/profitability`,
        'customers.update-metrics': (id) => `/api/customers/${id}/update-metrics`,
        'dashboards.index': '/dashboards',
        'dashboards.create': '/dashboards/create',
        'dashboards.show': (id) => `/dashboards/${id}`,
        'dashboards.edit': (id) => `/dashboards/${id}/edit`,
        'formulas.index': '/formulas',
        'formulas.create': '/formulas/create',
        'formulas.show': (id) => `/formulas/${id}`,
        'formulas.edit': (id) => `/formulas/${id}/edit`,
        'formulas.destroy': (id) => `/formulas/${id}`,
        'formulas.duplicate': (id) => `/formulas/${id}/duplicate`,
        'data-ingestion.index': '/data-ingestion',
        'queue-monitor.index': '/queue-monitor'
    };

    const routeFunction = routes[name];
    
    if (typeof routeFunction === 'function' && params !== null) {
        return routeFunction(params);
    }
    
    if (typeof routeFunction === 'string') {
        return routeFunction;
    }
    
    return '/';
};

// Make it globally available
if (typeof window !== 'undefined') {
    window.route = route;
}