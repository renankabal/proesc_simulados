<?php

namespace App\Domain\Leitura\Models;

use App\Domain\Cartao\Models\CartaoResposta;
use App\Domain\Leitura\Enums\StatusLeitura;
use App\Domain\Resultado\Models\Resultado;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Leitura extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'leituras';

    protected $fillable = [
        'cartao_id',
        'lido_por',
        'status',
        'imagem_path',
        'imagem_thumbnail',
        'metadados_omr',
        'origem',
        'confirmada_em',
    ];

    protected function casts(): array
    {
        return [
            'status'        => StatusLeitura::class,
            'metadados_omr' => 'array',
            'confirmada_em' => 'datetime',
        ];
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(CartaoResposta::class, 'cartao_id');
    }

    public function lidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lido_por');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(RespostaAluno::class)->orderBy('questao_numero');
    }

    public function resultado(): HasOne
    {
        return $this->hasOne(Resultado::class);
    }

    public function isConfirmada(): bool
    {
        return $this->status === StatusLeitura::Confirmada;
    }
}
