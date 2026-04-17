<?php

namespace App\Application\Actions\Prova;

use App\Application\DTOs\CriarProvaDTO;
use App\Domain\Prova\Models\Prova;
use App\Support\Traits\HasAuditLog;

class CriarProvaAction
{
    use HasAuditLog;

    public function execute(CriarProvaDTO $dto): Prova
    {
        $prova = Prova::create([
            'user_id'        => $dto->userId,
            'titulo'         => $dto->titulo,
            'disciplina'     => $dto->disciplina,
            'turma'          => $dto->turma,
            'total_questoes' => $dto->totalQuestoes,
            'nota_maxima'    => $dto->notaMaxima,
            'data_aplicacao' => $dto->dataAplicacao,
            'status'         => $dto->status,
        ]);

        static::logInfo('prova.criada', 'provas', $prova->id, [
            'titulo' => $prova->titulo,
        ]);

        return $prova;
    }
}
