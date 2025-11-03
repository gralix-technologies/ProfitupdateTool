import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    IconPlus, 
    IconSearch, 
    IconFilter,
    IconCopy,
    IconEdit,
    IconTrash,
    IconEye,
    IconMath
} from '@tabler/icons-react';

export default function Index({ formulas, filters, products }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedProduct, setSelectedProduct] = useState(filters.product_id || '');
    const [selectedReturnType, setSelectedReturnType] = useState(filters.return_type || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.is_active || '');

    const handleSearch = () => {
        router.get('/formulas', {
            search: searchTerm,
            product_id: selectedProduct,
            return_type: selectedReturnType,
            is_active: selectedStatus
        }, {
            preserveState: true,
            replace: true
        });
    };

    const handleClearFilters = () => {
        setSearchTerm('');
        setSelectedProduct('');
        setSelectedReturnType('');
        setSelectedStatus('');
        router.get('/formulas');
    };

    const handleDuplicate = async (formula) => {
        try {
            await router.post(`/formulas/${formula.id}/duplicate`, {}, {
                onSuccess: () => {
                    // Success notification
                },
                onError: () => {
                    // Error notification
                }
            });
        } catch (error) {
            // Error notification
        }
    };

    const handleDelete = async (formula) => {
        if (!confirm('Are you sure you want to delete this formula?')) {
            return;
        }

        try {
            await router.delete(`/formulas/${formula.id}`, {
                onSuccess: () => {
                    // Success notification
                },
                onError: () => {
                    // Error notification
                }
            });
        } catch (error) {
            // Error notification
        }
    };

    const getReturnTypeBadgeColor = (type) => {
        const colors = {
            numeric: 'bg-blue',
            text: 'bg-green',
            boolean: 'bg-purple',
            date: 'bg-orange'
        };
        return colors[type] || 'bg-secondary';
    };

    return (
        <AppLayout title="Formulas">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Analytics
                            </div>
                            <h2 className="page-title">
                                Formulas
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/formulas/create" className="btn btn-primary d-none d-sm-inline-block">
                                    <IconPlus size={16} className="me-1" />
                                    Create Formula
                                </Link>
                                <Link href="/formulas/create" className="btn btn-primary d-sm-none btn-icon">
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
                        {/* Filters */}
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconFilter size={18} className="me-2" />
                                        Search & Filter Formulas
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="row g-3 align-items-end">
                                        <div className="col-md-3">
                                            <label className="form-label">Search</label>
                                            <div className="input-icon">
                                                <span className="input-icon-addon">
                                                    <IconSearch size={16} />
                                                </span>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    placeholder="Search formulas..."
                                                    value={searchTerm}
                                                    onChange={(e) => setSearchTerm(e.target.value)}
                                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-2">
                                            <label className="form-label">Product</label>
                                            <select 
                                                className="form-select" 
                                                value={selectedProduct} 
                                                onChange={(e) => setSelectedProduct(e.target.value)}
                                            >
                                                <option value="">All Products</option>
                                                <option value="global">Global Formulas</option>
                                                {products?.map((product) => (
                                                    <option key={product.id} value={product.id.toString()}>
                                                        {product.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-2">
                                            <label className="form-label">Type</label>
                                            <select 
                                                className="form-select" 
                                                value={selectedReturnType} 
                                                onChange={(e) => setSelectedReturnType(e.target.value)}
                                            >
                                                <option value="">All Types</option>
                                                <option value="numeric">Numeric</option>
                                                <option value="text">Text</option>
                                                <option value="boolean">Boolean</option>
                                                <option value="date">Date</option>
                                            </select>
                                        </div>
                                        <div className="col-md-2">
                                            <label className="form-label">Status</label>
                                            <select 
                                                className="form-select" 
                                                value={selectedStatus} 
                                                onChange={(e) => setSelectedStatus(e.target.value)}
                                            >
                                                <option value="">All Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        <div className="col-md-3">
                                            <div className="btn-list">
                                                <button className="btn btn-primary" onClick={handleSearch}>
                                                    <IconSearch size={16} className="me-1" />
                                                    Search
                                                </button>
                                                <button className="btn btn-outline-secondary" onClick={handleClearFilters}>
                                                    Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Formulas List */}
                        {formulas?.data && formulas.data.length > 0 ? (
                            formulas.data.map((formula) => (
                                <div key={formula.id} className="col-12">
                                    <div className="card">
                                        <div className="card-body">
                                            <div className="row align-items-center">
                                                <div className="col">
                                                    <div className="d-flex align-items-center mb-2">
                                                        <span className="avatar me-3 bg-primary text-white">
                                                            <IconMath size={24} />
                                                        </span>
                                                        <div>
                                                            <h3 className="mb-0">{formula.name}</h3>
                                                            <div className="d-flex align-items-center gap-2 mt-1">
                                                                <span className={`badge ${getReturnTypeBadgeColor(formula.return_type)} text-white`}>
                                                                    {formula.return_type}
                                                                </span>
                                                                {!formula.is_active && (
                                                                    <span className="badge bg-secondary text-white">Inactive</span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div className="mb-2">
                                                        <code className="bg-light px-2 py-1 rounded small">
                                                            {formula.expression}
                                                        </code>
                                                    </div>
                                                    
                                                    {formula.description && (
                                                        <p className="text-muted mb-2">{formula.description}</p>
                                                    )}
                                                    
                                                    <div className="text-muted small">
                                                        Product: {formula.product?.name || 'Global'} • 
                                                        Created by: {formula.creator?.name || 'Unknown'} • 
                                                        {new Date(formula.created_at).toLocaleDateString()}
                                                    </div>
                                                </div>
                                                <div className="col-auto">
                                                    <div className="btn-list">
                                                        <Link href={`/formulas/${formula.id}`} className="btn btn-white btn-sm">
                                                            <IconEye size={16} />
                                                        </Link>
                                                        <Link href={`/formulas/${formula.id}/edit`} className="btn btn-white btn-sm">
                                                            <IconEdit size={16} />
                                                        </Link>
                                                        <button 
                                                            className="btn btn-white btn-sm"
                                                            onClick={() => handleDuplicate(formula)}
                                                        >
                                                            <IconCopy size={16} />
                                                        </button>
                                                        <button 
                                                            className="btn btn-white btn-sm text-danger"
                                                            onClick={() => handleDelete(formula)}
                                                        >
                                                            <IconTrash size={16} />
                                                        </button>
                                                    </div>
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
                                                <IconMath size={48} className="text-muted" />
                                            </div>
                                            <p className="empty-title">No formulas found</p>
                                            <p className="empty-subtitle text-muted">
                                                Get started by creating your first custom formula
                                            </p>
                                            <div className="empty-action">
                                                <Link href="/formulas/create" className="btn btn-primary">
                                                    <IconPlus size={16} className="me-1" />
                                                    Create Formula
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Pagination */}
                        {formulas?.links && formulas.data && formulas.data.length > 0 && (
                            <div className="col-12">
                                <div className="card">
                                    <div className="card-footer d-flex align-items-center">
                                        <p className="m-0 text-muted">
                                            Showing <span>{formulas.from || 1}</span> to <span>{formulas.to || formulas.data.length}</span> of <span>{formulas.total || formulas.data.length}</span> entries
                                        </p>
                                        <ul className="pagination m-0 ms-auto">
                                            {formulas.links.map((link, index) => (
                                                <li key={index} className={`page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`}>
                                                    {link.url ? (
                                                        <Link href={link.url} className="page-link" dangerouslySetInnerHTML={{ __html: link.label }} />
                                                    ) : (
                                                        <span className="page-link" dangerouslySetInnerHTML={{ __html: link.label }} />
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
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