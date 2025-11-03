<?php

namespace App\Providers;

use App\Models\Dashboard;
use App\Policies\DashboardPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    
    protected $policies = [
        Dashboard::class => DashboardPolicy::class,
    ];

    
    public function boot(): void
    {
    }
}


