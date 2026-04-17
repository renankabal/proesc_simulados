<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\Enums\RoleUsuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => RoleUsuario::Professor,
            'ativo'             => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => RoleUsuario::Admin]);
    }

    public function professor(): static
    {
        return $this->state(fn () => ['role' => RoleUsuario::Professor]);
    }

    public function operador(): static
    {
        return $this->state(fn () => ['role' => RoleUsuario::Operador]);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['ativo' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
