import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    IconPlus, 
    IconEdit, 
    IconTrash, 
    IconUserCheck, 
    IconUserX,
    IconKey,
    IconShield,
    IconSearch,
    IconFilter,
    IconMail,
    IconCalendar
} from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';

export default function UserManagement({ users, roles, auth }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [filteredUsers, setFilteredUsers] = useState(users || []);

    useEffect(() => {
        const filtered = users.filter(user => 
            user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            user.email.toLowerCase().includes(searchTerm.toLowerCase())
        );
        setFilteredUsers(filtered);
    }, [searchTerm, users]);

    const handleDeleteUser = (userId) => {
        if (confirm('Are you sure you want to delete this user?')) {
            router.delete(`/admin/users/${userId}`);
        }
    };

    const handleToggleUserStatus = (userId, currentStatus) => {
        const action = currentStatus ? 'deactivate' : 'activate';
        if (confirm(`Are you sure you want to ${action} this user?`)) {
            router.patch(`/admin/users/${userId}/toggle-status`);
        }
    };

    const getRoleBadgeColor = (roleName) => {
        switch (roleName) {
            case 'Admin': return 'danger';
            case 'Analyst': return 'warning';
            case 'Viewer': return 'success';
            default: return 'secondary';
        }
    };

    return (
        <AppLayout title="User Management">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Admin
                            </div>
                            <h2 className="page-title">
                                User Management
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/admin/users/create" className="btn btn-primary">
                                    <IconPlus size={16} className="me-1" />
                                    Add User
                                </Link>
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
                                        <div className="subheader">Total Users</div>
                                        <div className="ms-auto">
                                            <IconUserCheck size={24} className="text-primary" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{users.length}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Registered users</div>
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
                                            <IconUserCheck size={24} className="text-success" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{users.filter(u => u.is_active !== false).length}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Currently active</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Admin Users</div>
                                        <div className="ms-auto">
                                            <IconShield size={24} className="text-danger" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        {users.filter(u => u.roles?.some(r => r.name === 'Admin')).length}
                                    </div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Administrators</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Total Roles</div>
                                        <div className="ms-auto">
                                            <IconKey size={24} className="text-info" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{roles.length}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Available roles</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filter */}
                    <div className="card mb-3">
                        <div className="card-header">
                            <h3 className="card-title">Filter Users</h3>
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
                                            placeholder="Search by name or email..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Users Table */}
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">Users</h3>
                        </div>
                        <div className="card-body">
                            {filteredUsers.length > 0 ? (
                                <div className="table-responsive">
                                    <table className="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Roles</th>
                                                <th>Status</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filteredUsers.map((user) => (
                                                <tr key={user.id}>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <div className="avatar avatar-sm me-2">
                                                                <div className="avatar-initial bg-primary text-white">
                                                                    {user.name.charAt(0).toUpperCase()}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div className="fw-bold">{user.name}</div>
                                                                <div className="text-muted small">ID: {user.id}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <IconMail size={16} className="me-2 text-muted" />
                                                            {user.email}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div className="d-flex flex-wrap gap-1">
                                                            {user.roles?.map((role) => (
                                                                <span key={role.id} className={`badge bg-${getRoleBadgeColor(role.name)}`}>
                                                                    {role.name}
                                                                </span>
                                                            )) || <span className="badge bg-secondary">No roles</span>}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span className={`badge bg-${user.is_active !== false ? 'success' : 'danger'}`}>
                                                            {user.is_active !== false ? 'Active' : 'Inactive'}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <IconCalendar size={16} className="me-2 text-muted" />
                                                            <span className="text-muted small">
                                                                {user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : 'Never'}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div className="btn-list">
                                                            <Link href={`/admin/users/${user.id}/edit`} className="btn btn-sm btn-outline-primary">
                                                                <IconEdit size={16} />
                                                            </Link>
                                                            <button 
                                                                className="btn btn-sm btn-outline-warning"
                                                                onClick={() => handleToggleUserStatus(user.id, user.is_active !== false)}
                                                            >
                                                                {user.is_active !== false ? <IconUserX size={16} /> : <IconUserCheck size={16} />}
                                                            </button>
                                                            <button 
                                                                className="btn btn-sm btn-outline-danger"
                                                                onClick={() => handleDeleteUser(user.id)}
                                                            >
                                                                <IconTrash size={16} />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="empty">
                                    <div className="empty-img">
                                        <IconUserCheck size={48} className="text-muted" />
                                    </div>
                                    <p className="empty-title">No users found</p>
                                    <p className="empty-subtitle text-muted">
                                        {searchTerm 
                                            ? 'Try adjusting your search terms'
                                            : 'Create your first user to get started'
                                        }
                                    </p>
                                    <div className="empty-action">
                                        <Link href="/admin/users/create" className="btn btn-primary">
                                            <IconPlus size={16} className="me-1" />
                                            Add User
                                        </Link>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}