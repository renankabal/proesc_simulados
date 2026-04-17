<?php

namespace Database\Factories;

use App\Domain\Prova\Enums\StatusProva;
use App\Domain\Prova\Models\Prova;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prova>
 */
class ProvaFactory extends Factory
{
    protected $model = Prova::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'titulo'         => fake()->sentence(4),
            'disciplina'     => fake()->randomElement(['Matemática', 'Português', 'Ciências', 'História', 'Geografia']),
            'turma'          => fake()->randomElement(['9A', '9B', '8A', '8B', '7A']),
            'total_questoes' => fake()->randomElement([20, 30, 40, 50]),
            'status'         => StatusProva::Rascunho,
            'nota_maxima'    => 10.00,
            'data_aplicacao' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }

    public function publicada(): static
    {
        return $this->state(fn () => ['status' => StatusProva::Publicada]);
    }

    public function encerrada(): static
    {
        return $this->state(fn () => ['status' => StatusProva::Encerrada]);
    }
}
