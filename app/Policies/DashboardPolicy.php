<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;

class DashboardPolicy
{
    
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-dashboards');
    }

    
    public function view(User $user, Dashboard $dashboard): bool
    {
        // Allow users with view-dashboards permission to view any dashboard
        // This is more permissive for business operations
        return $user->hasPermissionTo('view-dashboards');
    }

    
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-dashboards');
    }

    
    public function update(User $user, Dashboard $dashboard): bool
    {
        return $user->hasPermissionTo('create-products');
    }

    
    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $user->hasPermissionTo('delete-dashboards') && 
               $dashboard->user_id === $user->id;
    }

    
    public function restore(User $user, Dashboard $dashboard): bool
    {
        return $user->hasPermissionTo('delete-dashboards') && 
               $dashboard->user_id === $user->id;
    }

    
    public function forceDelete(User $user, Dashboard $dashboard): bool
    {
        return $user->hasPermissionTo('create-products') && 
               $dashboard->user_id === $user->id;
    }
}


