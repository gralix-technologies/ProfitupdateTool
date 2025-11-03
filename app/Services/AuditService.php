<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    
    public function log(
        string $action,
        string $model = null,
        int $modelId = null,
        array $oldValues = [],
        array $newValues = [],
        Request $request = null
    ): AuditLog {
        $user = Auth::user();
        $request = $request ?: request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
        ]);
    }

    
    public function logCreated(string $model, int $modelId, array $attributes = []): AuditLog
    {
        return $this->log(
            action: 'created',
            model: $model,
            modelId: $modelId,
            newValues: $attributes
        );
    }

    
    public function logUpdated(string $model, int $modelId, array $oldValues, array $newValues): AuditLog
    {
        return $this->log(
            action: 'updated',
            model: $model,
            modelId: $modelId,
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    
    public function logDeleted(string $model, int $modelId, array $attributes = []): AuditLog
    {
        return $this->log(
            action: 'deleted',
            model: $model,
            modelId: $modelId,
            oldValues: $attributes
        );
    }

    
    public function logAuth(string $action, array $data = []): AuditLog
    {
        return $this->log(
            action: "auth.{$action}",
            newValues: $data
        );
    }

    
    public function logFileOperation(string $action, string $filename, array $metadata = []): AuditLog
    {
        return $this->log(
            action: "file.{$action}",
            newValues: array_merge([
                'filename' => $filename,
            ], $metadata)
        );
    }

    
    public function logSecurityEvent(string $event, array $data = []): AuditLog
    {
        return $this->log(
            action: "security.{$event}",
            newValues: $data
        );
    }

    
    public function getModelAuditLogs(string $model, int $modelId, int $limit = 50)
    {
        return AuditLog::where('model', $model)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    
    public function getUserAuditLogs(int $userId, int $limit = 100)
    {
        // Show all audit logs to all users regardless of user
        return AuditLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}


