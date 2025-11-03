<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptionService
{
    
    public function encrypt(mixed $value): string
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        return Crypt::encrypt($value);
    }

    
    public function decrypt(string $encryptedValue): mixed
    {
        if (empty($encryptedValue)) {
            return null;
        }

        try {
            return Crypt::decrypt($encryptedValue);
        } catch (DecryptException $e) {
            throw new \Exception('Failed to decrypt data: ' . $e->getMessage());
        }
    }

    
    public function encryptFields(array $data, array $sensitiveFields): array
    {
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }

        return $data;
    }

    
    public function decryptFields(array $data, array $sensitiveFields): array
    {
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    $data[$field] = $this->decrypt($data[$field]);
                } catch (\Exception $e) {
                    \Log::warning("Failed to decrypt field {$field}: " . $e->getMessage());
                    $data[$field] = null;
                }
            }
        }

        return $data;
    }

    
    public function isEncrypted(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            Crypt::decrypt($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
}


