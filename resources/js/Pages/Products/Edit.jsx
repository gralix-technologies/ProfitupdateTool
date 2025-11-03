import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconArrowLeft, IconDeviceFloppy, IconTrash } from '@tabler/icons-react';
import ProductForm from '@/Components/Products/ProductForm';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Edit({ product, categories, field_types }) {
    const { data, setData, put, processing, errors } = useFormWithCsrf({
        name: product.name || '',
        description: product.description || '',
        category: product.category || '',
        field_definitions: product.field_definitions || [],
        portfolio_value_field: product.portfolio_value_field || '',
        is_active: product.is_active ?? true,
        _token: null // Will be auto-populated by useFormWithCsrf
    });

    const { delete: destroy, processing: deleting } = useFormWithCsrf();

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/products/${product.id}`, {
            onSuccess: () => {
                // Handle success
            }
        });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            destroy(`/products/${product.id}`);
        }
    };

    return (
        <AppLayout title={`Edit Product: ${product.name}`}>
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/products">Products</Link>
                                    </li>
                                    <li className="breadcrumb-item">
                                        <Link href={`/products/${product.id}`}>{product.name}</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">Edit</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Product Management
                            </div>
                            <h2 className="page-title">
                                Edit Product: {product.name}
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href={`/products/${product.id}`} className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Product
                                </Link>
                                <button 
                                    type="button" 
                                    className="btn btn-danger"
                                    onClick={handleDelete}
                                    disabled={deleting}
                                >
                                    <IconTrash size={16} className="me-1" />
                                    {deleting ? 'Deleting...' : 'Delete'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-fluid px-3">
                    <div className="row g-3">
                        <div className="col-12">
                            <form onSubmit={handleSubmit}>
                                <ProductForm
                                    data={data}
                                    setData={setData}
                                    errors={errors}
                                    categories={categories}
                                    field_types={field_types}
                                    processing={processing}
                                    submitText={processing ? 'Updating...' : 'Update Product'}
                                    cancelUrl={`/products/${product.id}`}
                                />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}