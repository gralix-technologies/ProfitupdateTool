<?php

namespace App\Services;

class ImportResult
{
    protected bool $success;
    protected string $message;
    protected array $validRows;
    protected array $errors;

    public function __construct(bool $success, string $message, array $validRows = [], array $errors = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->validRows = $validRows;
        $this->errors = $errors;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getValidRows(): array
    {
        return $this->validRows;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasValidRows(): bool
    {
        return !empty($this->validRows);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getValidRowCount(): int
    {
        return count($this->validRows);
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'valid_rows' => $this->getValidRowCount(),
            'error_count' => $this->getErrorCount(),
            'errors' => $this->errors
        ];
    }
}


