<?php

namespace App\Exceptions;

class DivisionByZeroException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Division by zero');
    }
}
