import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { 
    IconFileText, 
    IconSearch, 
    IconFilter,
    IconDownload,
    IconRefresh,
    IconUser,
    IconCalendar,
    IconShield,
    IconDatabase,
    IconEye,
    IconPlus,
    IconEdit,
    IconTrash
} from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';

export default function SystemLogs({ auditLogs, totalLogs, auth }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedEvent, setSelectedEvent] = useState('');
    const [filteredLogs, setFilteredLogs] = useState(auditLogs || []);

    useEffect(() => {
        let filtered = auditLogs || [];

        // Filter by search term
        if (searchTerm) {
            filtered = filtered.filter(log => 
                log.user_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                log.event?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                log.auditable_type?.toLowerCase().includes(searchTerm.toLowerCase())
            );
        }

        // Filter by event type
        if (selectedEvent) {
            filtered = filtered.filter(log => 
                log.event === selectedEvent
            );
        }

        setFilteredLogs(filtered);
    }, [searchTerm, selectedEvent, auditLogs]);

    const getEventColor = (event) => {
        switch (event) {
            case 'created': return 'success';
            case 'updated': return 'warning';
            case 'deleted': return 'danger';
            default: return 'secondary';
        }
    };

    const formatAuditableType = (type) => {
        return type ? type.split('\\').pop() : 'Unknown';
    };

    const getEventIcon = (event) => {
        switch (event) {
            case 'created': return <IconPlus size={16} />;
            case 'updated': return <IconEdit size={16} />;
            case 'deleted': return <IconTrash size={16} />;
            default: return <IconShield size={16} />;
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleString();
    };

    const handleExport = () => {
        // Export functionality would go here
        console.log('Export audit logs');
    };

    const handleRefresh = () => {
        window.location.reload();
    };

    return (
        <AppLayout title="System Logs">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Admin
                            </div>
                            <h2 className="page-title">
                                System Audit Logs
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
                                        <div className="subheader">Total Logs</div>
                                        <div className="ms-auto">
                                            <IconFileText size={24} className="text-primary" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{totalLogs || 0}</div>
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
                                        <div className="subheader">Recent Activity</div>
                                        <div className="ms-auto">
                                            <IconDatabase size={24} className="text-success" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{filteredLogs.length}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Filtered results</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="card mb-3">
                        <div className="card-header">
                            <h3 className="card-title">Filter Audit Logs</h3>
                        </div>
                        <div className="card-body">
                            <div className="row g-3">
                                <div className="col-md-6">
                                    <label className="form-label">Search</label>
                                    <div className="input-group">
                                        <span className="input-group-text">
                                            <IconSearch size={16} />
                                        </span>
                                        <input
                                            type="text"
                                            className="form-control"
                                            placeholder="Search by user, event, or model..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="col-md-6">
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
                                                <th>Changes</th>
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
                                                                <div className="fw-bold">{log.user_name}</div>
                                                                {log.user_email && (
                                                                    <div className="text-muted small">{log.user_email}</div>
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
                                                        <div className="small">
                                                            {log.old_values && Object.keys(log.old_values).length > 0 && (
                                                                <div className="text-danger">
                                                                    <strong>Old:</strong> {Object.keys(log.old_values).length} fields
                                                                </div>
                                                            )}
                                                            {log.new_values && Object.keys(log.new_values).length > 0 && (
                                                                <div className="text-success">
                                                                    <strong>New:</strong> {Object.keys(log.new_values).length} fields
                                                                </div>
                                                            )}
                                                        </div>
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
                                        <IconFileText size={48} className="text-muted" />
                                    </div>
                                    <p className="empty-title">No audit logs found</p>
                                    <p className="empty-subtitle text-muted">
                                        {searchTerm || selectedEvent 
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