<?php

namespace App\Domain\Prova\Enums;

enum StatusProva: string
{
    case Rascunho   = 'rascunho';
    case Publicada  = 'publicada';
    case Encerrada  = 'encerrada';
    case Arquivada  = 'arquivada';

    public function label(): string
    {
        return match($this) {
            self::Rascunho  => 'Rascunho',
            self::Publicada => 'Publicada',
            self::Encerrada => 'Encerrada',
            self::Arquivada => 'Arquivada',
        };
    }

    public function podeGerarCartoes(): bool
    {
        return $this === self::Publicada;
    }

    public function podeReceberLeituras(): bool
    {
        return in_array($this, [self::Publicada, self::Encerrada]);
    }
}
