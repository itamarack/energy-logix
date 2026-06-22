<?php

namespace App\Exceptions;

class UndefinedVariableException extends \RuntimeException
{
    public function __construct(string $variableName)
    {
        parent::__construct("Undefined variable: {$variableName}");
    }
}
