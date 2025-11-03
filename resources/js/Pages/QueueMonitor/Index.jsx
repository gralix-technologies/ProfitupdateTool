import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    IconActivity, 
    IconAlertTriangle, 
    IconCircleCheck, 
    IconClock, 
    IconRefresh, 
    IconTrash,
    IconPlayerPlay,
    IconChartBar,
    IconUsers,
    IconDatabase,
    IconServer
} from '@tabler/icons-react';
import axios from 'axios';

export default function QueueMonitorIndex({ stats: initialStats, health: initialHealth, performance: initialPerformance }) {
    const [stats, setStats] = useState(initialStats || {});
    const [health, setHealth] = useState(initialHealth || { status: 'healthy', issues: [], recommendations: [] });
    const [performance, setPerformance] = useState(initialPerformance || {});
    const [failedJobs, setFailedJobs] = useState([]);
    const [loading, setLoading] = useState(false);
    const [autoRefresh, setAutoRefresh] = useState(true);

    // Auto-refresh data every 30 seconds
    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(() => {
            refreshData();
        }, 30000);

        return () => clearInterval(interval);
    }, [autoRefresh]);

    // Load initial data
    useEffect(() => {
        loadFailedJobs();
    }, []);

    const refreshData = async () => {
        try {
            const [statsRes, healthRes, performanceRes] = await Promise.all([
                axios.get('/api/queue/stats'),
                axios.get('/api/queue/health'),
                axios.get('/api/queue/performance')
            ]);

            setStats(statsRes.data.data);
            setHealth(healthRes.data.data);
            setPerformance(performanceRes.data.data);
        } catch (error) {
            console.error('Failed to refresh queue data:', error);
        }
    };

    const loadFailedJobs = async () => {
        try {
            const response = await axios.get('/api/queue/failed-jobs');
            setFailedJobs(response.data.data);
        } catch (error) {
            console.error('Failed to load failed jobs:', error);
        }
    };

    const retryJob = async (jobId) => {
        setLoading(true);
        try {
            await axios.post('/api/queue/retry-job', { job_id: jobId });
            await loadFailedJobs();
            await refreshData();
        } catch (error) {
            console.error('Failed to retry job:', error);
        } finally {
            setLoading(false);
        }
    };

    const retryAllJobs = async () => {
        setLoading(true);
        try {
            await axios.post('/api/queue/retry-all');
            await loadFailedJobs();
            await refreshData();
        } catch (error) {
            console.error('Failed to retry all jobs:', error);
        } finally {
            setLoading(false);
        }
    };

    const clearFailedJobs = async () => {
        setLoading(true);
        try {
            await axios.post('/api/queue/clear-failed');
            await loadFailedJobs();
            await refreshData();
        } catch (error) {
            console.error('Failed to clear failed jobs:', error);
        } finally {
            setLoading(false);
        }
    };

    const testJob = async (jobType) => {
        setLoading(true);
        try {
            await axios.post('/api/queue/test-job', { job_type: jobType });
            setTimeout(() => refreshData(), 2000); // Refresh after 2 seconds
        } catch (error) {
            console.error('Failed to dispatch test job:', error);
        } finally {
            setLoading(false);
        }
    };

    const getHealthBadgeColor = (status) => {
        switch (status) {
            case 'healthy': return 'bg-success';
            case 'warning': return 'bg-warning';
            case 'critical': return 'bg-danger';
            default: return 'bg-secondary';
        }
    };

    return (
        <AppLayout title="Queue Monitor">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                System Monitoring
                            </div>
                            <h2 className="page-title">
                                Queue Monitor
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <button
                                    className={`btn ${autoRefresh ? 'btn-primary' : 'btn-outline-primary'}`}
                                    onClick={() => setAutoRefresh(!autoRefresh)}
                                >
                                    <IconActivity size={16} className="me-1" />
                                    Auto Refresh {autoRefresh ? 'On' : 'Off'}
                                </button>
                                <button 
                                    className="btn btn-primary" 
                                    onClick={refreshData} 
                                    disabled={loading}
                                >
                                    <IconRefresh size={16} className={`me-1 ${loading ? 'spin' : ''}`} />
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    <div className="row row-deck row-cards">
                        {/* Health Status Alert */}
                        {health.status !== 'healthy' && (
                            <div className="col-12">
                                <div className={`alert alert-${health.status === 'critical' ? 'danger' : 'warning'} alert-dismissible`}>
                                    <div className="d-flex">
                                        <div>
                                            <IconAlertTriangle size={24} />
                                        </div>
                                        <div>
                                            <h4>Queue Health: {health.status.toUpperCase()}</h4>
                                            <ul className="mb-0">
                                                {health.issues.map((issue, index) => (
                                                    <li key={index}>{issue}</li>
                                                ))}
                                            </ul>
                                            {health.recommendations.length > 0 && (
                                                <div className="mt-2">
                                                    <strong>Recommendations:</strong>
                                                    <ul>
                                                        {health.recommendations.map((rec, index) => (
                                                            <li key={index}>{rec}</li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Overview Cards */}
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Pending Jobs</div>
                                        <div className="ms-auto">
                                            <div className="bg-primary text-white avatar">
                                                <IconClock size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{stats.pending_jobs || 0}</div>
                                    <div className="text-muted">Jobs waiting to be processed</div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Failed Jobs</div>
                                        <div className="ms-auto">
                                            <div className="bg-danger text-white avatar">
                                                <IconAlertTriangle size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="h1 mb-3 text-danger">{stats.failed_jobs || 0}</div>
                                    <div className="text-muted">Jobs that failed processing</div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Processed Today</div>
                                        <div className="ms-auto">
                                            <div className="bg-success text-white avatar">
                                                <IconCircleCheck size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="h1 mb-3 text-success">{stats.processed_jobs_today || 0}</div>
                                    <div className="text-muted">Jobs completed today</div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Health Status</div>
                                        <div className="ms-auto">
                                            <div className="bg-info text-white avatar">
                                                <IconActivity size={24} />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        <span className={`badge ${getHealthBadgeColor(health.status)} text-white`}>
                                            {health.status.toUpperCase()}
                                        </span>
                                    </div>
                                    <div className="text-muted">Overall queue health</div>
                                </div>
                            </div>
                        </div>

                        {/* Performance Metrics */}
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="subheader">Jobs/Minute</div>
                                    <div className="h2 mb-0">{performance.jobs_per_minute || 0}</div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="subheader">Avg Wait Time</div>
                                    <div className="h2 mb-0">{performance.average_wait_time || 0}s</div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="subheader">Success Rate</div>
                                    <div className="h2 mb-0 text-success">{performance.success_rate || 0}%</div>
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="subheader">Worker Status</div>
                                    <div className="h2 mb-0">
                                        {stats.worker_status?.active_workers || 0}/{stats.worker_status?.total_workers || 0}
                                    </div>
                                    <div className="text-muted">{stats.worker_status?.status || 'Unknown'}</div>
                                </div>
                            </div>
                        </div>

                        {/* Queue Actions */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">Queue Actions</h3>
                                </div>
                                <div className="card-body">
                                    <div className="row g-2">
                                        <div className="col-12">
                                            <button 
                                                className="btn btn-primary w-100"
                                                onClick={retryAllJobs} 
                                                disabled={loading || (stats.failed_jobs === 0)}
                                            >
                                                <IconRefresh size={16} className="me-1" />
                                                Retry All Failed Jobs
                                            </button>
                                        </div>
                                        <div className="col-12">
                                            <button 
                                                className="btn btn-danger w-100"
                                                onClick={clearFailedJobs} 
                                                disabled={loading || (stats.failed_jobs === 0)}
                                            >
                                                <IconTrash size={16} className="me-1" />
                                                Clear All Failed Jobs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Test Jobs */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">Test Jobs</h3>
                                </div>
                                <div className="card-body">
                                    <div className="row g-2">
                                        <div className="col-6">
                                            <button 
                                                className="btn btn-outline-primary w-100"
                                                onClick={() => testJob('profitability')} 
                                                disabled={loading}
                                            >
                                                <IconChartBar size={16} className="me-1" />
                                                Profitability
                                            </button>
                                        </div>
                                        <div className="col-6">
                                            <button 
                                                className="btn btn-outline-primary w-100"
                                                onClick={() => testJob('dashboard')} 
                                                disabled={loading}
                                            >
                                                <IconActivity size={16} className="me-1" />
                                                Dashboard
                                            </button>
                                        </div>
                                        <div className="col-6">
                                            <button 
                                                className="btn btn-outline-primary w-100"
                                                onClick={() => testJob('notification')} 
                                                disabled={loading}
                                            >
                                                <IconUsers size={16} className="me-1" />
                                                Notification
                                            </button>
                                        </div>
                                        <div className="col-6">
                                            <button 
                                                className="btn btn-outline-primary w-100"
                                                onClick={() => testJob('dataset')} 
                                                disabled={loading}
                                            >
                                                <IconDatabase size={16} className="me-1" />
                                                Dataset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Recent Failed Jobs */}
                        {failedJobs.length > 0 && (
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">Recent Failed Jobs</h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="divide-y">
                                            {failedJobs.map((job) => (
                                                <div key={job.id} className="row py-3">
                                                    <div className="col">
                                                        <div className="strong">{job.queue}</div>
                                                        <div className="text-muted">
                                                            Failed at: {new Date(job.failed_at).toLocaleString()}
                                                        </div>
                                                        <div className="text-danger small mt-1">
                                                            {job.exception.split('\n')[0]}
                                                        </div>
                                                    </div>
                                                    <div className="col-auto">
                                                        <button
                                                            className="btn btn-outline-primary btn-sm"
                                                            onClick={() => retryJob(job.id)}
                                                            disabled={loading}
                                                        >
                                                            <IconPlayerPlay size={16} className="me-1" />
                                                            Retry
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
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