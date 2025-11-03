<?php

namespace App\Services\Exceptions;

use Exception;

class FormulaException extends Exception
{
    protected ?string $expression;
    protected ?array $context;

    public function __construct(string $message = "", ?string $expression = null, ?array $context = null, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->expression = $expression;
        $this->context = $context;
    }

    public function getExpression(): ?string
    {
        return $this->expression;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }
}


