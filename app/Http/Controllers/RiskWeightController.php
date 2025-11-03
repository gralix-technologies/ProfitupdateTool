<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRiskWeightRequest;
use App\Http\Requests\UpdateRiskWeightRequest;
use App\Models\RiskWeight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiskWeightController extends Controller
{
    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $creditRating = $request->get('credit_rating');
        $collateralType = $request->get('collateral_type');

        $query = RiskWeight::query();

        if ($search) {
            $query->where('credit_rating', 'like', "%{$search}%")
                  ->orWhere('collateral_type', 'like', "%{$search}%");
        }

        if ($creditRating) {
            $query->where('credit_rating', $creditRating);
        }

        if ($collateralType) {
            $query->where('collateral_type', $collateralType);
        }

        $riskWeights = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $riskWeights
            ]);
        }

        return Inertia::render('RiskWeights/Index', [
            'riskWeights' => $riskWeights,
            'filters' => [
                'search' => $search,
                'credit_rating' => $creditRating,
                'collateral_type' => $collateralType
            ]
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('RiskWeights/Create');
    }

    
    public function store(StoreRiskWeightRequest $request): JsonResponse
    {
        try {
            $riskWeight = RiskWeight::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Risk Weight created successfully.',
                'data' => $riskWeight
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Risk Weight.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function show(RiskWeight $riskWeight, Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $riskWeight
            ]);
        }

        return Inertia::render('RiskWeights/Show', [
            'riskWeight' => $riskWeight
        ]);
    }

    
    public function edit(RiskWeight $riskWeight): Response
    {
        return Inertia::render('RiskWeights/Edit', [
            'riskWeight' => $riskWeight
        ]);
    }

    
    public function update(UpdateRiskWeightRequest $request, RiskWeight $riskWeight): JsonResponse
    {
        try {
            $riskWeight->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Risk Weight updated successfully.',
                'data' => $riskWeight
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Risk Weight.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(RiskWeight $riskWeight): JsonResponse
    {
        try {
            $riskWeight->delete();

            return response()->json([
                'success' => true,
                'message' => 'Risk Weight deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Risk Weight.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getByRatingAndCollateral(Request $request): JsonResponse
    {
        $request->validate([
            'credit_rating' => 'required|string',
            'collateral_type' => 'required|string'
        ]);

        try {
            $riskWeight = RiskWeight::where('credit_rating', $request->credit_rating)
                ->where('collateral_type', $request->collateral_type)
                ->first();

            if (!$riskWeight) {
                return response()->json([
                    'success' => false,
                    'message' => 'Risk Weight not found for the given parameters.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $riskWeight
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Risk Weight.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'risk_weights' => 'required|array',
            'risk_weights.*.credit_rating' => 'required|string',
            'risk_weights.*.collateral_type' => 'required|string',
            'risk_weights.*.risk_weight_percent' => 'required|numeric|min:0|max:100'
        ]);

        try {
            $created = [];
            $errors = [];

            foreach ($request->risk_weights as $index => $riskWeightData) {
                try {
                    $riskWeight = RiskWeight::create($riskWeightData);
                    $created[] = $riskWeight;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'data' => $riskWeightData,
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
            $ratings = RiskWeight::select('credit_rating')
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

    
    public function getCollateralTypes(): JsonResponse
    {
        try {
            $types = RiskWeight::select('collateral_type')
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



