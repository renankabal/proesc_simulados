<?php

namespace App\Http\Policies;

use App\Domain\Prova\Models\Prova;
use App\Models\User;
use App\Support\Enums\RoleUsuario;

class ProvaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, Prova $prova): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $prova->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->ativo && $user->canManageProvas();
    }

    public function update(User $user, Prova $prova): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $prova->user_id === $user->id;
    }

    public function delete(User $user, Prova $prova): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $prova->user_id === $user->id && $prova->status->value === 'rascunho';
    }
}
