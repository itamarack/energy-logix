<?php

namespace App\Exceptions;

class CircularDependencyException extends \RuntimeException
{
    public function __construct(array $cycleMembers)
    {
        $names = implode(', ', $cycleMembers);

        parent::__construct("Circular dependency detected among variables: {$names}");
    }
}
