<?php

namespace App\Domain\Resultado\Models;

use App\Domain\Leitura\Models\Leitura;
use App\Domain\Prova\Models\Gabarito;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resultado extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'resultados';

    protected $fillable = [
        'leitura_id',
        'gabarito_id',
        'total_questoes',
        'total_acertos',
        'total_erros',
        'total_brancos',
        'total_anuladas',
        'nota_bruta',
        'nota_final',
        'percentual_acerto',
        'detalhe_questoes',
        'versao_calculo',
        'recalculado_em',
    ];

    protected function casts(): array
    {
        return [
            'nota_bruta'        => 'decimal:2',
            'nota_final'        => 'decimal:2',
            'percentual_acerto' => 'decimal:2',
            'detalhe_questoes'  => 'array',
            'calculado_em'      => 'datetime',
            'recalculado_em'    => 'datetime',
            'total_questoes'    => 'integer',
            'total_acertos'     => 'integer',
            'total_erros'       => 'integer',
            'total_brancos'     => 'integer',
            'total_anuladas'    => 'integer',
            'versao_calculo'    => 'integer',
        ];
    }

    public function leitura(): BelongsTo
    {
        return $this->belongsTo(Leitura::class);
    }

    public function gabarito(): BelongsTo
    {
        return $this->belongsTo(Gabarito::class);
    }

    public function percentualFormatado(): string
    {
        return number_format((float) $this->percentual_acerto, 1) . '%';
    }
}
