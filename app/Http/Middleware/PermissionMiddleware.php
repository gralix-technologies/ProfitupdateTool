<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            return redirect('/login');
        }

        // Check if user has any of the required permissions
        if (!empty($permissions)) {
            $user = $request->user();
            $hasPermission = false;
            
            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    $hasPermission = true;
                    break;
                }
            }
            
            if (!$hasPermission) {
                abort(403, 'This action is unauthorized.');
            }
        }

        return $next($request);
    }
}



