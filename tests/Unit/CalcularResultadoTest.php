<?php

namespace Tests\Unit;

use App\Application\Actions\Leitura\CalcularResultadoAction;
use App\Domain\Cartao\Models\CartaoResposta;
use App\Domain\Leitura\Enums\StatusLeitura;
use App\Domain\Leitura\Models\Leitura;
use App\Domain\Leitura\Models\RespostaAluno;
use App\Domain\Prova\Models\Gabarito;
use App\Domain\Prova\Models\Prova;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalcularResultadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_resultado_corretamente(): void
    {
        $professor = User::factory()->professor()->create();
        $prova     = Prova::factory()->publicada()->create([
            'user_id'        => $professor->id,
            'total_questoes' => 4,
            'nota_maxima'    => 10,
        ]);

        // Gabarito: 1=A, 2=B, 3=C, 4=D
        $gabarito = Gabarito::create([
            'prova_id'   => $prova->id,
            'versao'     => 1,
            'ativo'      => true,
            'criado_por' => $professor->id,
            'respostas'  => ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D'],
        ]);

        $cartao = CartaoResposta::create([
            'prova_id'     => $prova->id,
            'codigo_aluno' => 'ALU001',
            'qr_data'      => 'test-qr-' . uniqid(),
            'tentativa'    => 1,
        ]);

        $leitura = Leitura::create([
            'cartao_id' => $cartao->id,
            'status'    => StatusLeitura::Pendente,
            'origem'    => 'webcam',
        ]);

        // Aluno acerta 1=A(✓), 2=C(✗), 3=C(✓), 4=D(✓) — 3 acertos
        RespostaAluno::insert([
            ['leitura_id' => $leitura->id, 'questao_numero' => 1, 'marcacao' => 'A', 'dupla_marcacao' => false, 'em_branco' => false],
            ['leitura_id' => $leitura->id, 'questao_numero' => 2, 'marcacao' => 'C', 'dupla_marcacao' => false, 'em_branco' => false],
            ['leitura_id' => $leitura->id, 'questao_numero' => 3, 'marcacao' => 'C', 'dupla_marcacao' => false, 'em_branco' => false],
            ['leitura_id' => $leitura->id, 'questao_numero' => 4, 'marcacao' => 'D', 'dupla_marcacao' => false, 'em_branco' => false],
        ]);

        $action    = new CalcularResultadoAction();
        $resultado = $action->execute($leitura);

        $this->assertEquals(4, $resultado->total_questoes);
        $this->assertEquals(3, $resultado->total_acertos);
        $this->assertEquals(1, $resultado->total_erros);
        $this->assertEquals(7.5, (float) $resultado->nota_final);
        $this->assertEquals(75.0, (float) $resultado->percentual_acerto);
    }

    public function test_questao_em_branco_nao_conta_como_erro(): void
    {
        $professor = User::factory()->professor()->create();
        $prova     = Prova::factory()->publicada()->create([
            'user_id'        => $professor->id,
            'total_questoes' => 2,
            'nota_maxima'    => 10,
        ]);

        Gabarito::create([
            'prova_id'   => $prova->id,
            'versao'     => 1,
            'ativo'      => true,
            'criado_por' => $professor->id,
            'respostas'  => ['1' => 'A', '2' => 'B'],
        ]);

        $cartao = CartaoResposta::create([
            'prova_id'     => $prova->id,
            'codigo_aluno' => 'ALU002',
            'qr_data'      => 'test-qr-' . uniqid(),
            'tentativa'    => 1,
        ]);

        $leitura = Leitura::create([
            'cartao_id' => $cartao->id,
            'status'    => StatusLeitura::Pendente,
            'origem'    => 'webcam',
        ]);

        RespostaAluno::insert([
            ['leitura_id' => $leitura->id, 'questao_numero' => 1, 'marcacao' => 'A', 'dupla_marcacao' => false, 'em_branco' => false],
            ['leitura_id' => $leitura->id, 'questao_numero' => 2, 'marcacao' => null, 'dupla_marcacao' => false, 'em_branco' => true],
        ]);

        $action    = new CalcularResultadoAction();
        $resultado = $action->execute($leitura);

        $this->assertEquals(1, $resultado->total_acertos);
        $this->assertEquals(0, $resultado->total_erros);
        $this->assertEquals(1, $resultado->total_brancos);
    }

    public function test_lanca_excecao_sem_gabarito_ativo(): void
    {
        $this->expectException(\RuntimeException::class);

        $professor = User::factory()->professor()->create();
        $prova     = Prova::factory()->publicada()->create(['user_id' => $professor->id, 'total_questoes' => 1]);

        $cartao = CartaoResposta::create([
            'prova_id'     => $prova->id,
            'codigo_aluno' => 'ALU003',
            'qr_data'      => 'test-qr-' . uniqid(),
            'tentativa'    => 1,
        ]);

        $leitura = Leitura::create([
            'cartao_id' => $cartao->id,
            'status'    => StatusLeitura::Pendente,
            'origem'    => 'webcam',
        ]);

        (new CalcularResultadoAction())->execute($leitura);
    }
}
