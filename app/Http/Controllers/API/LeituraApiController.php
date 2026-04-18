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

    public function buscarCartao(Request $request): JsonResponse
    {
        $q = $request->validate(['q' => ['required', 'string', 'min:2']])['q'];

        $cartoes = CartaoResposta::with('prova')
            ->where('codigo_aluno', 'ilike', "%{$q}%")
            ->orWhere('nome_aluno', 'ilike', "%{$q}%")
            ->orderBy('nome_aluno')
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'qr_data'      => $c->qr_data,
                'codigo_aluno' => $c->codigo_aluno,
                'nome_aluno'   => $c->nome_aluno,
                'turma'        => $c->turma,
                'prova'        => [
                    'titulo'         => $c->prova->titulo,
                    'total_questoes' => $c->prova->total_questoes,
                ],
            ]);

        return response()->json($cartoes);
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
     * Suporta três formatos:
     *   - UUID puro (atual): "019d9b8d-84bf-..." → lookup direto por PK
     *   - JSON legado:       {"id":"uuid",...}   → extrai id e faz lookup por PK
     *   - Pipe legado:       prova_id|codigo|... → busca pelo campo qr_data
     */
    /**
     * Localiza o CartaoResposta a partir do conteúdo lido do QR Code.
     *
     * Estratégia de lookup (mais rápido → mais genérico):
     *   1. UUID puro  → find() por PK, fallback where(qr_data)
     *   2. JSON       → find() pelo campo 'id', fallback where(qr_data) com JSON completo
     *   3. Qualquer   → where(qr_data) exato (pipe-legado, etc.)
     */
    private function resolverCartaoPorQr(string $qrData, bool $withProva = false): ?CartaoResposta
    {
        $base = $withProva ? CartaoResposta::with('prova') : CartaoResposta::query();

        $trimmed = trim($qrData);

        // UUID puro: tenta PK primeiro, depois campo qr_data
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $trimmed)) {
            return (clone $base)->find($trimmed)
                ?? (clone $base)->where('qr_data', $trimmed)->first();
        }

        // JSON: extrai 'id' e tenta PK, depois qr_data com o JSON completo
        if (str_starts_with(ltrim($qrData), '{')) {
            $payload = json_decode($qrData, true);
            if (!empty($payload['id'])) {
                $cartao = (clone $base)->find($payload['id']);
                if ($cartao) return $cartao;
            }
            return (clone $base)->where('qr_data', $qrData)->first();
        }

        // Pipe-legado ou qualquer outro formato: busca exata por qr_data
        return (clone $base)->where('qr_data', $qrData)->first();
    }
}
