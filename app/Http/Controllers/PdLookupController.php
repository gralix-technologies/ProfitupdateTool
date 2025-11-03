<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePdLookupRequest;
use App\Http\Requests\UpdatePdLookupRequest;
use App\Models\PdLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PdLookupController extends Controller
{
    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = PdLookup::query();

        if ($search) {
            $query->where('credit_rating', 'like', "%{$search}%");
        }

        $pdLookups = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $pdLookups
            ]);
        }

        return Inertia::render('PdLookups/Index', [
            'pdLookups' => $pdLookups,
            'filters' => ['search' => $search]
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('PdLookups/Create');
    }

    
    public function store(StorePdLookupRequest $request): JsonResponse
    {
        try {
            $pdLookup = PdLookup::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'PD Lookup created successfully.',
                'data' => $pdLookup
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function show(PdLookup $pdLookup, Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $pdLookup
            ]);
        }

        return Inertia::render('PdLookups/Show', [
            'pdLookup' => $pdLookup
        ]);
    }

    
    public function edit(PdLookup $pdLookup): Response
    {
        return Inertia::render('PdLookups/Edit', [
            'pdLookup' => $pdLookup
        ]);
    }

    
    public function update(UpdatePdLookupRequest $request, PdLookup $pdLookup): JsonResponse
    {
        try {
            $pdLookup->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'PD Lookup updated successfully.',
                'data' => $pdLookup
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(PdLookup $pdLookup): JsonResponse
    {
        try {
            $pdLookup->delete();

            return response()->json([
                'success' => true,
                'message' => 'PD Lookup deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getByRating(Request $request): JsonResponse
    {
        $request->validate([
            'credit_rating' => 'required|string'
        ]);

        try {
            $pdLookup = PdLookup::where('credit_rating', $request->credit_rating)->first();

            if (!$pdLookup) {
                return response()->json([
                    'success' => false,
                    'message' => 'PD Lookup not found for the given credit rating.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $pdLookup
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PD Lookup.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'pd_lookups' => 'required|array',
            'pd_lookups.*.credit_rating' => 'required|string',
            'pd_lookups.*.pd_default' => 'required|numeric|min:0|max:1'
        ]);

        try {
            $created = [];
            $errors = [];

            foreach ($request->pd_lookups as $index => $pdLookupData) {
                try {
                    $pdLookup = PdLookup::create($pdLookupData);
                    $created[] = $pdLookup;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'data' => $pdLookupData,
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

    
    public function getCreditRatings(): JsonResponse
    {
        try {
            $ratings = PdLookup::select('credit_rating')
                ->distinct()
                ->orderBy('credit_rating')
                ->pluck('credit_rating');

            return response()->json([
                'success' => true,
                'data' => $ratings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get credit ratings.',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}



