import { useState, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { 
    IconDashboard, 
    IconUsers, 
    IconChartBar, 
    IconDatabase, 
    IconSettings,
    IconLogout,
    IconUser,
    IconEdit,
    IconLock,
    IconUserCheck,
    IconShield,
    IconFileText,
    IconClipboardList,
    IconUsersGroup,
    IconKey,
    IconServer
} from '@tabler/icons-react';
import ToastContainer from '@/Components/ToastContainer';
import { useToast } from '@/Hooks/useToast';
import LogoutForm from '@/Components/LogoutForm';

export default function AppLayout({ children, title }) {
    const pageData = usePage();
    const { auth, flash } = pageData.props;
    const currentUrl = pageData.url || window.location.pathname;
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);
    const { toasts, removeToast, showSuccess, showError } = useToast();

    // Helper function to check if user has specific role
    const hasRole = (roleName) => {
        return auth?.user?.roles?.some(role => role.name === roleName);
    };

    // Helper function to check if user has specific permission
    const hasPermission = (permissionName) => {
        return auth?.user?.roles?.some(role => 
            role.permissions?.some(permission => permission.name === permissionName)
        );
    };

    // Handle flash messages
    useEffect(() => {
        console.log('AppLayout useEffect triggered with flash:', flash);
        if (flash?.success) {
            console.log('Showing success toast:', flash.success);
            showSuccess(flash.success);
        } else if (flash?.message) {
            console.log('Showing message toast:', flash.message);
            showSuccess(flash.message);
        }
        if (flash?.error) {
            console.log('Showing error toast:', flash.error);
            showError(flash.error);
        }
    }, [flash]);

    // Handle logout success/error
    const handleLogoutSuccess = () => {
        showSuccess('Logged out successfully');
        setUserDropdownOpen(false);
    };
    
    const handleLogoutError = (error) => {
        console.error('Logout error:', error);
        showError('Failed to logout. Please try again.');
        setUserDropdownOpen(false);
    };


    // Base navigation items available to all users
    const baseNavigation = [
        { name: 'Dashboard', href: '/', icon: IconDashboard, current: currentUrl === '/' },
        { name: 'Products', href: '/products', icon: IconDatabase, current: currentUrl.startsWith('/products') },
        { name: 'Customers', href: '/customers', icon: IconUsers, current: currentUrl.startsWith('/customers') },
        { name: 'Dashboards', href: '/dashboards', icon: IconChartBar, current: currentUrl.startsWith('/dashboards') },
        { name: 'Formulas', href: '/formulas', icon: IconSettings, current: currentUrl.startsWith('/formulas') },
        { name: 'Data Ingestion', href: '/data-ingestion', icon: IconDatabase, current: currentUrl.startsWith('/data-ingestion') },
    ];

    // Admin-only navigation items
    const adminNavigation = [
        { name: 'User Management', href: '/admin/users', icon: IconUsersGroup, current: currentUrl.startsWith('/admin/users') },
        { name: 'Audit Trail', href: '/admin/audit-trail', icon: IconClipboardList, current: currentUrl.startsWith('/admin/audit-trail') },
        { name: 'Role Management', href: '/admin/roles', icon: IconShield, current: currentUrl.startsWith('/admin/roles') },
        { name: 'System Settings', href: '/admin/settings', icon: IconServer, current: currentUrl.startsWith('/admin/settings') },
        { name: 'System Logs', href: '/admin/logs', icon: IconFileText, current: currentUrl.startsWith('/admin/logs') },
    ];

    // Combine navigation based on user role
    const navigation = [
        ...baseNavigation,
        // Only show admin navigation if user has Admin role or specific admin permissions
        ...(hasRole('Admin') || hasPermission('manage-users') || hasPermission('view-audit-trail') ? adminNavigation : [])
    ];

    return (
        <>
            <Head title={title} />
            
            <div className="page" style={{backgroundColor: '#000000', margin: 0, padding: 0}}>
                {/* Authentic Tabler Header Navigation */}
                <header className="navbar navbar-expand-md navbar-light d-print-none w-100" data-bs-theme="dark" style={{backgroundColor: '#222551', width: '100vw', margin: 0, padding: 0}}>
                    <div className="container-fluid">
                        
                        {/* Brand Logo */}
                        <Link href="/" className="navbar-brand me-3 d-flex align-items-center">
                            <img 
                                src="https://www.gralix.co/assets/images/resources/logo-gralix.png" 
                                alt="Gralix Logo" 
                                className="brand-logo me-2"
                                style={{ height: '32px', width: 'auto' }}
                            />
                        </Link>
                        
                        {/* Mobile toggle */}
                        <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                            <span className="navbar-toggler-icon"></span>
                        </button>
                        
                        {/* Main Navigation */}
                        <div className="collapse navbar-collapse" id="navbar-menu">
                            <div className="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                                <ul className="navbar-nav">
                                    {navigation.map((item) => {
                                        const Icon = item.icon;
                                        return (
                                            <li key={item.name} className="nav-item">
                                                <Link
                                                    href={item.href}
                                                    className={`nav-link ${item.current ? 'active' : ''}`}
                                                >
                                                    <span className="nav-link-icon d-md-none d-lg-inline-block">
                                                        <Icon size={24} />
                                                    </span>
                                                    <span className="nav-link-title">
                                                        {item.name}
                                                    </span>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        </div>
                        
                        {/* Right side navigation */}
                        <div className="navbar-nav flex-row order-md-last">
                            
                            {/* User menu */}
                            <div className="nav-item dropdown position-relative">
                                <button 
                                    className="nav-link d-flex lh-1 text-reset p-2 btn btn-link" 
                                    onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                                    style={{minWidth: 'fit-content', paddingRight: '1.5rem'}}
                                >
                                    <span className="avatar avatar-sm bg-primary text-white">
                                        {(auth?.user?.name?.charAt(0)) || 'U'}
                                    </span>
                                    <div className="d-none d-lg-block ps-2" style={{whiteSpace: 'nowrap'}}>
                                        <div className="text-white">{auth?.user?.name || 'User'}</div>
                                        <div className="mt-1 small text-muted">{auth?.user?.email || 'user@example.com'}</div>
                                    </div>
                                </button>
                                {userDropdownOpen && (
                                    <>
                                        <div 
                                            className="position-fixed top-0 start-0 w-100 h-100" 
                                            style={{zIndex: 1040}}
                                            onClick={() => setUserDropdownOpen(false)}
                                        ></div>
                                        <div className="dropdown-menu dropdown-menu-end dropdown-menu-arrow show position-absolute" style={{zIndex: 1050, right: 0, top: '100%'}}>
                                            <div className="dropdown-header">
                                                <strong>{auth?.user?.name || 'User'}</strong>
                                                <div className="text-muted small">{auth?.user?.email || 'user@example.com'}</div>
                                            </div>
                                            <div className="dropdown-divider"></div>
                                            <Link href="/profile" className="dropdown-item" onClick={() => setUserDropdownOpen(false)}>
                                                <IconUser size={16} className="me-2" />
                                                View Profile
                                            </Link>
                                            <Link href="/profile/edit" className="dropdown-item" onClick={() => setUserDropdownOpen(false)}>
                                                <IconEdit size={16} className="me-2" />
                                                Edit Profile
                                            </Link>
                                            <Link href="/profile/edit#password" className="dropdown-item" onClick={() => setUserDropdownOpen(false)}>
                                                <IconLock size={16} className="me-2" />
                                                Change Password
                                            </Link>
                                            <div className="dropdown-divider"></div>
                                            <LogoutForm 
                                                onSuccess={handleLogoutSuccess}
                                                onError={handleLogoutError}
                                                className="d-block"
                                            >
                                                <button type="submit" className="dropdown-item text-danger w-100 text-start">
                                                    <IconLogout size={16} className="me-2" />
                                                    Logout
                                                </button>
                                            </LogoutForm>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Main content - Full width without wrapper */}
                <main className="main-content">
                    {/* Flash messages - Banner style for backup if toasts don't work */}
                    {flash?.success && (
                        <div className="container-fluid px-4 pt-3">
                            <div className="alert alert-success alert-dismissible fade show" role="alert" style={{ backgroundColor: '#d4edda', borderColor: '#c3e6cb', color: '#155724' }}>
                                <div className="d-flex align-items-center">
                                    <svg className="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.384 6.323a.75.75 0 0 0-1.06 1.061l4.293 4.293a.75.75 0 0 0 1.24-.02l4.313-5.245a.75.75 0 0 0-.022-1.08z"/>
                                    </svg>
                                    <div className="flex-grow-1">
                                        <strong>Success: </strong>{flash.success}
                                    </div>
                                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        </div>
                    )}
                    
                    {flash?.message && (
                        <div className="container-fluid px-4 pt-3">
                            <div className="alert alert-success alert-dismissible fade show" role="alert" style={{ backgroundColor: '#d4edda', borderColor: '#c3e6cb', color: '#155724' }}>
                                <div className="d-flex align-items-center">
                                    <svg className="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.384 6.323a.75.75 0 0 0-1.06 1.061l4.293 4.293a.75.75 0 0 0 1.24-.02l4.313-5.245a.75.75 0 0 0-.022-1.08z"/>
                                    </svg>
                                    <div className="flex-grow-1">
                                        <strong>Success: </strong>{flash.message}
                                    </div>
                                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        </div>
                    )}
                    
                    {flash?.error && (
                        <div className="container-fluid px-4 pt-3">
                            <div className="alert alert-danger alert-dismissible fade show" role="alert" style={{ backgroundColor: '#f8d7da', borderColor: '#f5c6cb', color: '#721c24' }}>
                                <div className="d-flex align-items-center">
                                    <svg className="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                    </svg>
                                    <div className="flex-grow-1">
                                        <strong>Error: </strong>{flash.error}
                                    </div>
                                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        </div>
                    )}

                    {children}
                </main>
            </div>
            
            {/* Toast Container */}
            <ToastContainer toasts={toasts} onRemoveToast={removeToast} />
        </>
    );
}