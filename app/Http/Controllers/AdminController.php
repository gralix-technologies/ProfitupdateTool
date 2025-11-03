<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Admin']);
    }

    /**
     * Display user management page
     */
    public function userManagement()
    {
        $users = User::with('roles')->get();
        $roles = Role::all();

        return Inertia::render('Admin/UserManagement', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    /**
     * Display audit trail page
     */
    public function auditTrail(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters - remove user restriction
        // if ($request->filled('user_id')) {
        //     $query->where('user_id', $request->user_id);
        // }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->paginate(50);
        $users = User::select('id', 'name')->get();

        return Inertia::render('Admin/AuditTrail', [
            'auditLogs' => $auditLogs,
            'users' => $users
        ]);
    }

    /**
     * Export audit logs
     */
    public function exportAuditLogs(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Apply same filters as audit trail - remove user restriction
        // if ($request->filled('user_id')) {
        //     $query->where('user_id', $request->user_id);
        // }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($auditLogs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Timestamp', 'User', 'Event', 'Model', 'Description', 'IP Address'
            ]);

            // CSV data
            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at,
                    $log->user->name ?? 'Unknown',
                    $log->event,
                    $log->auditable_type,
                    $log->description,
                    $log->ip_address
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display role management page
     */
    public function roleManagement()
    {
        $roles = Role::with('permissions')->get();
        $permissions = \Spatie\Permission\Models\Permission::all();

        return Inertia::render('Admin/RoleManagement', [
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    /**
     * Display system settings page
     */
    public function systemSettings()
    {
        $configurations = \App\Models\Configuration::all();
        
        return Inertia::render('Admin/SystemSettings', [
            'configurations' => $configurations
        ]);
    }

    /**
     * Save system settings
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'nullable|url',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'session_timeout' => 'required|integer|min:5|max:1440',
            'max_login_attempts' => 'required|integer|min:3|max:20',
            'password_min_length' => 'required|integer|min:6|max:32',
        ]);

        $settings = $request->only([
            'app_name', 'app_url', 'mail_host', 'mail_port',
            'backup_frequency', 'session_timeout', 'max_login_attempts', 'password_min_length'
        ]);

        foreach ($settings as $key => $value) {
            \App\Models\Configuration::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return redirect()->route('admin.settings')
            ->with('success', 'System settings saved successfully!');
    }

    /**
     * Display system logs page (Spatie audit logs)
     */
    public function systemLogs()
    {
        // Get Spatie audit logs instead of Laravel logs
        $auditLogs = AuditLog::with(['user'])
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->user ? $log->user->name : 'System',
                    'user_email' => $log->user ? $log->user->email : null,
                    'event' => $log->event,
                    'auditable_type' => $log->auditable_type,
                    'auditable_id' => $log->auditable_id,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'created_at' => $log->created_at,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'url' => $log->url,
                ];
            });

        return Inertia::render('Admin/SystemLogs', [
            'auditLogs' => $auditLogs,
            'totalLogs' => AuditLog::count()
        ]);
    }
}
