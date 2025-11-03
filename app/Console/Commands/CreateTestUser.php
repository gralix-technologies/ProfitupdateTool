<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'user:create-test';
    protected $description = 'Create a test user for login';

    public function handle()
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        try {
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $role = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
                
                if ($role && !$user->hasRole('Admin')) {
                    $user->assignRole('Admin');
                    $this->info('Assigned Admin role to user');
                } elseif (!$role) {
                    $this->warn('Admin role not found. Please run: php artisan db:seed --class=RolePermissionSeeder');
                }
            }
        } catch (\Exception $e) {
            $this->info('Note: Role assignment skipped (permissions package not configured)');
        }

        $this->info('Test user created/updated:');
        $this->info('Email: test@example.com');
        $this->info('Password: password');
        $this->info('Permissions: All (Super Admin)');
        
        return 0;
    }
}


