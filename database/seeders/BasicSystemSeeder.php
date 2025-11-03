<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class BasicSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding Basic System (Users, Roles, Permissions)...');
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions based on actual system usage
        $this->createPermissions();
        
        // Create roles with proper permission assignments
        $this->createRoles();
        
        // Create users and assign roles
        $this->createUsers();
        
        // Verify all permissions are utilized
        $this->verifyPermissionUtilization();
        
        $this->command->info('✅ Basic System seeding completed!');
    }

    /**
     * Create all permissions based on actual system usage
     */
    private function createPermissions(): void
    {
        $this->command->info('📝 Creating permissions...');
        
        $permissions = [
            // Product Management
            'create-products',
            'edit-products', 
            'delete-products',
            'view-products',
            
            // Formula Management
            'create-formulas',
            'edit-formulas',
            'delete-formulas', 
            'view-formulas',
            
            // Dashboard Management
            'create-dashboards',
            'edit-dashboards',
            'delete-dashboards',
            'view-dashboards',
            'export-dashboards',
            
            // Data Management
            'create-data',
            'edit-data',
            'delete-data',
            'view-data',
            'import-data',
            'export-data',
            
            // Customer Management
            'create-customers',
            'edit-customers', 
            'delete-customers',
            'view-customers',
            'export-customers',
            
            // User Management
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Role Management
            'manage-roles',
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            
            // Permission Management
            'manage-permissions',
            'view-permissions',
            
            // Configuration Management
            'manage-configurations',
            'view-configurations',
            'create-configurations',
            'edit-configurations',
            'delete-configurations',
            
            // Lookup Management
            'manage-lookups',
            'view-lookups',
            'create-lookups',
            'edit-lookups',
            'delete-lookups',
            
            // Import Error Management
            'manage-import-errors',
            'view-import-errors',
            
            // Audit Trail
            'view-audit-logs',
            'export-audit-logs',
            
            // Portfolio Configuration
            'view-portfolio-config',
            'manage-portfolio-config',
            
            // System Administration
            'system-settings',
            'view-system-logs',
            'manage-system',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        $this->command->info('✅ Created ' . count($permissions) . ' permissions');
    }

    /**
     * Create roles with proper permission assignments
     */
    private function createRoles(): void
    {
        $this->command->info('👥 Creating roles...');
        
        // Admin Role - Full system access
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminPermissions = Permission::all()->pluck('name')->toArray();
        $adminRole->syncPermissions($adminPermissions);
        $this->command->info('✅ Admin role created with ' . count($adminPermissions) . ' permissions');

        // Analyst Role - Business operations access (no admin privileges)
        $analystRole = Role::firstOrCreate(['name' => 'Analyst']);
        $analystPermissions = [
            // Product Management - Full Access
            'create-products', 'edit-products', 'delete-products', 'view-products',
            
            // Formula Management - Full Access
            'create-formulas', 'edit-formulas', 'delete-formulas', 'view-formulas',
            
            // Dashboard Management - Full Access
            'create-dashboards', 'edit-dashboards', 'delete-dashboards', 'view-dashboards', 'export-dashboards',
            
            // Data Management - Full Access
            'create-data', 'edit-data', 'delete-data', 'view-data', 'import-data', 'export-data',
            
            // Customer Management - Full Access
            'create-customers', 'edit-customers', 'delete-customers', 'view-customers', 'export-customers',
            
            // Configuration Management - Limited Access
            'view-configurations', 'edit-configurations',
            
            // Lookup Management - Limited Access
            'view-lookups', 'create-lookups', 'edit-lookups',
            
            // Import Error Management - Limited Access
            'view-import-errors', 'manage-import-errors',
            
            // Portfolio Configuration - View Only
            'view-portfolio-config',
        ];
        $analystRole->syncPermissions($analystPermissions);
        $this->command->info('✅ Analyst role created with ' . count($analystPermissions) . ' permissions');

        // Viewer Role - Read-only access
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);
        $viewerPermissions = [
            // Product Management - View Only
            'view-products',
            
            // Formula Management - View Only
            'view-formulas',
            
            // Dashboard Management - View Only
            'view-dashboards', 'export-dashboards',
            
            // Data Management - View Only
            'view-data', 'export-data',
            
            // Customer Management - View Only
            'view-customers', 'export-customers',
            
            // Configuration Management - View Only
            'view-configurations',
            
            // Lookup Management - View Only
            'view-lookups',
            
            // Import Error Management - View Only
            'view-import-errors',
            
            // Portfolio Configuration - View Only
            'view-portfolio-config',
        ];
        $viewerRole->syncPermissions($viewerPermissions);
        $this->command->info('✅ Viewer role created with ' . count($viewerPermissions) . ' permissions');
    }

    /**
     * Create users and assign roles
     */
    private function createUsers(): void
    {
        $this->command->info('👤 Creating users...');
        
        // Create Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gralix.co'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@gralix.co',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['Admin']);
        $this->command->info('✅ Admin user created: admin@gralix.co / password');

        // Create Analyst User
        $analystUser = User::firstOrCreate(
            ['email' => 'analyst@gralix.co'],
            [
                'name' => 'Business Analyst',
                'email' => 'analyst@gralix.co',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $analystUser->syncRoles(['Analyst']);
        $this->command->info('✅ Analyst user created: analyst@gralix.co / password');

        // Create Viewer User
        $viewerUser = User::firstOrCreate(
            ['email' => 'viewer@gralix.co'],
            [
                'name' => 'Data Viewer',
                'email' => 'viewer@gralix.co',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $viewerUser->syncRoles(['Viewer']);
        $this->command->info('✅ Viewer user created: viewer@gralix.co / password');
    }

    /**
     * Verify all permissions are utilized by at least one role
     */
    private function verifyPermissionUtilization(): void
    {
        $this->command->info('🔍 Verifying permission utilization...');
        
        $allPermissions = Permission::all();
        $unusedPermissions = [];
        
        foreach ($allPermissions as $permission) {
            $rolesWithPermission = $permission->roles;
            if ($rolesWithPermission->isEmpty()) {
                $unusedPermissions[] = $permission->name;
            }
        }
        
        if (empty($unusedPermissions)) {
            $this->command->info('✅ All permissions are properly utilized by roles');
        } else {
            $this->command->warn('⚠️  Unused permissions found: ' . implode(', ', $unusedPermissions));
            $this->command->warn('These permissions should be either removed or assigned to appropriate roles');
        }
        
        // Display permission summary
        $this->command->info('');
        $this->command->info('📊 Permission Summary:');
        $this->command->info('   Total Permissions: ' . $allPermissions->count());
        $this->command->info('   Used Permissions: ' . ($allPermissions->count() - count($unusedPermissions)));
        $this->command->info('   Unused Permissions: ' . count($unusedPermissions));
        
        // Display role summary
        $this->command->info('');
        $this->command->info('👥 Role Summary:');
        $roles = Role::with('permissions')->get();
        foreach ($roles as $role) {
            $this->command->info('   ' . $role->name . ': ' . $role->permissions->count() . ' permissions');
        }
    }
}
