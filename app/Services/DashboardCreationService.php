<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\Product;
use App\Models\User;
use App\Models\Widget;

class DashboardCreationService
{
    /**
     * Create a complete dashboard for a product based on Working Capital template
     */
    public function createProductDashboard(Product $product, User $user): Dashboard
    {
        // Get Working Capital Dashboard as template
        $templateDashboard = Dashboard::where('name', 'LIKE', '%Working Capital%')
            ->with('widgets')
            ->first();

        if (!$templateDashboard) {
            throw new \Exception('Working Capital template dashboard not found');
        }

        // Check if dashboard already exists for this product and user
        $existingDashboard = Dashboard::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingDashboard) {
            // Clear existing widgets
            $existingDashboard->widgets()->delete();
            $dashboard = $existingDashboard;
        } else {
            // Create new dashboard
            $dashboard = Dashboard::create([
                'name' => $product->name . ' Dashboard',
                'description' => 'Comprehensive analytics dashboard for ' . $product->name,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'is_public' => true,
                'is_active' => true,
                'layout' => [],
                'filters' => []
            ]);
        }

        // Replicate all widgets from template
        $this->replicateWidgets($templateDashboard, $dashboard, $product);

        return $dashboard->fresh('widgets');
    }

    /**
     * Replicate widgets from template dashboard to new dashboard
     */
    private function replicateWidgets(Dashboard $templateDashboard, Dashboard $targetDashboard, Product $product): void
    {
        foreach ($templateDashboard->widgets as $templateWidget) {
            $config = is_string($templateWidget->configuration) 
                ? json_decode($templateWidget->configuration, true) 
                : $templateWidget->configuration;

            $targetDashboard->widgets()->create([
                'title' => $templateWidget->title,
                'type' => $templateWidget->type,
                'configuration' => $config,
                'position' => $templateWidget->position,
                'data_source' => json_encode(['product_id' => $product->id]),
                'is_active' => $templateWidget->is_active,
                'order_index' => $templateWidget->order_index
            ]);
        }
    }

    /**
     * Create dashboard for multiple products
     */
    public function createDashboardsForProducts(array $productIds, User $user): array
    {
        $dashboards = [];
        
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $dashboards[] = $this->createProductDashboard($product, $user);
            }
        }
        
        return $dashboards;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Dashboard $dashboard): array
    {
        $widgets = $dashboard->widgets;
        
        $categories = [];
        $types = [];
        
        foreach ($widgets as $widget) {
            $config = is_string($widget->configuration) 
                ? json_decode($widget->configuration, true) 
                : $widget->configuration;
            
            $category = $config['category'] ?? 'Unknown';
            $categories[$category] = ($categories[$category] ?? 0) + 1;
            $types[$widget->type] = ($types[$widget->type] ?? 0) + 1;
        }
        
        return [
            'total_widgets' => $widgets->count(),
            'categories' => $categories,
            'types' => $types,
            'product_id' => $dashboard->product_id,
            'owner_id' => $dashboard->user_id,
            'is_public' => $dashboard->is_public,
            'is_active' => $dashboard->is_active
        ];
    }
}
