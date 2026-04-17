<?php

namespace App\Support\Enums;

enum RoleUsuario: string
{
    case Admin     = 'admin';
    case Professor = 'professor';
    case Operador  = 'operador';
    case Aluno     = 'aluno';

    public function label(): string
    {
        return match($this) {
            self::Admin     => 'Administrador',
            self::Professor => 'Professor',
            self::Operador  => 'Operador',
            self::Aluno     => 'Aluno',
        };
    }

    public function canManageProvas(): bool
    {
        return in_array($this, [self::Admin, self::Professor]);
    }

    public function canReadCartoes(): bool
    {
        return in_array($this, [self::Admin, self::Professor, self::Operador]);
    }
}
