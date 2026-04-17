<?php

namespace App\Domain\Prova\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gabarito extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'gabaritos';

    protected $fillable = [
        'prova_id',
        'versao',
        'ativo',
        'criado_por',
        'respostas',
    ];

    protected function casts(): array
    {
        return [
            'ativo'    => 'boolean',
            'respostas' => 'array',
            'versao'   => 'integer',
        ];
    }

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function respostaParaQuestao(int $numero): ?string
    {
        return $this->respostas[(string) $numero] ?? null;
    }
}
