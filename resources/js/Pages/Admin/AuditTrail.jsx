import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    IconSearch, 
    IconFilter, 
    IconDownload,
    IconEye,
    IconCalendar,
    IconUser,
    IconDatabase,
    IconEdit,
    IconTrash,
    IconPlus,
    IconRefresh,
    IconClipboardList
} from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';

export default function AuditTrail({ auditLogs, users, auth }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedUser, setSelectedUser] = useState('');
    const [selectedEvent, setSelectedEvent] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [filteredLogs, setFilteredLogs] = useState(auditLogs?.data || []);

    useEffect(() => {
        let filtered = auditLogs?.data || [];

        // Filter by search term
        if (searchTerm) {
            filtered = filtered.filter(log => 
                log.user?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                log.event?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                log.auditable_type?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                log.description?.toLowerCase().includes(searchTerm.toLowerCase())
            );
        }

        // Filter by user
        if (selectedUser) {
            filtered = filtered.filter(log => log.user_id === parseInt(selectedUser));
        }

        // Filter by event
        if (selectedEvent) {
            filtered = filtered.filter(log => log.event === selectedEvent);
        }

        // Filter by date range
        if (dateFrom) {
            filtered = filtered.filter(log => 
                new Date(log.created_at) >= new Date(dateFrom)
            );
        }

        if (dateTo) {
            filtered = filtered.filter(log => 
                new Date(log.created_at) <= new Date(dateTo + 'T23:59:59')
            );
        }

        setFilteredLogs(filtered);
    }, [searchTerm, selectedUser, selectedEvent, dateFrom, dateTo, auditLogs]);

    const getEventColor = (event) => {
        switch (event) {
            case 'created': return 'success';
            case 'updated': return 'warning';
            case 'deleted': return 'danger';
            default: return 'secondary';
        }
    };

    const getEventIcon = (event) => {
        switch (event) {
            case 'created': return <IconPlus size={16} />;
            case 'updated': return <IconEdit size={16} />;
            case 'deleted': return <IconTrash size={16} />;
            default: return <IconDatabase size={16} />;
        }
    };

    const formatAuditableType = (type) => {
        return type ? type.split('\\').pop() : 'Unknown';
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleString();
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (selectedUser) params.append('user_id', selectedUser);
        if (selectedEvent) params.append('event', selectedEvent);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        window.open(`/admin/audit-trail/export?${params.toString()}`, '_blank');
    };

    const handleRefresh = () => {
        window.location.reload();
    };

    return (
        <AppLayout title="Audit Trail">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Admin
                            </div>
                            <h2 className="page-title">
                                Audit Trail
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <button className="btn btn-outline-primary" onClick={handleExport}>
                                    <IconDownload size={16} className="me-1" />
                                    Export
                                </button>
                                <button className="btn btn-primary" onClick={handleRefresh}>
                                    <IconRefresh size={16} className="me-1" />
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    {/* Statistics Cards */}
                    <div className="row row-deck row-cards mb-3">
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Total Events</div>
                                        <div className="ms-auto">
                                            <IconClipboardList size={24} className="text-primary" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{auditLogs?.total || 0}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Audit entries</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Filtered Results</div>
                                        <div className="ms-auto">
                                            <IconFilter size={24} className="text-success" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{filteredLogs.length}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Matching criteria</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Active Users</div>
                                        <div className="ms-auto">
                                            <IconUser size={24} className="text-info" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        {new Set(filteredLogs.map(log => log.user_id)).size}
                                    </div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Unique users</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Models Tracked</div>
                                        <div className="ms-auto">
                                            <IconDatabase size={24} className="text-warning" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        {new Set(filteredLogs.map(log => log.auditable_type)).size}
                                    </div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Different models</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="card mb-3">
                        <div className="card-header">
                            <h3 className="card-title">Filter Audit Trail</h3>
                        </div>
                        <div className="card-body">
                            <div className="row g-3">
                                <div className="col-md-3">
                                    <label className="form-label">Search</label>
                                    <div className="input-group">
                                        <span className="input-group-text">
                                            <IconSearch size={16} />
                                        </span>
                                        <input
                                            type="text"
                                            className="form-control"
                                            placeholder="Search events..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label">User</label>
                                    <select
                                        className="form-select"
                                        value={selectedUser}
                                        onChange={(e) => setSelectedUser(e.target.value)}
                                    >
                                        <option value="">All Users</option>
                                        {users.map((user) => (
                                            <option key={user.id} value={user.id}>
                                                {user.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label">Event Type</label>
                                    <select
                                        className="form-select"
                                        value={selectedEvent}
                                        onChange={(e) => setSelectedEvent(e.target.value)}
                                    >
                                        <option value="">All Events</option>
                                        <option value="created">Created</option>
                                        <option value="updated">Updated</option>
                                        <option value="deleted">Deleted</option>
                                    </select>
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label">Date From</label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={dateFrom}
                                        onChange={(e) => setDateFrom(e.target.value)}
                                    />
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label">Date To</label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Audit Logs Table */}
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">Audit Trail</h3>
                        </div>
                        <div className="card-body">
                            {filteredLogs.length > 0 ? (
                                <div className="table-responsive">
                                    <table className="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Event</th>
                                                <th>Model</th>
                                                <th>ID</th>
                                                <th>Description</th>
                                                <th>Date</th>
                                                <th>IP Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filteredLogs.map((log) => (
                                                <tr key={log.id}>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <IconUser size={16} className="me-2 text-muted" />
                                                            <div>
                                                                <div className="fw-bold">{log.user?.name || 'System'}</div>
                                                                {log.user?.email && (
                                                                    <div className="text-muted small">{log.user.email}</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span className={`badge bg-${getEventColor(log.event)}`}>
                                                            <span className="me-1">
                                                                {getEventIcon(log.event)}
                                                            </span>
                                                            {log.event}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <IconDatabase size={16} className="me-2 text-muted" />
                                                            {formatAuditableType(log.auditable_type)}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span className="text-muted">#{log.auditable_id}</span>
                                                    </td>
                                                    <td>
                                                        <span className="text-muted small">
                                                            {log.description || 'No description'}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <IconCalendar size={16} className="me-2 text-muted" />
                                                            <span className="text-muted small">
                                                                {formatDate(log.created_at)}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span className="text-muted small">{log.ip_address || 'N/A'}</span>
                                                    </td>
                                                    <td>
                                                        <button className="btn btn-sm btn-outline-primary">
                                                            <IconEye size={16} />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="empty">
                                    <div className="empty-img">
                                        <IconClipboardList size={48} className="text-muted" />
                                    </div>
                                    <p className="empty-title">No audit logs found</p>
                                    <p className="empty-subtitle text-muted">
                                        {searchTerm || selectedUser || selectedEvent || dateFrom || dateTo
                                            ? 'Try adjusting your filters to see more results'
                                            : 'System activity will appear here as users interact with the system'
                                        }
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}