<?php

namespace App\Services;

class DataStorageResult
{
    protected bool $success;
    protected string $message;
    protected int $storedCount;
    protected int $errorCount;
    protected string $importSessionId;

    public function __construct(
        bool $success,
        string $message,
        int $storedCount,
        int $errorCount,
        string $importSessionId
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->storedCount = $storedCount;
        $this->errorCount = $errorCount;
        $this->importSessionId = $importSessionId;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStoredCount(): int
    {
        return $this->storedCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getImportSessionId(): string
    {
        return $this->importSessionId;
    }

    public function hasErrors(): bool
    {
        return $this->errorCount > 0;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'stored_count' => $this->storedCount,
            'error_count' => $this->errorCount,
            'import_session_id' => $this->importSessionId,
            'has_errors' => $this->hasErrors()
        ];
    }
}


