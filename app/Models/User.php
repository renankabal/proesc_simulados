<?php

namespace App\Models;

use App\Support\Enums\RoleUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids;


    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'ativo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => RoleUsuario::class,
            'ativo'             => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleUsuario::Admin;
    }

    public function isProfessor(): bool
    {
        return $this->role === RoleUsuario::Professor;
    }

    public function canManageProvas(): bool
    {
        return $this->role?->canManageProvas() ?? false;
    }

    public function provas(): HasMany
    {
        return $this->hasMany(\App\Domain\Prova\Models\Prova::class, 'user_id');
    }
}
