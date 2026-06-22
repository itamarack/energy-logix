<?php

namespace App\Enums;

enum FormulaNode: string
{
    case NUMBER = 'NODE_NUMBER';
    case IDENT = 'NODE_IDENT';
    case BINARY_OP = 'NODE_BINARY_OP';
}
