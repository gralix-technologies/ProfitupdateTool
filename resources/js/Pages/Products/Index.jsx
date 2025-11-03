import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconPlus, IconSearch, IconEye, IconEdit, IconTrash, IconDatabase, IconFilter, IconCopy } from '@tabler/icons-react';

export default function Index({ products, categories, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '');

    const handleSearch = () => {
        router.get('/products', { search, category }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch('');
        setCategory('');
        router.get('/products');
    };

    const handleDelete = (product) => {
        if (confirm(`Are you sure you want to delete "${product.name}"?`)) {
            router.delete(`/products/${product.id}`, {
                onSuccess: () => {
                    // Handle success
                },
                onError: (errors) => {
                    alert('Failed to delete product: ' + Object.values(errors).join(', '));
                }
            });
        }
    };

    const handleCopy = (product) => {
        if (confirm(`Create a copy of "${product.name}"?`)) {
            router.post(`/products/${product.id}/copy`, {}, {
                onSuccess: (page) => {
                    // Redirect handled by controller
                    const response = page.props.flash?.success || {};
                    if (response.redirect) {
                        router.visit(response.redirect);
                    }
                },
                onError: (errors) => {
                    alert('Failed to copy product: ' + Object.values(errors).join(', '));
                }
            });
        }
    };

    const getCategoryBadgeColor = (category) => {
        const colors = {
            'Loan': 'bg-blue',
            'Account': 'bg-green',
            'Deposit': 'bg-yellow',
            'Transaction': 'bg-purple',
            'Other': 'bg-gray'
        };
        return colors[category] || colors['Other'];
    };

    return (
        <AppLayout title="Products">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Financial Products
                            </div>
                            <h2 className="page-title">
                                Products
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/products/create" className="btn btn-primary d-none d-sm-inline-block">
                                    <IconPlus size={16} className="me-1" />
                                    Create Product
                                </Link>
                                <Link href="/products/create" className="btn btn-primary d-sm-none btn-icon">
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
                        {/* Filter Section */}
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconFilter size={18} className="me-2" />
                                        Filter Products
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="row g-3 align-items-end">
                                        <div className="col-md-4">
                                            <label className="form-label">Search Products</label>
                                            <div className="input-icon">
                                                <span className="input-icon-addon">
                                                    <IconSearch size={16} />
                                                </span>
                                                <input 
                                                    type="text" 
                                                    className="form-control" 
                                                    placeholder="Search by name or description..."
                                                    value={search}
                                                    onChange={(e) => setSearch(e.target.value)}
                                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-3">
                                            <label className="form-label">Category</label>
                                            <select 
                                                className="form-select" 
                                                value={category} 
                                                onChange={(e) => setCategory(e.target.value)}
                                            >
                                                <option value="">All Categories</option>
                                                {categories.map((cat) => (
                                                    <option key={cat} value={cat}>
                                                        {cat}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-3">
                                            <div className="btn-list">
                                                <button className="btn btn-primary" onClick={handleSearch}>
                                                    <IconSearch size={16} className="me-1" />
                                                    Search
                                                </button>
                                                <button className="btn btn-outline-secondary" onClick={handleReset}>
                                                    Clear Filters
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Products List */}
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconDatabase size={18} className="me-2" />
                                        Products ({products.total || products.data?.length || 0})
                                    </h3>
                                </div>

                                {products.data && products.data.length > 0 ? (
                                    <div className="table-responsive">
                                        <table className="table table-vcenter card-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Category</th>
                                                    <th>Fields</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {products.data.map((product) => (
                                                    <tr key={product.id}>
                                                        <td>
                                                            <div className="d-flex align-items-center">
                                                                <span className={`avatar me-3 ${getCategoryBadgeColor(product.category)} text-white`}>
                                                                    {product.name.charAt(0).toUpperCase()}
                                                                </span>
                                                                <div className="flex-fill">
                                                                    <div className="font-weight-medium text-dark">{product.name}</div>
                                                                    {product.description && (
                                                                        <div className="text-muted small">{product.description}</div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span className={`badge ${getCategoryBadgeColor(product.category)} text-white`}>
                                                                {product.category}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div className="text-muted fw-medium">
                                                                {product.field_definitions?.length || 0} fields
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span className={`badge ${product.is_active ? 'bg-success' : 'bg-secondary'} text-white`}>
                                                                {product.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div className="text-muted">
                                                                {new Date(product.created_at).toLocaleDateString()}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="btn-list flex-nowrap">
                                                                <Link href={`/products/${product.id}`} className="btn btn-primary btn-sm" title="View">
                                                                    <IconEye size={16} />
                                                                </Link>
                                                                <Link href={`/products/${product.id}/edit`} className="btn btn-outline-primary btn-sm" title="Edit">
                                                                    <IconEdit size={16} />
                                                                </Link>
                                                                <button 
                                                                    className="btn btn-outline-info btn-sm"
                                                                    onClick={() => handleCopy(product)}
                                                                    title="Copy Product"
                                                                >
                                                                    <IconCopy size={16} />
                                                                </button>
                                                                <button 
                                                                    className="btn btn-outline-danger btn-sm"
                                                                    onClick={() => handleDelete(product)}
                                                                    title="Delete"
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
                                    <div className="card-body">
                                        <div className="empty">
                                            <div className="empty-img">
                                                <IconDatabase size={48} className="text-muted" />
                                            </div>
                                            <p className="empty-title">No products found</p>
                                            <p className="empty-subtitle text-muted">
                                                Try adjusting your search criteria or create a new product
                                            </p>
                                            <div className="empty-action">
                                                <Link href="/products/create" className="btn btn-primary">
                                                    <IconPlus size={16} className="me-1" />
                                                    Add Product
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Pagination */}
                                {products.links && products.data && products.data.length > 0 && (
                                    <div className="card-footer d-flex align-items-center">
                                        <p className="m-0 text-muted">
                                            Showing <span>{products.from}</span> to <span>{products.to}</span> of <span>{products.total}</span> entries
                                        </p>
                                        <ul className="pagination m-0 ms-auto">
                                            {products.links.map((link, index) => (
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
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}