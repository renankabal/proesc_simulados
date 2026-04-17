<?php

namespace App\Application\Actions\Cartao;

use App\Application\DTOs\GerarCartaoDTO;
use App\Domain\Cartao\Models\CartaoResposta;
use App\Domain\Prova\Models\Prova;
use App\Support\Traits\HasAuditLog;
use Illuminate\Support\Str;

class GerarCartaoAction
{
    use HasAuditLog;

    public function execute(GerarCartaoDTO $dto): CartaoResposta
    {
        // Gera ID antecipadamente para incluí-lo no payload do QR
        $id = (string) Str::uuid();

        $prova = \App\Domain\Prova\Models\Prova::find($dto->provaId);

        $qrData = json_encode([
            'id'        => $id,
            'codigo'    => $dto->codigoAluno,
            'aluno'     => $dto->nomeAluno ?? $dto->codigoAluno,
            'turma'     => $dto->turma ?? '',
            'prova'     => $prova?->titulo ?? '',
            'tentativa' => $dto->tentativa,
        ], JSON_UNESCAPED_UNICODE);

        $cartao = CartaoResposta::create([
            'id'           => $id,
            'prova_id'     => $dto->provaId,
            'codigo_aluno' => $dto->codigoAluno,
            'nome_aluno'   => $dto->nomeAluno,
            'turma'        => $dto->turma,
            'tentativa'    => $dto->tentativa,
            'qr_data'      => $qrData,
            'gerado_por'   => $dto->geradoPor,
        ]);

        static::logInfo('cartao.gerado', 'cartoes_resposta', $cartao->id, [
            'prova_id'     => $dto->provaId,
            'codigo_aluno' => $dto->codigoAluno,
        ]);

        return $cartao;
    }
}
