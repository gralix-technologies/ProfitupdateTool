import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { IconEdit, IconUser, IconMail, IconCalendar } from '@tabler/icons-react';

export default function Show({ user }) {
    return (
        <AppLayout title="Profile">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Account
                            </div>
                            <h2 className="page-title">
                                Profile
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/profile/edit" className="btn btn-primary">
                                    <IconEdit size={16} className="me-2" />
                                    Edit Profile
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    <div className="row row-deck row-cards">
                        <div className="col-md-6 col-lg-4">
                            <Card>
                                <CardContent className="text-center">
                                    <div className="avatar avatar-xl mb-3 bg-primary text-white">
                                        {user.name?.charAt(0) || 'U'}
                                    </div>
                                    <h3 className="m-0 mb-1">{user.name}</h3>
                                    <div className="text-muted">{user.email}</div>
                                    <div className="mt-3">
                                        <Link href="/profile/edit" className="btn btn-outline-primary">
                                            Edit Profile
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        
                        <div className="col-md-6 col-lg-8">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Profile Information</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <div className="form-label d-flex align-items-center">
                                                    <IconUser size={16} className="me-2" />
                                                    Full Name
                                                </div>
                                                <div className="form-control-plaintext">{user.name}</div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <div className="form-label d-flex align-items-center">
                                                    <IconMail size={16} className="me-2" />
                                                    Email Address
                                                </div>
                                                <div className="form-control-plaintext">{user.email}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <div className="form-label d-flex align-items-center">
                                                    <IconCalendar size={16} className="me-2" />
                                                    Member Since
                                                </div>
                                                <div className="form-control-plaintext">
                                                    {new Date(user.created_at).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="mb-3">
                                                <div className="form-label">Account Status</div>
                                                <div className="form-control-plaintext">
                                                    <span className="badge bg-success">Active</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}