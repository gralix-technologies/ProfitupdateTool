import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
    IconSettings, 
    IconDeviceFloppy,
    IconServer,
    IconDatabase,
    IconMail,
    IconShield,
    IconCloudDownload,
    IconRefresh,
    IconCheck,
    IconAlertCircle
} from '@tabler/icons-react';
import AppLayout from '@/Layouts/AppLayout';

export default function SystemSettings({ configurations, auth }) {
    const [settings, setSettings] = useState({
        app_name: configurations?.find(c => c.key === 'app_name')?.value || 'Portfolio Analytics Platform',
        app_url: configurations?.find(c => c.key === 'app_url')?.value || '',
        mail_host: configurations?.find(c => c.key === 'mail_host')?.value || '',
        mail_port: configurations?.find(c => c.key === 'mail_port')?.value || '587',
        backup_frequency: configurations?.find(c => c.key === 'backup_frequency')?.value || 'daily',
        session_timeout: configurations?.find(c => c.key === 'session_timeout')?.value || '120',
        max_login_attempts: configurations?.find(c => c.key === 'max_login_attempts')?.value || '5',
        password_min_length: configurations?.find(c => c.key === 'password_min_length')?.value || '8',
    });

    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState('');

    const handleSave = () => {
        setSaving(true);
        setMessage('');
        
        router.post('/admin/settings', settings, {
            onSuccess: () => {
                setMessage('Settings saved successfully!');
                setSaving(false);
            },
            onError: (errors) => {
                setMessage('Error saving settings. Please try again.');
                setSaving(false);
            }
        });
    };

    const handleReset = () => {
        if (confirm('Are you sure you want to reset all settings to default values?')) {
            setSettings({
                app_name: 'Portfolio Analytics Platform',
                app_url: '',
                mail_host: '',
                mail_port: '587',
                backup_frequency: 'daily',
                session_timeout: '120',
                max_login_attempts: '5',
                password_min_length: '8',
            });
            setMessage('Settings reset to defaults.');
        }
    };

    const updateSetting = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
    };

    return (
        <AppLayout title="System Settings">
            <div className="page-header d-print-none">
                <div className="container-xl">
                    <div className="row g-2 align-items-center">
                        <div className="col">
                            <div className="page-pretitle">
                                Admin
                            </div>
                            <h2 className="page-title">
                                System Settings
                            </h2>
                        </div>
                        <div className="col-auto ms-auto d-print-none">
                            <div className="btn-list">
                                <button className="btn btn-outline-warning" onClick={handleReset}>
                                    <IconRefresh size={16} className="me-1" />
                                    Reset
                                </button>
                                <button className="btn btn-primary" onClick={handleSave} disabled={saving}>
                                    <IconDeviceFloppy size={16} className="me-1" />
                                    {saving ? 'Saving...' : 'Save Settings'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="page-body">
                <div className="container-xl">
                    {message && (
                        <div className={`alert alert-${message.includes('Error') ? 'danger' : 'success'} alert-dismissible`}>
                            <div className="d-flex">
                                {message.includes('Error') ? <IconAlertCircle size={16} className="me-2" /> : <IconCheck size={16} className="me-2" />}
                                {message}
                            </div>
                            <button type="button" className="btn-close" onClick={() => setMessage('')}></button>
                        </div>
                    )}

                    <div className="row">
                        {/* Application Settings */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconSettings size={20} className="me-2" />
                                        Application Settings
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="mb-3">
                                        <label className="form-label">Application Name</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={settings.app_name}
                                            onChange={(e) => updateSetting('app_name', e.target.value)}
                                            placeholder="Enter application name"
                                        />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label">Application URL</label>
                                        <input
                                            type="url"
                                            className="form-control"
                                            value={settings.app_url}
                                            onChange={(e) => updateSetting('app_url', e.target.value)}
                                            placeholder="https://your-domain.com"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Mail Settings */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconMail size={20} className="me-2" />
                                        Mail Configuration
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="mb-3">
                                        <label className="form-label">Mail Host</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={settings.mail_host}
                                            onChange={(e) => updateSetting('mail_host', e.target.value)}
                                            placeholder="smtp.gmail.com"
                                        />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label">Mail Port</label>
                                        <input
                                            type="number"
                                            className="form-control"
                                            value={settings.mail_port}
                                            onChange={(e) => updateSetting('mail_port', e.target.value)}
                                            placeholder="587"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Security Settings */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconShield size={20} className="me-2" />
                                        Security Settings
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="mb-3">
                                        <label className="form-label">Session Timeout (minutes)</label>
                                        <input
                                            type="number"
                                            className="form-control"
                                            value={settings.session_timeout}
                                            onChange={(e) => updateSetting('session_timeout', e.target.value)}
                                            placeholder="120"
                                        />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label">Max Login Attempts</label>
                                        <input
                                            type="number"
                                            className="form-control"
                                            value={settings.max_login_attempts}
                                            onChange={(e) => updateSetting('max_login_attempts', e.target.value)}
                                            placeholder="5"
                                        />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label">Minimum Password Length</label>
                                        <input
                                            type="number"
                                            className="form-control"
                                            value={settings.password_min_length}
                                            onChange={(e) => updateSetting('password_min_length', e.target.value)}
                                            placeholder="8"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Backup Settings */}
                        <div className="col-md-6">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <IconCloudDownload size={20} className="me-2" />
                                        Backup & Maintenance
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="mb-3">
                                        <label className="form-label">Backup Frequency</label>
                                        <select
                                            className="form-select"
                                            value={settings.backup_frequency}
                                            onChange={(e) => updateSetting('backup_frequency', e.target.value)}
                                        >
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                    </div>
                                    <div className="alert alert-info">
                                        <IconServer size={16} className="me-2" />
                                        <strong>Database:</strong> MySQL - {configurations?.length || 0} configurations loaded
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* System Information */}
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title">
                                <IconServer size={20} className="me-2" />
                                System Information
                            </h3>
                        </div>
                        <div className="card-body">
                            <div className="row">
                                <div className="col-md-4">
                                    <div className="d-flex align-items-center mb-2">
                                        <IconDatabase size={16} className="me-2 text-muted" />
                                        <strong>Database:</strong>
                                        <span className="ms-2 text-muted">MySQL</span>
                                    </div>
                                </div>
                                <div className="col-md-4">
                                    <div className="d-flex align-items-center mb-2">
                                        <IconSettings size={16} className="me-2 text-muted" />
                                        <strong>PHP Version:</strong>
                                        <span className="ms-2 text-muted">{typeof window !== 'undefined' ? '8.2+' : '8.2+'}</span>
                                    </div>
                                </div>
                                <div className="col-md-4">
                                    <div className="d-flex align-items-center mb-2">
                                        <IconShield size={16} className="me-2 text-muted" />
                                        <strong>Framework:</strong>
                                        <span className="ms-2 text-muted">Laravel</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}