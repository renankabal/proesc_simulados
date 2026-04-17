<?php

namespace App\Domain\Leitura\Enums;

enum StatusLeitura: string
{
    case Pendente    = 'pendente';
    case Processando = 'processando';
    case Confirmada  = 'confirmada';
    case Rejeitada   = 'rejeitada';
    case Erro        = 'erro';

    public function label(): string
    {
        return match($this) {
            self::Pendente    => 'Pendente',
            self::Processando => 'Processando',
            self::Confirmada  => 'Confirmada',
            self::Rejeitada   => 'Rejeitada',
            self::Erro        => 'Erro',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Confirmada, self::Rejeitada, self::Erro]);
    }
}
