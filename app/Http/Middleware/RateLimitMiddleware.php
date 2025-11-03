<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function __construct(
        private AuditService $auditService
    ) {}

    
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, (int) $maxAttempts)) {
            $this->auditService->logSecurityEvent('rate_limit_exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'max_attempts' => $maxAttempts,
                'decay_minutes' => $decayMinutes,
            ]);

            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        RateLimiter::hit($key, (int) $decayMinutes * 60);

        $response = $next($request);

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, (int) $maxAttempts),
            'X-RateLimit-Reset' => RateLimiter::availableIn($key),
        ]);

        return $response;
    }

    
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        
        if ($user) {
            return 'rate_limit:user:' . $user->id . ':' . $request->route()->getName();
        }

        return 'rate_limit:ip:' . $request->ip() . ':' . $request->route()->getName();
    }
}



