import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    IconShield, 
    IconPlus, 
    IconEdit, 
    IconTrash,
    IconUsers,
    IconKey,
    IconCheck,
    IconX
} from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';

export default function RoleManagement({ roles, permissions, auth }) {
    const [expandedRole, setExpandedRole] = useState(null);

    const getRoleColor = (roleName) => {
        switch (roleName) {
            case 'Admin': return 'danger';
            case 'Analyst': return 'warning';
            case 'Viewer': return 'success';
            default: return 'secondary';
        }
    };

    const handleDeleteRole = (roleId, roleName) => {
        if (confirm(`Are you sure you want to delete the "${roleName}" role?`)) {
            router.delete(`/admin/roles/${roleId}`);
        }
    };

    const toggleRoleExpansion = (roleId) => {
        setExpandedRole(expandedRole === roleId ? null : roleId);
    };

    return (
        <AppLayout title="Role Management">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Admin
                            </div>
                            <h2 className="page-title">
                                Role Management
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/admin/roles/create" className="btn btn-primary">
                                    <IconPlus size={16} className="me-1" />
                                    Add Role
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
                                        <div className="subheader">Total Roles</div>
                                        <div className="ms-auto">
                                            <IconShield size={24} className="text-primary" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{roles.length}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Available roles</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Total Permissions</div>
                                        <div className="ms-auto">
                                            <IconKey size={24} className="text-success" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">{permissions.length}</div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">System permissions</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Admin Roles</div>
                                        <div className="ms-auto">
                                            <IconShield size={24} className="text-danger" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        {roles.filter(r => r.name === 'Admin').length}
                                    </div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Administrative roles</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 col-lg-3">
                            <div className="card">
                                <div className="card-body">
                                    <div className="d-flex align-items-center">
                                        <div className="subheader">Standard Roles</div>
                                        <div className="ms-auto">
                                            <IconUsers size={24} className="text-info" />
                                        </div>
                                    </div>
                                    <div className="h1 mb-3">
                                        {roles.filter(r => !['Admin'].includes(r.name)).length}
                                    </div>
                                    <div className="d-flex mb-2">
                                        <div className="text-muted">Standard user roles</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Roles List */}
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">Roles & Permissions</h3>
                        </div>
                        <div className="card-body">
                            {roles.length > 0 ? (
                                <div className="row">
                                    {roles.map((role) => (
                                        <div key={role.id} className="col-md-6 col-lg-4 mb-3">
                                            <div className="card">
                                                <div className="card-body">
                                                    <div className="d-flex align-items-center mb-3">
                                                        <div className="me-3">
                                                            <IconShield size={24} className={`text-${getRoleColor(role.name)}`} />
                                                        </div>
                                                        <div className="flex-fill">
                                                            <h4 className="card-title mb-1">{role.name}</h4>
                                                            <span className={`badge bg-${getRoleColor(role.name)}`}>
                                                                {role.permissions?.length || 0} permissions
                                                            </span>
                                                        </div>
                                                        <div className="dropdown">
                                                            <button className="btn btn-white btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                                Actions
                                                            </button>
                                                            <div className="dropdown-menu">
                                                                <Link href={`/admin/roles/${role.id}/edit`} className="dropdown-item">
                                                                    <IconEdit size={16} className="me-2" />
                                                                    Edit
                                                                </Link>
                                                                <button 
                                                                    className="dropdown-item text-danger" 
                                                                    onClick={() => handleDeleteRole(role.id, role.name)}
                                                                    disabled={role.name === 'Admin'}
                                                                >
                                                                    <IconTrash size={16} className="me-2" />
                                                                    Delete
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div className="mb-3">
                                                        <button 
                                                            className="btn btn-sm btn-outline-primary"
                                                            onClick={() => toggleRoleExpansion(role.id)}
                                                        >
                                                            {expandedRole === role.id ? 'Hide' : 'Show'} Permissions
                                                        </button>
                                                    </div>

                                                    {expandedRole === role.id && (
                                                        <div className="permissions-list">
                                                            <h6 className="mb-2">Permissions:</h6>
                                                            <div className="row">
                                                                {role.permissions?.map((permission) => (
                                                                    <div key={permission.id} className="col-12 mb-1">
                                                                        <div className="d-flex align-items-center">
                                                                            <IconCheck size={16} className="me-2 text-success" />
                                                                            <span className="small">{permission.name}</span>
                                                                        </div>
                                                                    </div>
                                                                )) || (
                                                                    <div className="col-12">
                                                                        <div className="text-muted small">No permissions assigned</div>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="empty">
                                    <div className="empty-img">
                                        <IconShield size={48} className="text-muted" />
                                    </div>
                                    <p className="empty-title">No roles found</p>
                                    <p className="empty-subtitle text-muted">
                                        Create your first role to get started with permission management
                                    </p>
                                    <div className="empty-action">
                                        <Link href="/admin/roles/create" className="btn btn-primary">
                                            <IconPlus size={16} className="me-1" />
                                            Add Role
                                        </Link>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* All Permissions Overview */}
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">All Available Permissions</h3>
                        </div>
                        <div className="card-body">
                            <div className="row">
                                {permissions.map((permission) => (
                                    <div key={permission.id} className="col-md-6 col-lg-4 mb-2">
                                        <div className="d-flex align-items-center">
                                            <IconKey size={16} className="me-2 text-muted" />
                                            <span className="small">{permission.name}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}