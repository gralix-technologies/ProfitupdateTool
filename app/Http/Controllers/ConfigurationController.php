<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConfigurationRequest;
use App\Http\Requests\UpdateConfigurationRequest;
use App\Models\Configuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    
    public function index(Request $request): Response|JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = Configuration::query();

        if ($search) {
            $query->where('key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $configurations = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $configurations
            ]);
        }

        return Inertia::render('Configurations/Index', [
            'configurations' => $configurations,
            'filters' => ['search' => $search]
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('Configurations/Create');
    }

    
    public function store(StoreConfigurationRequest $request): JsonResponse
    {
        try {
            $configuration = Configuration::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Configuration created successfully.',
                'data' => $configuration
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create configuration.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function show(Configuration $configuration, Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $configuration
            ]);
        }

        return Inertia::render('Configurations/Show', [
            'configuration' => $configuration
        ]);
    }

    
    public function edit(Configuration $configuration): Response
    {
        return Inertia::render('Configurations/Edit', [
            'configuration' => $configuration
        ]);
    }

    
    public function update(UpdateConfigurationRequest $request, Configuration $configuration): JsonResponse
    {
        try {
            $configuration->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully.',
                'data' => $configuration
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function destroy(Configuration $configuration): JsonResponse
    {
        try {
            $configuration->delete();

            return response()->json([
                'success' => true,
                'message' => 'Configuration deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete configuration.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getValue(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string'
        ]);

        try {
            $value = Configuration::getValue($request->key, $request->get('default'));

            return response()->json([
                'success' => true,
                'key' => $request->key,
                'value' => $value
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get configuration value.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function setValue(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'description' => 'nullable|string'
        ]);

        try {
            Configuration::setValue(
                $request->key,
                $request->value,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuration value set successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set configuration value.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function getMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'keys' => 'required|array',
            'keys.*' => 'string'
        ]);

        try {
            $values = [];
            foreach ($request->keys as $key) {
                $values[$key] = Configuration::getValue($key);
            }

            return response()->json([
                'success' => true,
                'data' => $values
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get configuration values.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'configurations' => 'required|array',
            'configurations.*.key' => 'required|string',
            'configurations.*.value' => 'required',
            'configurations.*.description' => 'nullable|string'
        ]);

        try {
            $updated = [];
            $errors = [];

            foreach ($request->configurations as $index => $config) {
                try {
                    Configuration::setValue(
                        $config['key'],
                        $config['value'],
                        $config['description'] ?? null
                    );
                    $updated[] = $config['key'];
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'key' => $config['key'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed.',
                'updated' => $updated,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk update.',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}



