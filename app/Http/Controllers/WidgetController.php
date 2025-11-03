<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWidgetRequest;
use App\Http\Requests\UpdateWidgetRequest;
use App\Models\Dashboard;
use App\Models\Widget;
use App\Repositories\WidgetRepository;
use App\Services\ChartDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function __construct(
        private WidgetRepository $widgetRepository,
        private ChartDataService $chartDataService
    ) {}

    
    public function store(StoreWidgetRequest $request, Dashboard $dashboard): JsonResponse
    {
        $this->authorize('update', $dashboard);
        
        $widget = $this->widgetRepository->create([
            'dashboard_id' => $dashboard->id,
            'type' => $request->type,
            'configuration' => $request->configuration,
            'position' => $request->position ?? ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 4]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget created successfully',
            'data' => $widget
        ], 201);
    }

    
    public function update(UpdateWidgetRequest $request, Dashboard $dashboard, Widget $widget): JsonResponse
    {
        $this->authorize('update', $dashboard);
        
        if ($widget->dashboard_id !== $dashboard->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget does not belong to this dashboard'
            ], 403);
        }

        $updated = $this->widgetRepository->update($widget->id, [
            'type' => $request->type,
            'configuration' => $request->configuration,
            'position' => $request->position
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget updated successfully',
            'data' => $updated
        ]);
    }

    
    public function destroy(Dashboard $dashboard, Widget $widget): JsonResponse
    {
        $this->authorize('update', $dashboard);
        
        if ($widget->dashboard_id !== $dashboard->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget does not belong to this dashboard'
            ], 403);
        }

        $this->widgetRepository->delete($widget->id);

        return response()->json([
            'success' => true,
            'message' => 'Widget deleted successfully'
        ]);
    }

    
    public function updatePosition(Request $request, Dashboard $dashboard, Widget $widget): JsonResponse
    {
        $this->authorize('update', $dashboard);
        
        if ($widget->dashboard_id !== $dashboard->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget does not belong to this dashboard'
            ], 403);
        }

        $request->validate([
            'position' => 'required|array',
            'position.x' => 'required|integer|min:0',
            'position.y' => 'required|integer|min:0',
            'position.w' => 'required|integer|min:1',
            'position.h' => 'required|integer|min:1'
        ]);

        $updated = $this->widgetRepository->update($widget->id, [
            'position' => $request->position
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget position updated successfully',
            'data' => $updated
        ]);
    }

    
    public function getData(Request $request, Dashboard $dashboard, Widget $widget): JsonResponse
    {
        $this->authorize('view', $dashboard);
        
        if ($widget->dashboard_id !== $dashboard->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget does not belong to this dashboard'
            ], 403);
        }

        $data = $this->chartDataService->getChartData(
            $widget,
            $request->get('filters', [])
        );

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    
    public function duplicate(Dashboard $dashboard, Widget $widget): JsonResponse
    {
        $this->authorize('update', $dashboard);
        
        if ($widget->dashboard_id !== $dashboard->id) {
            return response()->json([
                'success' => false,
                'message' => 'Widget does not belong to this dashboard'
            ], 403);
        }

        $duplicated = $this->widgetRepository->duplicate($widget->id);

        return response()->json([
            'success' => true,
            'message' => 'Widget duplicated successfully',
            'data' => $duplicated
        ]);
    }
}


