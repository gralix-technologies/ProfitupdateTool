import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconArrowLeft, IconDeviceFloppy, IconTrash } from '@tabler/icons-react';
import FormulaForm from '@/Components/Formulas/FormulaForm';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Edit({ formula, products, returnTypes, supportedOperations }) {
    const { data, setData, put, processing, errors } = useFormWithCsrf({
        name: formula.name || '',
        description: formula.description || '',
        expression: formula.expression || '',
        product_id: formula.product_id || '',
        return_type: formula.return_type || 'numeric',
        is_active: formula.is_active ?? true
    });

    const { delete: destroy, processing: deleting } = useForm();

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/formulas/${formula.id}`, {
            onSuccess: () => {
                // Handle success
            }
        });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this formula? This action cannot be undone.')) {
            destroy(`/formulas/${formula.id}`);
        }
    };

    return (
        <AppLayout title={`Edit Formula: ${formula.name}`}>
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb">
                                    <li className="breadcrumb-item">
                                        <Link href="/formulas">Formulas</Link>
                                    </li>
                                    <li className="breadcrumb-item">
                                        <Link href={`/formulas/${formula.id}`}>{formula.name}</Link>
                                    </li>
                                    <li className="breadcrumb-item active" aria-current="page">Edit</li>
                                </ol>
                            </nav>
                            <div className="page-pretitle">
                                Formula Management
                            </div>
                            <h2 className="page-title">
                                Edit Formula: {formula.name}
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href={`/formulas/${formula.id}`} className="btn">
                                    <IconArrowLeft size={16} className="me-1" />
                                    Back to Formula
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
                                <FormulaForm
                                    data={data}
                                    setData={setData}
                                    errors={errors}
                                    products={products}
                                    returnTypes={returnTypes}
                                    supportedOperations={supportedOperations}
                                    processing={processing}
                                    submitText={processing ? 'Updating...' : 'Update Formula'}
                                    cancelUrl={`/formulas/${formula.id}`}
                                />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}