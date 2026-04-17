<?php

namespace Tests\Feature\Prova;

use App\Domain\Prova\Models\Gabarito;
use App\Domain\Prova\Models\Prova;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GabaritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_pode_salvar_gabarito(): void
    {
        $professor = User::factory()->professor()->create();
        $prova     = Prova::factory()->create(['user_id' => $professor->id, 'total_questoes' => 5]);

        $respostas = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E'];

        $response = $this->actingAs($professor)->put(route('provas.gabarito.update', $prova), [
            'respostas' => $respostas,
        ]);

        $response->assertRedirect(route('provas.show', $prova));
        $this->assertDatabaseHas('gabaritos', ['prova_id' => $prova->id, 'ativo' => true, 'versao' => 1]);
    }

    public function test_novo_gabarito_desativa_anterior(): void
    {
        $professor = User::factory()->professor()->create();
        $prova     = Prova::factory()->create(['user_id' => $professor->id, 'total_questoes' => 3]);

        // primeiro gabarito
        $this->actingAs($professor)->put(route('provas.gabarito.update', $prova), [
            'respostas' => ['1' => 'A', '2' => 'B', '3' => 'C'],
        ]);

        // segundo gabarito
        $this->actingAs($professor)->put(route('provas.gabarito.update', $prova), [
            'respostas' => ['1' => 'B', '2' => 'C', '3' => 'D'],
        ]);

        $this->assertEquals(1, Gabarito::where('prova_id', $prova->id)->where('ativo', true)->count());
        $this->assertEquals(2, Gabarito::where('prova_id', $prova->id)->count());

        $ativo = Gabarito::where('prova_id', $prova->id)->where('ativo', true)->first();
        $this->assertEquals(2, $ativo->versao);
    }

    public function test_resposta_invalida_e_rejeitada(): void
    {
        $professor = User::factory()->professor()->create();
        $prova     = Prova::factory()->create(['user_id' => $professor->id, 'total_questoes' => 2]);

        $response = $this->actingAs($professor)->put(route('provas.gabarito.update', $prova), [
            'respostas' => ['1' => 'Z', '2' => 'A'],
        ]);

        $response->assertSessionHasErrors();
    }
}
