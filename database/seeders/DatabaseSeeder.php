<?php

namespace Database\Seeders;

use App\Domain\Prova\Enums\StatusProva;
use App\Domain\Prova\Models\Gabarito;
use App\Domain\Prova\Models\Prova;
use App\Models\User;
use App\Support\Enums\RoleUsuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::factory()->admin()->create([
            'name'  => 'Administrador',
            'email' => 'admin@proesc.com',
            'password' => Hash::make('password'),
        ]);

        // Professor
        $professor = User::factory()->professor()->create([
            'name'  => 'Professor Demo',
            'email' => 'professor@proesc.com',
            'password' => Hash::make('password'),
        ]);

        // Operador
        User::factory()->operador()->create([
            'name'  => 'Operador Demo',
            'email' => 'operador@proesc.com',
            'password' => Hash::make('password'),
        ]);

        // Prova demo com gabarito
        $prova = Prova::create([
            'user_id'        => $professor->id,
            'titulo'         => 'Simulado Demo — Matemática',
            'disciplina'     => 'Matemática',
            'turma'          => '9A',
            'total_questoes' => 20,
            'status'         => StatusProva::Publicada,
            'nota_maxima'    => 10.00,
            'data_aplicacao' => now()->addDays(7)->format('Y-m-d'),
        ]);

        // Gabarito da prova demo
        $respostas = [];
        $letras = ['A', 'B', 'C', 'D', 'E'];
        for ($i = 1; $i <= 20; $i++) {
            $respostas[(string) $i] = $letras[array_rand($letras)];
        }

        Gabarito::create([
            'prova_id'   => $prova->id,
            'versao'     => 1,
            'ativo'      => true,
            'criado_por' => $professor->id,
            'respostas'  => $respostas,
        ]);
    }
}
