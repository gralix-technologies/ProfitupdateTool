import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { IconPlus, IconEye, IconEdit, IconTrash, IconCopy, IconChartBar } from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';

export default function Index() {
    const { dashboards } = usePage().props;

    const handleDelete = (dashboardId) => {
        if (confirm('Are you sure you want to delete this dashboard?')) {
            // Implementation for delete
            console.log('Delete dashboard:', dashboardId);
        }
    };

    const handleDuplicate = (dashboardId) => {
        // Implementation for duplicate
        console.log('Duplicate dashboard:', dashboardId);
    };

    return (
        <AppLayout title="Dashboards">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Analytics
                            </div>
                            <h2 className="page-title">
                                Dashboards
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/dashboards/create" className="btn btn-primary d-none d-sm-inline-block">
                                    <IconPlus size={16} className="me-1" />
                                    Create Dashboard
                                </Link>
                                <Link href="/dashboards/create" className="btn btn-primary d-sm-none btn-icon">
                                    <IconPlus size={16} />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    <div className="row row-deck row-cards">
                        {dashboards?.data && dashboards.data.length > 0 ? (
                            dashboards.data.map((dashboard) => (
                                <div key={dashboard.id} className="col-sm-6 col-lg-4">
                                    <div className="card">
                                        <div className="card-body">
                                            <div className="d-flex align-items-center">
                                                <div className="subheader">Dashboard</div>
                                                <div className="ms-auto">
                                                    <div className="dropdown">
                                                        <button className="btn btn-white btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <div className="dropdown-menu">
                                                            <Link href={`/dashboards/${dashboard.id}`} className="dropdown-item">
                                                                <IconEye size={16} className="me-2" />
                                                                View
                                                            </Link>
                                                            <Link href={`/dashboards/${dashboard.id}/edit`} className="dropdown-item">
                                                                <IconEdit size={16} className="me-2" />
                                                                Edit
                                                            </Link>
                                                            <button className="dropdown-item" onClick={() => handleDuplicate(dashboard.id)}>
                                                                <IconCopy size={16} className="me-2" />
                                                                Duplicate
                                                            </button>
                                                            <div className="dropdown-divider"></div>
                                                            <button className="dropdown-item text-danger" onClick={() => handleDelete(dashboard.id)}>
                                                                <IconTrash size={16} className="me-2" />
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="h1 mb-3">{dashboard.name}</div>
                                            <div className="d-flex mb-2">
                                                <div className="text-muted">{dashboard.widgets_count || 0} widgets</div>
                                                <div className="ms-auto">
                                                    <span className="text-green d-inline-flex align-items-center lh-1">
                                                        <IconChartBar size={16} className="me-1" />
                                                        Active
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="progress progress-sm">
                                                <div className="progress-bar bg-primary" style={{width: '75%'}} role="progressbar"></div>
                                            </div>
                                        </div>
                                        <div className="card-footer">
                                            <div className="row align-items-center">
                                                <div className="col">
                                                    <div className="text-muted">
                                                        Created {new Date(dashboard.created_at).toLocaleDateString()}
                                                    </div>
                                                </div>
                                                <div className="col-auto">
                                                    <Link href={`/dashboards/${dashboard.id}`} className="btn btn-primary btn-sm">
                                                        <IconEye size={16} className="me-1" />
                                                        View
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-body">
                                        <div className="empty">
                                            <div className="empty-img">
                                                <IconChartBar size={48} className="text-muted" />
                                            </div>
                                            <p className="empty-title">No dashboards found</p>
                                            <p className="empty-subtitle text-muted">
                                                Get started by creating your first analytics dashboard
                                            </p>
                                            <div className="empty-action">
                                                <Link href="/dashboards/create" className="btn btn-primary">
                                                    <IconPlus size={16} className="me-1" />
                                                    Create your first dashboard
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}