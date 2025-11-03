<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $action = $request->get('action');
        $model = $request->get('model');
        $userId = $request->get('user_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = AuditLog::with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($model) {
            $query->where('model', $model);
        }

        // Remove user restriction - show all audit logs to all users
        // if ($userId) {
        //     $query->where('user_id', $userId);
        // }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $auditLogs = $query->latest()->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $auditLogs
            ]);
        }

        return Inertia::render('AuditLogs/Index', [
            'auditLogs' => $auditLogs,
            'users' => User::select('id', 'name', 'email')->get(),
            'filters' => [
                'search' => $search,
                'action' => $action,
                'model' => $model,
                'user_id' => $userId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ]);
    }

    
    public function show(AuditLog $auditLog, Request $request): Response|JsonResponse
    {
        $auditLog->load('user');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $auditLog
            ]);
        }

        return Inertia::render('AuditLogs/Show', [
            'auditLog' => $auditLog
        ]);
    }

    
    public function getByModel(Request $request): JsonResponse
    {
        $request->validate([
            'model' => 'required|string',
            'model_id' => 'required|integer'
        ]);

        try {
            $auditLogs = AuditLog::where('model', $request->model)
                ->where('model_id', $request->model_id)
                ->with('user')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $auditLogs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get audit logs.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getByUser(User $user): JsonResponse
    {
        try {
            // Show all audit logs to all users regardless of user
            $auditLogs = AuditLog::with('user')
                ->latest()
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $auditLogs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user audit logs.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function statistics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30));
            $dateTo = $request->get('date_to', now());

            $query = AuditLog::whereBetween('created_at', [$dateFrom, $dateTo]);

            $stats = [
                'total_actions' => $query->count(),
                'actions_by_type' => $query->selectRaw('action, COUNT(*) as count')
                    ->groupBy('action')
                    ->pluck('count', 'action'),
                'actions_by_model' => $query->selectRaw('model, COUNT(*) as count')
                    ->groupBy('model')
                    ->pluck('count', 'model'),
                'actions_by_user' => $query->with('user')
                    ->selectRaw('user_id, COUNT(*) as count')
                    ->groupBy('user_id')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->user->name ?? 'Unknown' => $item->count];
                    }),
                'recent_actions' => $query->with('user')
                    ->latest()
                    ->take(10)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get audit log statistics.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getActions(): JsonResponse
    {
        try {
            $actions = AuditLog::select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action');

            return response()->json([
                'success' => true,
                'data' => $actions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get actions.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getModels(): JsonResponse
    {
        try {
            $models = AuditLog::select('model')
                ->distinct()
                ->orderBy('model')
                ->pluck('model');

            return response()->json([
                'success' => true,
                'data' => $models
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get models.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        try {
            $query = AuditLog::with('user');

            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $auditLogs = $query->latest()->get();

            if ($request->format === 'csv') {
                $csvData = $auditLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user' => $log->user->name ?? 'System',
                        'action' => $log->action,
                        'model' => $log->model,
                        'model_id' => $log->model_id,
                        'ip_address' => $log->ip_address,
                        'created_at' => $log->created_at->format('Y-m-d H:i:s')
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data' => $csvData,
                    'format' => 'csv'
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => $auditLogs,
                    'format' => 'json'
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export audit logs.',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}



