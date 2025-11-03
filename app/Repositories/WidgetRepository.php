<?php

namespace App\Repositories;

use App\Models\Widget;
use App\Models\Dashboard;
use Illuminate\Database\Eloquent\Collection;

class WidgetRepository
{
    
    public function create(array $data): Widget
    {
        return Widget::create($data);
    }

    
    public function findById(int $id): ?Widget
    {
        return Widget::with('dashboard')->find($id);
    }

    
    public function update(Widget $widget, array $data): Widget
    {
        $widget->update($data);
        return $widget->fresh('dashboard');
    }

    
    public function delete(Widget $widget): bool
    {
        return $widget->delete();
    }

    
    public function getByDashboard(Dashboard $dashboard): Collection
    {
        return $dashboard->widgets()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();
    }

    
    public function updatePosition(Widget $widget, array $position): Widget
    {
        $widget->updatePosition($position);
        return $widget->fresh('dashboard');
    }

    
    public function updateConfiguration(Widget $widget, array $configuration): Widget
    {
        $widget->updateConfiguration($configuration);
        return $widget->fresh('dashboard');
    }

    
    public function bulkUpdatePositions(array $widgets): bool
    {
        foreach ($widgets as $widgetData) {
            if (isset($widgetData['id']) && isset($widgetData['position'])) {
                $widget = $this->findById($widgetData['id']);
                if ($widget) {
                    $this->updatePosition($widget, $widgetData['position']);
                }
            }
        }
        return true;
    }

    
    public function reorderWidgets(Dashboard $dashboard, array $widgetIds): bool
    {
        foreach ($widgetIds as $index => $widgetId) {
            Widget::where('id', $widgetId)
                ->where('dashboard_id', $dashboard->id)
                ->update(['order_index' => $index + 1]);
        }
        return true;
    }

    
    public function getByType(string $type): Collection
    {
        return Widget::where('type', $type)
            ->where('is_active', true)
            ->with('dashboard')
            ->get();
    }

    
    public function cloneWidget(Widget $widget, Dashboard $targetDashboard): Widget
    {
        $maxOrder = $targetDashboard->widgets()->max('order_index') ?? 0;

        return $this->create([
            'dashboard_id' => $targetDashboard->id,
            'title' => $widget->title . ' (Copy)',
            'type' => $widget->type,
            'configuration' => $widget->configuration,
            'position' => $widget->position,
            'data_source' => $widget->data_source,
            'is_active' => true,
            'order_index' => $maxOrder + 1
        ]);
    }

    
    public function getDashboardWidgetStats(Dashboard $dashboard): array
    {
        $widgets = $this->getByDashboard($dashboard);
        $typeCount = $widgets->groupBy('type')->map->count();

        return [
            'total_widgets' => $widgets->count(),
            'widget_types' => $typeCount->toArray(),
            'active_widgets' => $widgets->where('is_active', true)->count(),
        ];
    }

    
    public function validateWidgetLimit(Dashboard $dashboard, int $limit = 50): bool
    {
        return $dashboard->getWidgetCountAttribute() < $limit;
    }

    
    public function getAvailableTypes(): array
    {
        return Widget::getTypes();
    }

    
    public function getDefaultConfiguration(string $type): array
    {
        return Widget::getDefaultConfiguration($type);
    }
}


