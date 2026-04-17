<?php

namespace Tests\Feature\Prova;

use App\Domain\Prova\Enums\StatusProva;
use App\Domain\Prova\Models\Prova;
use App\Models\User;
use App\Support\Enums\RoleUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvaTest extends TestCase
{
    use RefreshDatabase;

    private User $professor;
    private User $outroUsuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->professor    = User::factory()->professor()->create();
        $this->outroUsuario = User::factory()->professor()->create();
    }

    public function test_professor_pode_criar_prova(): void
    {
        $response = $this->actingAs($this->professor)->post(route('provas.store'), [
            'titulo'         => 'Prova de Matemática',
            'disciplina'     => 'Matemática',
            'turma'          => '9A',
            'total_questoes' => 20,
            'nota_maxima'    => 10,
            'status'         => 'rascunho',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('provas', [
            'titulo'   => 'Prova de Matemática',
            'user_id'  => $this->professor->id,
        ]);
    }

    public function test_titulo_e_obrigatorio(): void
    {
        $response = $this->actingAs($this->professor)->post(route('provas.store'), [
            'total_questoes' => 20,
            'nota_maxima'    => 10,
        ]);

        $response->assertSessionHasErrors('titulo');
    }

    public function test_professor_so_ve_suas_provas(): void
    {
        $minhaProva   = Prova::factory()->create(['user_id' => $this->professor->id]);
        $outraProva   = Prova::factory()->create(['user_id' => $this->outroUsuario->id]);

        $response = $this->actingAs($this->professor)->get(route('provas.index'));

        $response->assertOk();
        $response->assertSee($minhaProva->titulo);
        $response->assertDontSee($outraProva->titulo);
    }

    public function test_professor_nao_pode_editar_prova_alheia(): void
    {
        $prova = Prova::factory()->create(['user_id' => $this->outroUsuario->id]);

        $response = $this->actingAs($this->professor)->put(route('provas.update', $prova), [
            'titulo'         => 'Alterado',
            'total_questoes' => 20,
            'nota_maxima'    => 10,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_pode_editar_prova_alheia(): void
    {
        $admin = User::factory()->admin()->create();
        $prova = Prova::factory()->create(['user_id' => $this->professor->id]);

        $response = $this->actingAs($admin)->put(route('provas.update', $prova), [
            'titulo'         => 'Alterado pelo admin',
            'total_questoes' => 20,
            'nota_maxima'    => 10,
            'status'         => 'rascunho',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('provas', ['titulo' => 'Alterado pelo admin']);
    }

    public function test_usuario_nao_autenticado_e_redirecionado(): void
    {
        $response = $this->get(route('provas.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_prova_pode_ser_publicada(): void
    {
        $prova = Prova::factory()->create(['user_id' => $this->professor->id, 'status' => StatusProva::Rascunho]);

        $this->actingAs($this->professor)->put(route('provas.update', $prova), [
            'titulo'         => $prova->titulo,
            'total_questoes' => $prova->total_questoes,
            'nota_maxima'    => $prova->nota_maxima,
            'status'         => 'publicada',
        ]);

        $this->assertDatabaseHas('provas', ['id' => $prova->id, 'status' => 'publicada']);
    }
}
