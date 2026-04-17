<?php

namespace App\Domain\Prova\Models;

use App\Domain\Cartao\Models\CartaoResposta;
use App\Domain\Prova\Enums\StatusProva;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prova extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'provas';

    protected static function newFactory(): \Database\Factories\ProvaFactory
    {
        return \Database\Factories\ProvaFactory::new();
    }

    protected $fillable = [
        'user_id',
        'titulo',
        'disciplina',
        'turma',
        'total_questoes',
        'status',
        'nota_maxima',
        'data_aplicacao',
    ];

    protected function casts(): array
    {
        return [
            'status'          => StatusProva::class,
            'nota_maxima'     => 'decimal:2',
            'data_aplicacao'  => 'date',
            'total_questoes'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questoes(): HasMany
    {
        return $this->hasMany(Questao::class)->orderBy('numero');
    }

    public function gabaritos(): HasMany
    {
        return $this->hasMany(Gabarito::class);
    }

    public function gabaritoAtivo(): HasOne
    {
        return $this->hasOne(Gabarito::class)->where('ativo', true);
    }

    public function cartoes(): HasMany
    {
        return $this->hasMany(CartaoResposta::class);
    }

    public function scopePublicada($query)
    {
        return $query->where('status', StatusProva::Publicada);
    }

    public function scopeDoUsuario($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
