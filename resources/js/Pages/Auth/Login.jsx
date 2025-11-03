import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { useFormWithCsrf } from '@/Hooks/useFormWithCsrf';

export default function Login() {
    const { data, setData, post, processing, errors } = useFormWithCsrf({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login" />
            
            <div className="page page-center login-gradient">
                <div className="container container-tight py-4">
                    <div className="text-center mb-4">
                        <img 
                            src="https://www.gralix.co/assets/images/resources/logo-gralix.png" 
                            alt="Gralix Logo" 
                            className="mb-3"
                            style={{ height: '48px', width: 'auto' }}
                        />
                        <h1 className="h2" style={{ color: '#222551' }}>Portfolio Analytics Platform</h1>
                        <p className="text-muted">Powered by Gralix</p>
                    </div>
                    
                    <div className="card card-md">
                        <div className="card-body">
                            <h2 className="h2 text-center mb-4">Login to your account</h2>
                            
                            <form onSubmit={handleSubmit}>
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
                                
                                <div className="mb-2">
                                    <label className="form-label">
                                        Password
                                        <span className="form-label-description">
                                            <a href="/forgot-password">I forgot password</a>
                                        </span>
                                    </label>
                                    <input
                                        type="password"
                                        className={`form-control ${errors.password ? 'is-invalid' : ''}`}
                                        placeholder="Your password"
                                        autoComplete="current-password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    {errors.password && (
                                        <div className="invalid-feedback">{errors.password}</div>
                                    )}
                                </div>
                                
                                <div className="mb-2">
                                    <label className="form-check">
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                        />
                                        <span className="form-check-label">Remember me on this device</span>
                                    </label>
                                </div>
                                
                                <div className="form-footer">
                                    <button 
                                        type="submit" 
                                        className="btn btn-primary w-100"
                                        disabled={processing}
                                    >
                                        {processing ? 'Signing in...' : 'Sign in'}
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div className="hr-text">or</div>
                        
                        <div className="card-body">
                            <div className="row">
                                <div className="col">
                                    <a href="/register" className="btn w-100">
                                        Create new account
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="text-center text-muted mt-3">
                        Don't have account yet? <a href="/register">Sign up</a>
                    </div>
                </div>
            </div>
        </>
    );
}