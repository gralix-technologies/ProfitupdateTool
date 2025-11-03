<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run(): void
    {
        $this->command->info('🚀 Starting Final System Seeding...');
        
        // Run basic system seeder first (users, roles, permissions)
        $this->call([
            BasicSystemSeeder::class,
        ]);
        
        // Then run the final Working Capital Product seeder
        $this->call([
            FinalWorkingCapitalSeeder::class,
        ]);
        
        $this->command->info('✅ Final System seeding completed!');
        $this->command->info('🎉 System is now ready for comprehensive testing!');
        $this->command->info('');
        $this->command->info('📋 Login Credentials:');
        $this->command->info('   Admin: admin@gralix.co / password');
        $this->command->info('   Analyst: analyst@gralix.co / password');
        $this->command->info('   Viewer: viewer@gralix.co / password');
        $this->command->info('');
        $this->command->info('📊 System includes:');
        $this->command->info('   • 3 user roles (Admin, Analyst, Viewer)');
        $this->command->info('   • Comprehensive permission system');
        $this->command->info('   • Working Capital Loans product with full data');
        $this->command->info('   • 50 sample customers');
        $this->command->info('   • 100 working capital loan records');
        $this->command->info('   • 22 comprehensive formulas');
        $this->command->info('   • Complete dashboard with all widget types');
        $this->command->info('   • Currency system (ZMW base)');
        $this->command->info('   • System configurations');
        $this->command->info('');
        $this->command->info('✅ Features:');
        $this->command->info('   • Admin: Full system access');
        $this->command->info('   • Analyst: Business operations access');
        $this->command->info('   • Viewer: Read-only access');
        $this->command->info('   • Complete Working Capital Loans analytics');
        $this->command->info('   • Risk assessment and ECL calculations');
        $this->command->info('   • IFRS 9 compliance metrics');
    }
}



