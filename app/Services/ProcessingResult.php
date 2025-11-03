<?php

namespace App\Services;

class ProcessingResult
{
    protected array $validRows;
    protected array $errors;
    protected string $message;

    public function __construct(array $validRows, array $errors, string $message)
    {
        $this->validRows = $validRows;
        $this->errors = $errors;
        $this->message = $message;
    }

    public function getValidRows(): array
    {
        return $this->validRows;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMessage(): string
    {
        return $this->message;
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
}


