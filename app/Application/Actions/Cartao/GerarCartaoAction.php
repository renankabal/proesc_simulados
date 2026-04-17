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
        $qrData = implode('|', [
            $dto->provaId,
            $dto->codigoAluno,
            $dto->tentativa,
            Str::random(8),
        ]);

        $cartao = CartaoResposta::create([
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
