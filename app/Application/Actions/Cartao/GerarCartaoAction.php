<?php

namespace App\Application\Actions\Cartao;

use App\Application\DTOs\GerarCartaoDTO;
use App\Domain\Cartao\Models\CartaoResposta;
use App\Support\Traits\HasAuditLog;

class GerarCartaoAction
{
    use HasAuditLog;

    public function execute(GerarCartaoDTO $dto): CartaoResposta
    {
        // Cria primeiro — HasUuids gera o ID real via evento creating
        // (id não está em $fillable, então pré-gerar um UUID separado causaria divergência)
        $cartao = CartaoResposta::create([
            'prova_id'     => $dto->provaId,
            'codigo_aluno' => $dto->codigoAluno,
            'nome_aluno'   => $dto->nomeAluno,
            'turma'        => $dto->turma,
            'tentativa'    => $dto->tentativa,
            'qr_data'      => 'tmp',
            'gerado_por'   => $dto->geradoPor,
        ]);

        // Usa o ID gerado pelo Eloquent como qr_data — garante que QR e PK são iguais
        $cartao->qr_data = $cartao->id;
        $cartao->save();

        static::logInfo('cartao.gerado', 'cartoes_resposta', $cartao->id, [
            'prova_id'     => $dto->provaId,
            'codigo_aluno' => $dto->codigoAluno,
        ]);

        return $cartao;
    }
}
