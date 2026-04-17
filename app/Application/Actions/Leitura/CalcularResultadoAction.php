<?php

namespace App\Application\Actions\Leitura;

use App\Domain\Leitura\Enums\StatusLeitura;
use App\Domain\Leitura\Models\Leitura;
use App\Domain\Resultado\Models\Resultado;
use App\Support\Traits\HasAuditLog;
use Illuminate\Support\Facades\DB;

class CalcularResultadoAction
{
    use HasAuditLog;

    public function execute(Leitura $leitura): Resultado
    {
        return DB::transaction(function () use ($leitura) {
            $cartao   = $leitura->cartao()->with('prova.gabaritoAtivo')->firstOrFail();
            $prova    = $cartao->prova;
            $gabarito = $prova->gabaritoAtivo;

            if (!$gabarito) {
                throw new \RuntimeException("Prova {$prova->id} não possui gabarito ativo.");
            }

            $respostas = $leitura->respostas;
            $detalhes  = [];
            $acertos   = 0;
            $erros     = 0;
            $brancos   = 0;
            $anuladas  = 0;

            foreach ($respostas as $resposta) {
                $num     = $resposta->questao_numero;
                $gabarito_letra = $gabarito->respostaParaQuestao($num);
                $questao = $prova->questoes()->where('numero', $num)->first();
                $anulada = $questao?->anulada ?? false;

                $status = match(true) {
                    $anulada                           => 'anulada',
                    $resposta->em_branco               => 'branco',
                    $resposta->dupla_marcacao          => 'dupla',
                    $resposta->marcacao === $gabarito_letra => 'acerto',
                    default                            => 'erro',
                };

                match($status) {
                    'acerto'  => $acertos++,
                    'erro'    => $erros++,
                    'branco'  => $brancos++,
                    'anulada' => $anuladas++,
                    'dupla'   => $erros++,
                    default   => null,
                };

                $detalhes[$num] = [
                    'marcacao'  => $resposta->marcacao,
                    'gabarito'  => $gabarito_letra,
                    'status'    => $status,
                ];
            }

            $totalNaoNulas = $prova->total_questoes - $anuladas;
            $notaBruta     = $totalNaoNulas > 0
                ? round(($acertos + $anuladas) / $prova->total_questoes * $prova->nota_maxima, 2)
                : 0;

            $percentual = $totalNaoNulas > 0
                ? round($acertos / $totalNaoNulas * 100, 2)
                : 0;

            $resultado = Resultado::updateOrCreate(
                ['leitura_id' => $leitura->id],
                [
                    'gabarito_id'       => $gabarito->id,
                    'total_questoes'    => $prova->total_questoes,
                    'total_acertos'     => $acertos,
                    'total_erros'       => $erros,
                    'total_brancos'     => $brancos,
                    'total_anuladas'    => $anuladas,
                    'nota_bruta'        => $notaBruta,
                    'nota_final'        => $notaBruta,
                    'percentual_acerto' => $percentual,
                    'detalhe_questoes'  => $detalhes,
                    'recalculado_em'    => now(),
                ]
            );

            $leitura->update([
                'status'       => StatusLeitura::Confirmada,
                'confirmada_em' => now(),
            ]);

            static::logInfo('resultado.calculado', 'resultados', $resultado->id, [
                'leitura_id' => $leitura->id,
                'acertos'    => $acertos,
                'nota_final' => $notaBruta,
            ]);

            return $resultado;
        });
    }
}
