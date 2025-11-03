import './bootstrap';
import '../css/app.css';
import './utils/route';
import { initializeCsrf } from './Utils/csrf';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { router } from '@inertiajs/react';

// Initialize CSRF handling
initializeCsrf();

// Initialize CSRF handling
initializeCsrf();

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Scroll to top on page changes
router.on('end', () => {
    // Multiple attempts to ensure scroll reset works
    const scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'instant' });
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
    };
    
    // Immediate scroll
    scrollToTop();
    
    // Also try after a short delay in case of async DOM updates
    setTimeout(scrollToTop, 10);
    setTimeout(scrollToTop, 50);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#E85C2C',
    },
});