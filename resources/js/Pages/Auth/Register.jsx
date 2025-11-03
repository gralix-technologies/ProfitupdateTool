import React from 'react';
import { Head } from '@inertiajs/react';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Register() {
    const { data, setData, post, processing, errors } = useFormWithCsrf({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <>
            <Head title="Register" />
            
            <div className="page page-center">
                <div className="container container-tight py-4">
                    <div className="text-center mb-4">
                        <h1 className="h2 text-primary">Portfolio Analytics Platform</h1>
                    </div>
                    
                    <div className="card card-md">
                        <div className="card-body">
                            <h2 className="h2 text-center mb-4">Create new account</h2>
                            
                            <form onSubmit={handleSubmit}>
                                <div className="mb-3">
                                    <label className="form-label">Full Name</label>
                                    <input
                                        type="text"
                                        className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                        placeholder="Enter your full name"
                                        autoComplete="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                    {errors.name && (
                                        <div className="invalid-feedback">{errors.name}</div>
                                    )}
                                </div>
                                
                                <div className="mb-3">
                                    <label className="form-label">Email address</label>
                                    <input
                                        type="email"
                                        className={`form-control ${errors.email ? 'is-invalid' : ''}`}
                                        placeholder="your@email.com"
                                        autoComplete="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                    {errors.email && (
                                        <div className="invalid-feedback">{errors.email}</div>
                                    )}
                                </div>
                                
                                <div className="mb-3">
                                    <label className="form-label">Password</label>
                                    <input
                                        type="password"
                                        className={`form-control ${errors.password ? 'is-invalid' : ''}`}
                                        placeholder="Your password"
                                        autoComplete="new-password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    {errors.password && (
                                        <div className="invalid-feedback">{errors.password}</div>
                                    )}
                                </div>
                                
                                <div className="mb-3">
                                    <label className="form-label">Confirm Password</label>
                                    <input
                                        type="password"
                                        className={`form-control ${errors.password_confirmation ? 'is-invalid' : ''}`}
                                        placeholder="Confirm your password"
                                        autoComplete="new-password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                    />
                                    {errors.password_confirmation && (
                                        <div className="invalid-feedback">{errors.password_confirmation}</div>
                                    )}
                                </div>
                                
                                <div className="mb-3">
                                    <label className="form-check">
                                        <input type="checkbox" className="form-check-input" required />
                                        <span className="form-check-label">
                                            I agree to the <a href="/terms">terms and policy</a>.
                                        </span>
                                    </label>
                                </div>
                                
                                <div className="form-footer">
                                    <button 
                                        type="submit" 
                                        className="btn btn-primary w-100"
                                        disabled={processing}
                                    >
                                        {processing ? 'Creating account...' : 'Create new account'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div className="text-center text-muted mt-3">
                        Already have account? <a href="/login">Sign in</a>
                    </div>
                </div>
            </div>
        </>
    );
}