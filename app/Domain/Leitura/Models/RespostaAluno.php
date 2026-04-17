<?php

namespace App\Domain\Leitura\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespostaAluno extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    public $timestamps = false;

    protected $table = 'respostas_aluno';

    protected $fillable = [
        'leitura_id',
        'questao_numero',
        'marcacao',
        'dupla_marcacao',
        'em_branco',
        'confianca',
        'corrigida_manual',
    ];

    protected function casts(): array
    {
        return [
            'dupla_marcacao'   => 'boolean',
            'em_branco'        => 'boolean',
            'corrigida_manual' => 'boolean',
            'confianca'        => 'float',
            'questao_numero'   => 'integer',
            'created_at'       => 'datetime',
        ];
    }

    public function leitura(): BelongsTo
    {
        return $this->belongsTo(Leitura::class);
    }

    public function isValida(): bool
    {
        return !$this->dupla_marcacao && !$this->em_branco && $this->marcacao !== null;
    }
}
