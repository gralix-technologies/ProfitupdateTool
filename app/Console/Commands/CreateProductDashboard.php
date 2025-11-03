<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Services\DashboardCreationService;
use Illuminate\Console\Command;

class CreateProductDashboard extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dashboard:create {product_id} {user_id}';

    /**
     * The console command description.
     */
    protected $description = 'Create a complete dashboard for a product based on Working Capital template';

    /**
     * Execute the console command.
     */
    public function handle(DashboardCreationService $dashboardService)
    {
        $productId = $this->argument('product_id');
        $userId = $this->argument('user_id');

        // Validate product exists
        $product = Product::find($productId);
        if (!$product) {
            $this->error("Product with ID {$productId} not found");
            return 1;
        }

        // Validate user exists
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        try {
            $this->info("Creating dashboard for product: {$product->name} (ID: {$product->id})");
            $this->info("User: {$user->name} (ID: {$user->id})");

            $dashboard = $dashboardService->createProductDashboard($product, $user);
            $stats = $dashboardService->getDashboardStats($dashboard);

            $this->info("✅ Dashboard created successfully!");
            $this->info("Dashboard ID: {$dashboard->id}");
            $this->info("Dashboard Name: {$dashboard->name}");
            $this->info("Total Widgets: {$stats['total_widgets']}");
            
            $this->info("\nWidget Categories:");
            foreach ($stats['categories'] as $category => $count) {
                $this->info("  - {$category}: {$count} widgets");
            }

            $this->info("\nWidget Types:");
            foreach ($stats['types'] as $type => $count) {
                $this->info("  - {$type}: {$count} widgets");
            }

            $this->info("\nDashboard URLs:");
            $this->info("  View: http://localhost:8000/dashboards/{$dashboard->id}");
            $this->info("  Edit: http://localhost:8000/dashboards/{$dashboard->id}/edit");

            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to create dashboard: " . $e->getMessage());
            return 1;
        }
    }
}
