<?php

namespace App\Traits;

use App\Services\AuditService;

trait Auditable
{
    
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditCreated();
        });

        static::updated(function ($model) {
            $model->auditUpdated();
        });

        static::deleted(function ($model) {
            $model->auditDeleted();
        });
    }

    
    protected function auditCreated(): void
    {
        $auditService = app(AuditService::class);
        
        $auditService->logCreated(
            model: get_class($this),
            modelId: $this->getKey(),
            attributes: $this->getAuditableAttributes()
        );
    }

    
    protected function auditUpdated(): void
    {
        if (!$this->isDirty()) {
            return;
        }

        $auditService = app(AuditService::class);
        
        $auditService->logUpdated(
            model: get_class($this),
            modelId: $this->getKey(),
            oldValues: $this->getOriginal(),
            newValues: $this->getDirty()
        );
    }

    
    protected function auditDeleted(): void
    {
        $auditService = app(AuditService::class);
        
        $auditService->logDeleted(
            model: get_class($this),
            modelId: $this->getKey(),
            attributes: $this->getAuditableAttributes()
        );
    }

    
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        
        $excludeFromAudit = $this->getExcludeFromAudit();
        
        return array_diff_key($attributes, array_flip($excludeFromAudit));
    }

    
    protected function getExcludeFromAudit(): array
    {
        return array_merge(
            ['password', 'remember_token', 'api_token'],
            $this->excludeFromAudit ?? []
        );
    }

    
    public function auditLogs()
    {
        $auditService = app(AuditService::class);
        
        return $auditService->getModelAuditLogs(
            model: get_class($this),
            modelId: $this->getKey()
        );
    }
}


