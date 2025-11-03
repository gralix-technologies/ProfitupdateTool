<?php

namespace App\Services;

class ParsedFormula
{
    public function __construct(
        private string $expression,
        private array $ast,
        private array $fieldReferences,
        private array $tokens
    ) {}

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getAST(): array
    {
        return $this->ast;
    }

    public function getFieldReferences(): array
    {
        return $this->fieldReferences;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getOriginalExpression(): string
    {
        return $this->expression;
    }
}


