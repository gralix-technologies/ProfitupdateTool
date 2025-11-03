<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $role = $request->get('role');

        $query = User::with('roles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::select('id', 'name')->get(),
            'filters' => [
                'search' => $search,
                'role' => $role
            ]
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => Role::select('id', 'name')->get()
        ]);
    }

    
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['password'] = Hash::make($validated['password']);

            $user = User::create($validated);

            if ($request->has('roles')) {
                $user->assignRole($request->roles);
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data' => $user->load('roles')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function show(User $user, Request $request): Response|JsonResponse
    {
        $user->load(['roles', 'dashboards', 'formulas']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        }

        return Inertia::render('Users/Show', [
            'user' => $user
        ]);
    }

    
    public function edit(User $user): Response
    {
        $user->load('roles');

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => Role::select('id', 'name')->get()
        ]);
    }

    
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => $user->load('roles')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(User $user): JsonResponse
    {
        try {
            if ($user->hasRole('Admin') && User::role('Admin')->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last admin user.'
                ], 422);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function updateRoles(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        try {
            $user->syncRoles($request->roles);

            return response()->json([
                'success' => true,
                'message' => 'User roles updated successfully.',
                'data' => $user->load('roles')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user roles.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function activity(User $user): JsonResponse
    {
        try {
            $activity = [
                'dashboards_count' => $user->dashboards()->count(),
                'formulas_count' => $user->formulas()->count(),
                'last_login' => $user->last_login_at ?? 'Never',
                'created_at' => $user->created_at,
                'recent_dashboards' => $user->dashboards()->latest()->take(5)->get(),
                'recent_formulas' => $user->formulas()->latest()->take(5)->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $activity
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user activity.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::whereNotNull('email_verified_at')->count(),
                'admin_users' => User::role('Admin')->count(),
                'analyst_users' => User::role('Analyst')->count(),
                'viewer_users' => User::role('Viewer')->count(),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user statistics.',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}



