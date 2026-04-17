<?php

namespace App\Application\Actions\Gabarito;

use App\Domain\Prova\Models\Gabarito;
use App\Domain\Prova\Models\Prova;
use App\Support\Traits\HasAuditLog;
use Illuminate\Support\Facades\DB;

class SalvarGabaritoAction
{
    use HasAuditLog;

    public function execute(Prova $prova, array $respostas, string $criadoPor): Gabarito
    {
        return DB::transaction(function () use ($prova, $respostas, $criadoPor) {
            // Desativa gabarito anterior
            $prova->gabaritos()->where('ativo', true)->update(['ativo' => false]);

            $versao = $prova->gabaritos()->max('versao') + 1;

            $gabarito = Gabarito::create([
                'prova_id'   => $prova->id,
                'versao'     => $versao,
                'ativo'      => true,
                'criado_por' => $criadoPor,
                'respostas'  => $respostas,
            ]);

            static::logInfo('gabarito.salvo', 'gabaritos', $gabarito->id, [
                'prova_id' => $prova->id,
                'versao'   => $versao,
            ]);

            return $gabarito;
        });
    }
}
