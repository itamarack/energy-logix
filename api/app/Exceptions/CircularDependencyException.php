<?php

namespace App\Exceptions;

class CircularDependencyException extends \RuntimeException
{
    public function __construct(array $params)
    {
        parent::__construct(sprintf('Circular dependency detected among variables: %s', implode(', ', $params)));
    }
}
