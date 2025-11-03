<?php

namespace App\Services;

class ValidationResult
{
    private array $warnings = [];

    public function __construct(
        private bool $isValid,
        private array $errors = []
    ) {}

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->isValid = false;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}


