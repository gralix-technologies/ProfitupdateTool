<?php

namespace App\Repositories;

use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardRepository
{
    
    public function create(array $data): Dashboard
    {
        return Dashboard::create($data);
    }

    
    public function findById(int $id): ?Dashboard
    {
        return Dashboard::with(['widgets', 'user'])->find($id);
    }

    
    public function update(Dashboard $dashboard, array $data): Dashboard
    {
        $dashboard->update($data);
        return $dashboard->fresh(['widgets', 'user']);
    }

    
    public function delete(Dashboard $dashboard): bool
    {
        return $dashboard->delete();
    }

    
    public function getByUser(User $user, bool $includePublic = true): Collection
    {
        // Show all active dashboards to all users
        return Dashboard::with(['widgets', 'user'])
            ->withCount('widgets')
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    
    public function getUserDashboards(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        // Show all active dashboards to all users
        return Dashboard::with(['widgets', 'user', 'product'])
            ->withCount('widgets')
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Dashboard::with(['widgets', 'user'])
            ->withCount('widgets')
            ->where('is_active', true);

        // Remove user_id filter - show all dashboards to all users
        // if (isset($filters['user_id'])) {
        //     $query->where('user_id', $filters['user_id']);
        // }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    
    public function getPublicDashboards(): Collection
    {
        return Dashboard::with(['widgets', 'user'])
            ->withCount('widgets')
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    
    public function cloneDashboard(Dashboard $dashboard, User $user, string $name = null): Dashboard
    {
        $clonedData = [
            'name' => $name ?? $dashboard->name . ' (Copy)',
            'user_id' => $user->id,
            'layout' => $dashboard->layout,
            'filters' => $dashboard->filters,
            'description' => $dashboard->description,
            'is_public' => false,
            'is_active' => true
        ];

        $clonedDashboard = $this->create($clonedData);

        foreach ($dashboard->widgets as $widget) {
            $clonedDashboard->addWidget([
                'title' => $widget->title,
                'type' => $widget->type,
                'configuration' => $widget->configuration,
                'position' => $widget->position,
                'data_source' => $widget->data_source
            ]);
        }

        return $clonedDashboard->fresh(['widgets', 'user']);
    }

    
    public function updateLayout(Dashboard $dashboard, array $layout): Dashboard
    {
        $dashboard->updateLayout($layout);
        return $dashboard->fresh(['widgets', 'user']);
    }

    
    public function getStatistics(User $user = null): array
    {
        $baseQuery = Dashboard::query();
        
        // Remove user restriction - show statistics for all dashboards
        // if ($user) {
        //     $baseQuery->where('user_id', $user->id);
        // }

        return [
            'total_dashboards' => $baseQuery->count(),
            'active_dashboards' => (clone $baseQuery)->where('is_active', true)->count(),
            'public_dashboards' => (clone $baseQuery)->where('is_public', true)->count(),
            'private_dashboards' => (clone $baseQuery)->where('is_public', false)->count(),
        ];
    }
}


