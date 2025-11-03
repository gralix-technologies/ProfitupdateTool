<?php

namespace App\Services;

class InputSanitizationService
{
    
    public function sanitizeString(string $input): string
    {
        $input = str_replace("\0", '', $input);
        
        $input = strip_tags($input);
        
        $input = preg_replace('/[<>"\']/', '', $input);
        
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return trim($input);
    }

    
    public function sanitizeHtml(string $input, array $allowedTags = []): string
    {
        $defaultAllowedTags = ['p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li'];
        $allowedTags = array_merge($defaultAllowedTags, $allowedTags);
        
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        
        $input = strip_tags($input, $allowedTagsString);
        
        $input = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $input);
        $input = preg_replace('/\s*javascript\s*:\s*/i', '', $input);
        $input = preg_replace('/\s*vbscript\s*:\s*/i', '', $input);
        
        return $input;
    }

    
    public function sanitizeEmail(string $email): string
    {
        $email = strip_tags($email);
        
        $sanitized = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        return $sanitized ?: '';
    }

    
    public function sanitizeUrl(string $url): string
    {
        $url = strip_tags($url);
        
        $sanitized = filter_var(trim($url), FILTER_SANITIZE_URL);
        
        return $sanitized ?: '';
    }

    
    public function sanitizeNumber($input): float|int|null
    {
        if (is_numeric($input)) {
            return is_float($input + 0) ? (float) $input : (int) $input;
        }
        
        return null;
    }

    
    public function sanitizeArray(array $input, string $type = 'string'): array
    {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            $sanitizedKey = $this->sanitizeString($key);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $type);
            } else {
                $sanitized[$sanitizedKey] = $this->sanitizeByType($value, $type);
            }
        }
        
        return $sanitized;
    }

    
    public function sanitizeByType($input, string $type)
    {
        return match ($type) {
            'string' => $this->sanitizeString($input),
            'html' => $this->sanitizeHtml($input),
            'email' => $this->sanitizeEmail($input),
            'url' => $this->sanitizeUrl($input),
            'number' => $this->sanitizeNumber($input),
            default => $this->sanitizeString($input),
        };
    }

    
    public function containsSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
            '/(\b(OR|AND)\s+\d+\s*=\s*\d+)/i',
            '/(\b(OR|AND)\s+["\']?\w+["\']?\s*=\s*["\']?\w+["\']?)/i',
            '/(\-\-|\#|\/\*|\*\/)/i',
            '/(\bxp_\w+)/i',
            '/(\bsp_\w+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    
    public function containsXss(string $input): bool
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
            '/<form\b[^>]*>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    
    public function sanitizeFileName(string $filename): string
    {
        $filename = basename($filename);
        
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        $filename = preg_replace('/\.+/', '.', $filename);
        
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        return $filename;
    }
}


