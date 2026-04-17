<?php

namespace App\Domain\Cartao\Models;

use App\Domain\Leitura\Models\Leitura;
use App\Domain\Prova\Models\Prova;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CartaoResposta extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'cartoes_resposta';

    protected $fillable = [
        'prova_id',
        'codigo_aluno',
        'nome_aluno',
        'turma',
        'tentativa',
        'qr_data',
        'pdf_path',
        'gerado_por',
    ];

    protected function casts(): array
    {
        return [
            'tentativa'  => 'integer',
            'gerado_em'  => 'datetime',
        ];
    }

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class);
    }

    public function geradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerado_por');
    }

    public function leitura(): HasOne
    {
        return $this->hasOne(Leitura::class, 'cartao_id');
    }

    public function foiLido(): bool
    {
        return $this->leitura()->whereIn('status', ['confirmada'])->exists();
    }
}
