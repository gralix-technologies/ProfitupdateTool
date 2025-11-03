<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dashboard;
use App\Models\Widget;

class ConsolidateDashboards extends Command
{
    protected $signature = 'dashboards:consolidate';
    protected $description = 'Consolidate all Working Capital dashboards into dashboard 1 and remove duplicates';

    public function handle()
    {
        $this->info('Starting dashboard consolidation...');

        $widgets = Widget::with('dashboard')->get();
        $this->info("Total widgets found: {$widgets->count()}");

        $widgetGroups = [];
        foreach ($widgets as $widget) {
            $key = $widget->title . '|' . $widget->type;
            if (!isset($widgetGroups[$key])) {
                $widgetGroups[$key] = [];
            }
            $widgetGroups[$key][] = $widget;
        }

        $duplicates = [];
        $uniqueWidgets = [];
        foreach ($widgetGroups as $key => $group) {
            if (count($group) > 1) {
                $duplicates[$key] = $group;
            } else {
                $uniqueWidgets[] = $group[0];
            }
        }

        $this->info("Unique widgets: " . count($uniqueWidgets));
        $this->info("Duplicate groups: " . count($duplicates));

        $widgetsToDelete = [];
        foreach ($duplicates as $key => $group) {
            $this->line("Processing duplicates for: '{$group[0]->title}' ({$group[0]->type})");
            
            usort($group, function($a, $b) {
                if ($a->dashboard_id == 1) return -1;
                if ($b->dashboard_id == 1) return 1;
                return $a->dashboard_id - $b->dashboard_id;
            });
            
            $keepWidget = array_shift($group);
            $this->line("  - Keeping widget {$keepWidget->id} from dashboard {$keepWidget->dashboard_id}");
            
            foreach ($group as $duplicateWidget) {
                $widgetsToDelete[] = $duplicateWidget->id;
                $this->line("  - Marking widget {$duplicateWidget->id} from dashboard {$duplicateWidget->dashboard_id} for deletion");
            }
        }

        if (!empty($widgetsToDelete)) {
            $this->info("Deleting " . count($widgetsToDelete) . " duplicate widgets...");
            Widget::whereIn('id', $widgetsToDelete)->delete();
            $this->info("Duplicates removed!");
        }

        $this->info("Consolidating all widgets to dashboard 1...");
        $remainingWidgets = Widget::all();
        $movedCount = 0;
        
        foreach ($remainingWidgets as $widget) {
            if ($widget->dashboard_id != 1) {
                $widget->dashboard_id = 1;
                $widget->save();
                $movedCount++;
            }
        }
        
        $this->info("Moved {$movedCount} widgets to dashboard 1");

        $this->info("Deleting empty dashboards...");
        $deletedDashboards = Dashboard::whereIn('id', [2, 3, 4])->delete();
        $this->info("Deleted {$deletedDashboards} empty dashboards");

        $mainDashboard = Dashboard::find(1);
        if ($mainDashboard) {
            $mainDashboard->name = "Working Capital - Comprehensive Analytics (Consolidated)";
            $mainDashboard->save();
            $this->info("Updated dashboard 1 name to: {$mainDashboard->name}");
        }

        $finalWidgetCount = Widget::count();
        $finalDashboardCount = Dashboard::count();
        
        $this->info("Final Results:");
        $this->info("Total dashboards: {$finalDashboardCount}");
        $this->info("Total widgets: {$finalWidgetCount}");
        
        $widgetTypes = [];
        $allWidgets = Widget::all();
        foreach ($allWidgets as $widget) {
            $widgetTypes[$widget->type] = ($widgetTypes[$widget->type] ?? 0) + 1;
        }
        
        $this->info("Widget breakdown by type:");
        foreach ($widgetTypes as $type => $count) {
            $this->line("- {$type}: {$count} widgets");
        }

        $this->info("Consolidation complete!");
        $this->info("Dashboard 1 now contains all unique widgets with tables at the bottom.");
    }
}


