<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLgdLookupRequest;
use App\Http\Requests\UpdateLgdLookupRequest;
use App\Models\LgdLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LgdLookupController extends Controller
{
    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = LgdLookup::query();

        if ($search) {
            $query->where('collateral_type', 'like', "%{$search}%");
        }

        $lgdLookups = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $lgdLookups
            ]);
        }

        return Inertia::render('LgdLookups/Index', [
            'lgdLookups' => $lgdLookups,
            'filters' => ['search' => $search]
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('LgdLookups/Create');
    }

    
    public function store(StoreLgdLookupRequest $request): JsonResponse
    {
        try {
            $lgdLookup = LgdLookup::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'LGD Lookup created successfully.',
                'data' => $lgdLookup
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create LGD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function show(LgdLookup $lgdLookup, Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $lgdLookup
            ]);
        }

        return Inertia::render('LgdLookups/Show', [
            'lgdLookup' => $lgdLookup
        ]);
    }

    
    public function edit(LgdLookup $lgdLookup): Response
    {
        return Inertia::render('LgdLookups/Edit', [
            'lgdLookup' => $lgdLookup
        ]);
    }

    
    public function update(UpdateLgdLookupRequest $request, LgdLookup $lgdLookup): JsonResponse
    {
        try {
            $lgdLookup->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'LGD Lookup updated successfully.',
                'data' => $lgdLookup
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update LGD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(LgdLookup $lgdLookup): JsonResponse
    {
        try {
            $lgdLookup->delete();

            return response()->json([
                'success' => true,
                'message' => 'LGD Lookup deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete LGD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getByCollateralType(Request $request): JsonResponse
    {
        $request->validate([
            'collateral_type' => 'required|string'
        ]);

        try {
            $lgdLookup = LgdLookup::where('collateral_type', $request->collateral_type)->first();

            if (!$lgdLookup) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGD Lookup not found for the given collateral type.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $lgdLookup
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get LGD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'lgd_lookups' => 'required|array',
            'lgd_lookups.*.collateral_type' => 'required|string',
            'lgd_lookups.*.lgd_default' => 'required|numeric|min:0|max:1'
        ]);

        try {
            $created = [];
            $errors = [];

            foreach ($request->lgd_lookups as $index => $lgdLookupData) {
                try {
                    $lgdLookup = LgdLookup::create($lgdLookupData);
                    $created[] = $lgdLookup;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'data' => $lgdLookupData,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk creation completed.',
                'created_count' => count($created),
                'error_count' => count($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk creation.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getCollateralTypes(): JsonResponse
    {
        try {
            $types = LgdLookup::select('collateral_type')
                ->distinct()
                ->orderBy('collateral_type')
                ->pluck('collateral_type');

            return response()->json([
                'success' => true,
                'data' => $types
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get collateral types.',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}



