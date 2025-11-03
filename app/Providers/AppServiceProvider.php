<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use App\Listeners\UpdateLastLogin;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        $this->app->singleton(\App\Services\FormulaEngine::class, function ($app) {
            return new \App\Services\FormulaEngine();
        });
    }

    
    public function boot(): void
    {
        // Register login event listener
        Event::listen(
            Login::class,
            UpdateLastLogin::class
        );

        // Register custom validation rule for array or object
        Validator::extend('array_or_object', function ($attribute, $value, $parameters, $validator) {
            return is_array($value) || is_object($value);
        }, 'The :attribute field must be an array or object.');
    }
}



