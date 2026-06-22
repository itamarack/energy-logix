<?php

namespace App\Enums;

enum FormulaToken: string
{
    case NUMBER = 'NUMBER';
    case IDENT = 'IDENT';
    case PLUS = 'PLUS';
    case MINUS = 'MINUS';
    case STAR = 'STAR';
    case SLASH = 'SLASH';
    case LPAREN = 'LPAREN';
    case RPAREN = 'RPAREN';
}
