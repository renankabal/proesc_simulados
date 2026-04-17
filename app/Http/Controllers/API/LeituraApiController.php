<?php

namespace App\Http\Controllers\API;

use App\Application\Actions\Leitura\CalcularResultadoAction;
use App\Application\Actions\Leitura\ProcessarLeituraAction;
use App\Application\DTOs\ProcessarLeituraDTO;
use App\Domain\Cartao\Models\CartaoResposta;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeituraApiController extends Controller
{
    public function store(
        Request $request,
        ProcessarLeituraAction $processarAction,
        CalcularResultadoAction $calcularAction
    ): JsonResponse {
        $data = $request->validate([
            'qr_data'     => ['required', 'string'],
            'imagem'      => ['required', 'string'],  // base64
            'respostas'   => ['required', 'array', 'min:1'],
            'respostas.*.questao_numero' => ['required', 'integer', 'min:1'],
            'respostas.*.marcacao'       => ['nullable', 'string', 'in:A,B,C,D,E'],
            'respostas.*.dupla_marcacao' => ['nullable', 'boolean'],
            'respostas.*.em_branco'      => ['nullable', 'boolean'],
            'respostas.*.confianca'      => ['nullable', 'numeric', 'min:0', 'max:1'],
            'origem'      => ['nullable', 'string', 'in:webcam,upload'],
            'metadados'   => ['nullable', 'array'],
        ]);

        $cartao = $this->resolverCartaoPorQr($data['qr_data']);

        if (!$cartao) {
            return response()->json(['error' => 'QR Code não encontrado.'], 404);
        }

        $dto = new ProcessarLeituraDTO(
            cartaoId:     $cartao->id,
            imagemBase64: $data['imagem'],
            origem:       $data['origem'] ?? 'webcam',
            lidoPor:      $request->user()?->id,
            metadadosOmr: $data['metadados'] ?? [],
        );

        $leitura  = $processarAction->execute($dto, $data['respostas']);
        $resultado = $calcularAction->execute($leitura);

        return response()->json([
            'leitura_id'    => $leitura->id,
            'resultado_url' => route('resultados.show', $resultado),
            'resultado'     => [
                'id'                => $resultado->id,
                'total_acertos'     => $resultado->total_acertos,
                'total_questoes'    => $resultado->total_questoes,
                'nota_final'        => $resultado->nota_final,
                'percentual_acerto' => $resultado->percentual_acerto,
            ],
        ], 201);
    }

    public function qrInfo(Request $request): JsonResponse
    {
        $qrData = $request->validate(['qr_data' => ['required', 'string']])['qr_data'];

        $cartao = $this->resolverCartaoPorQr($qrData, withProva: true);

        if (!$cartao) {
            return response()->json(['error' => 'Cartão não encontrado.'], 404);
        }

        return response()->json([
            'cartao_id'    => $cartao->id,
            'codigo_aluno' => $cartao->codigo_aluno,
            'nome_aluno'   => $cartao->nome_aluno,
            'turma'        => $cartao->turma,
            'prova'        => [
                'id'             => $cartao->prova->id,
                'titulo'         => $cartao->prova->titulo,
                'total_questoes' => $cartao->prova->total_questoes,
            ],
        ]);
    }

    /**
     * Localiza o CartaoResposta a partir do conteúdo lido do QR Code.
     *
     * Suporta dois formatos:
     *   - Novo (JSON): {"id":"uuid","codigo":"...","aluno":"...","turma":"..."}
     *   - Legado (pipe): prova_id|codigo_aluno|tentativa|token
     */
    private function resolverCartaoPorQr(string $qrData, bool $withProva = false): ?CartaoResposta
    {
        $query = $withProva
            ? CartaoResposta::with('prova')
            : CartaoResposta::query();

        // Formato JSON (novo): usa o campo 'id' para lookup eficiente por PK
        if (str_starts_with(ltrim($qrData), '{')) {
            $payload = json_decode($qrData, true);
            if (!empty($payload['id'])) {
                $cartao = $query->find($payload['id']);
                if ($cartao) {
                    return $cartao;
                }
            }
        }

        // Formato legado ou fallback: busca exata pelo campo qr_data
        return $query->where('qr_data', $qrData)->first();
    }
}
