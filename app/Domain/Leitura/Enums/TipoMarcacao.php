<?php

namespace App\Domain\Leitura\Enums;

enum TipoMarcacao: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    public static function validas(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromChar(?string $char): ?self
    {
        if ($char === null) {
            return null;
        }
        return self::tryFrom(strtoupper($char));
    }
}
