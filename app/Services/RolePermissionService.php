<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class RolePermissionService
{
    
    public function getAllRoles(): Collection
    {
        return Role::with('permissions')->get();
    }

    
    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    
    public function assignRoleToUser(User $user, string $roleName): bool
    {
        try {
            $user->assignRole($roleName);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    
    public function removeRoleFromUser(User $user, string $roleName): bool
    {
        try {
            $user->removeRole($roleName);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    
    public function userHasRole(User $user, string $roleName): bool
    {
        return $user->hasRole($roleName);
    }

    
    public function userHasAnyRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    
    public function userHasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    
    public function userHasAnyPermission(User $user, array $permissions): bool
    {
        return $user->hasAnyPermission($permissions);
    }

    
    public function getUserRolesWithPermissions(User $user): Collection
    {
        return $user->roles()->with('permissions')->get();
    }

    
    public function getUserDirectPermissions(User $user): Collection
    {
        return $user->permissions;
    }

    
    public function getAllUserPermissions(User $user): Collection
    {
        return $user->getAllPermissions();
    }

    
    public function createRole(string $name, array $permissions = []): Role
    {
        $role = Role::create(['name' => $name]);
        
        if (!empty($permissions)) {
            $role->givePermissionTo($permissions);
        }
        
        return $role;
    }

    
    public function updateRolePermissions(string $roleName, array $permissions): bool
    {
        try {
            $role = Role::findByName($roleName);
            $role->syncPermissions($permissions);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    
    public function deleteRole(string $roleName): bool
    {
        try {
            $role = Role::findByName($roleName);
            $role->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    
    public function getUsersByRole(string $roleName): Collection
    {
        return User::role($roleName)->get();
    }

    
    public function getRoleHierarchy(): array
    {
        return [
            'Admin' => ['Admin', 'Analyst', 'Viewer'],
            'Analyst' => ['Analyst', 'Viewer'],
            'Viewer' => ['Viewer']
        ];
    }

    
    public function canAccessRoleLevel(User $user, string $requiredRole): bool
    {
        $hierarchy = $this->getRoleHierarchy();
        $userRoles = $user->getRoleNames()->toArray();
        
        foreach ($userRoles as $userRole) {
            if (isset($hierarchy[$userRole]) && in_array($requiredRole, $hierarchy[$userRole])) {
                return true;
            }
        }
        
        return false;
    }
}


