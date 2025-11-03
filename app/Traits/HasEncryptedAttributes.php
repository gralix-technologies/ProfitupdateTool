<?php

namespace App\Traits;

use App\Services\EncryptionService;

trait HasEncryptedAttributes
{

    
    public static function bootHasEncryptedAttributes()
    {
        static::saving(function ($model) {
            $model->encryptAttributes();
        });

        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    
    protected function encryptAttributes(): void
    {
        $encryptionService = app(EncryptionService::class);
        
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                if (!$encryptionService->isEncrypted($this->attributes[$attribute])) {
                    $this->attributes[$attribute] = $encryptionService->encrypt($this->attributes[$attribute]);
                }
            }
        }
    }

    
    protected function decryptAttributes(): void
    {
        $encryptionService = app(EncryptionService::class);
        
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                try {
                    $this->attributes[$attribute] = $encryptionService->decrypt($this->attributes[$attribute]);
                } catch (\Exception $e) {
                    \Log::warning("Failed to decrypt attribute {$attribute} for model " . get_class($this) . ": " . $e->getMessage());
                    $this->attributes[$attribute] = null;
                }
            }
        }
    }

    
    public function getEncryptedAttributes(): array
    {
        return property_exists($this, 'encrypted') ? $this->encrypted : [];
    }

    
    public function setEncryptedAttribute(string $key, $value): void
    {
        $encryptionService = app(EncryptionService::class);
        $this->attributes[$key] = $encryptionService->encrypt($value);
    }

    
    public function getEncryptedAttribute(string $key)
    {
        if (!isset($this->attributes[$key])) {
            return null;
        }

        $encryptionService = app(EncryptionService::class);
        return $encryptionService->decrypt($this->attributes[$key]);
    }
}


