<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Services\InputSanitizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityValidationMiddleware
{
    public function __construct(
        private InputSanitizationService $sanitizationService,
        private AuditService $auditService
    ) {}

    
    public function handle(Request $request, Closure $next): Response
    {
        $this->validateRequestSecurity($request);

        return $next($request);
    }

    
    protected function validateRequestSecurity(Request $request): void
    {
        $allInput = $request->all();
        
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                $this->checkForSecurityThreats($key, $value, $request);
            } elseif (is_array($value)) {
                $this->checkArrayForSecurityThreats($key, $value, $request);
            }
        }
    }

    
    protected function checkForSecurityThreats(string $key, string $value, Request $request): void
    {
        if ($this->sanitizationService->containsSqlInjection($value)) {
            $this->logSecurityThreat('sql_injection_attempt', $key, $value, $request);
            abort(400, 'Invalid input detected');
        }

        if ($this->sanitizationService->containsXss($value)) {
            $this->logSecurityThreat('xss_attempt', $key, $value, $request);
            abort(400, 'Invalid input detected');
        }

        if ($this->containsPathTraversal($value)) {
            $this->logSecurityThreat('path_traversal_attempt', $key, $value, $request);
            abort(400, 'Invalid input detected');
        }
    }

    
    protected function checkArrayForSecurityThreats(string $key, array $values, Request $request): void
    {
        foreach ($values as $subKey => $value) {
            if (is_string($value)) {
                $this->checkForSecurityThreats("{$key}.{$subKey}", $value, $request);
            } elseif (is_array($value)) {
                $this->checkArrayForSecurityThreats("{$key}.{$subKey}", $value, $request);
            }
        }
    }

    
    protected function containsPathTraversal(string $input): bool
    {
        $patterns = [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/\.\.\%2f/',
            '/\.\.\%5c/',
            '/\%2e\%2e\%2f/',
            '/\%2e\%2e\%5c/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    
    protected function logSecurityThreat(string $threatType, string $field, string $value, Request $request): void
    {
        $this->auditService->logSecurityEvent($threatType, [
            'field' => $field,
            'value' => substr($value, 0, 100), // Limit logged value length
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
        ]);
    }
}



