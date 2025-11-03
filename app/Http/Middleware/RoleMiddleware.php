<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect('/login');
        }

        // Check if user has any of the required roles
        if (!empty($roles)) {
            $user = $request->user();
            $hasRole = false;
            
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }
            
            if (!$hasRole) {
                abort(403, 'This action is unauthorized.');
            }
        }

        return $next($request);
    }
}



