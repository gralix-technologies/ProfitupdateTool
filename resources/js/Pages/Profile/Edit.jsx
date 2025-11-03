import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { IconArrowLeft, IconUser, IconLock } from '@tabler/icons-react';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Edit({ user }) {
    const [activeTab, setActiveTab] = useState(() => {
        // Check if URL has #password hash to show password tab
        if (typeof window !== 'undefined' && window.location.hash === '#password') {
            return 'password';
        }
        return 'profile';
    });

    // Profile form
    const profileForm = useFormWithCsrf({
        name: user.name || '',
        email: user.email || '',
    });

    // Password form
    const passwordForm = useFormWithCsrf({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        profileForm.put('/profile');
    };

    const handlePasswordSubmit = (e) => {
        e.preventDefault();
        passwordForm.put('/profile/password', {
            onSuccess: () => {
                passwordForm.reset();
            }
        });
    };

    return (
        <AppLayout title="Edit Profile">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Account Settings
                            </div>
                            <h2 className="page-title">
                                Edit Profile
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <Link href="/profile" className="btn">
                                    <IconArrowLeft size={16} className="me-2" />
                                    Back to Profile
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    <div className="row row-deck row-cards">
                        <div className="col-12">
                            <div className="card">
                                <div className="card-header">
                                    <ul className="nav nav-tabs card-header-tabs">
                                        <li className="nav-item">
                                            <button 
                                                className={`nav-link ${activeTab === 'profile' ? 'active' : ''}`}
                                                onClick={() => setActiveTab('profile')}
                                            >
                                                <IconUser size={16} className="me-2" />
                                                Profile Information
                                            </button>
                                        </li>
                                        <li className="nav-item">
                                            <button 
                                                className={`nav-link ${activeTab === 'password' ? 'active' : ''}`}
                                                onClick={() => setActiveTab('password')}
                                            >
                                                <IconLock size={16} className="me-2" />
                                                Change Password
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <div className="card-body">
                                    {activeTab === 'profile' && (
                                        <form onSubmit={handleProfileSubmit}>
                                            <div className="row">
                                                <div className="col-md-6">
                                                    <div className="mb-3">
                                                        <label className="form-label">Full Name</label>
                                                        <input
                                                            type="text"
                                                            className={`form-control ${profileForm.errors.name ? 'is-invalid' : ''}`}
                                                            value={profileForm.data.name}
                                                            onChange={(e) => profileForm.setData('name', e.target.value)}
                                                            placeholder="Enter your full name"
                                                        />
                                                        {profileForm.errors.name && (
                                                            <div className="invalid-feedback">{profileForm.errors.name}</div>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="col-md-6">
                                                    <div className="mb-3">
                                                        <label className="form-label">Email Address</label>
                                                        <input
                                                            type="email"
                                                            className={`form-control ${profileForm.errors.email ? 'is-invalid' : ''}`}
                                                            value={profileForm.data.email}
                                                            onChange={(e) => profileForm.setData('email', e.target.value)}
                                                            placeholder="Enter your email address"
                                                        />
                                                        {profileForm.errors.email && (
                                                            <div className="invalid-feedback">{profileForm.errors.email}</div>
                                                        )}
                                                        <div className="form-hint">
                                                            We'll never share your email with anyone else.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="form-footer">
                                                <button 
                                                    type="submit" 
                                                    className="btn btn-primary"
                                                    disabled={profileForm.processing}
                                                >
                                                    {profileForm.processing ? (
                                                        <>
                                                            <div className="spinner-border spinner-border-sm me-2" role="status"></div>
                                                            Updating...
                                                        </>
                                                    ) : (
                                                        'Update Profile'
                                                    )}
                                                </button>
                                            </div>
                                        </form>
                                    )}

                                    {activeTab === 'password' && (
                                        <form onSubmit={handlePasswordSubmit}>
                                            <div className="row">
                                                <div className="col-md-8">
                                                    <div className="mb-3">
                                                        <label className="form-label">Current Password</label>
                                                        <input
                                                            type="password"
                                                            className={`form-control ${passwordForm.errors.current_password ? 'is-invalid' : ''}`}
                                                            value={passwordForm.data.current_password}
                                                            onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                                            placeholder="Enter your current password"
                                                        />
                                                        {passwordForm.errors.current_password && (
                                                            <div className="invalid-feedback">{passwordForm.errors.current_password}</div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="row">
                                                <div className="col-md-6">
                                                    <div className="mb-3">
                                                        <label className="form-label">New Password</label>
                                                        <input
                                                            type="password"
                                                            className={`form-control ${passwordForm.errors.password ? 'is-invalid' : ''}`}
                                                            value={passwordForm.data.password}
                                                            onChange={(e) => passwordForm.setData('password', e.target.value)}
                                                            placeholder="Enter new password"
                                                        />
                                                        {passwordForm.errors.password && (
                                                            <div className="invalid-feedback">{passwordForm.errors.password}</div>
                                                        )}
                                                        <div className="form-hint">
                                                            Password should be at least 8 characters long.
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="col-md-6">
                                                    <div className="mb-3">
                                                        <label className="form-label">Confirm New Password</label>
                                                        <input
                                                            type="password"
                                                            className="form-control"
                                                            value={passwordForm.data.password_confirmation}
                                                            onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                                            placeholder="Confirm new password"
                                                        />
                                                        <div className="form-hint">
                                                            Re-enter your new password to confirm.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="form-footer">
                                                <button 
                                                    type="submit" 
                                                    className="btn btn-primary"
                                                    disabled={passwordForm.processing}
                                                >
                                                    {passwordForm.processing ? (
                                                        <>
                                                            <div className="spinner-border spinner-border-sm me-2" role="status"></div>
                                                            Updating...
                                                        </>
                                                    ) : (
                                                        'Update Password'
                                                    )}
                                                </button>
                                            </div>
                                        </form>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}