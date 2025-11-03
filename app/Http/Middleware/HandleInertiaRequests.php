<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    
    protected $rootView = 'app';

    
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    
    public function share(Request $request): array
    {
        // Ensure session is started
        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }
        
        // Generate CSRF token
        $csrfToken = csrf_token();
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles.permissions') : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'csrf_token' => $csrfToken,
            'csrf_field' => csrf_field(),
            'csrf_meta' => '<meta name="csrf-token" content="' . $csrfToken . '">',
        ];
    }
}



