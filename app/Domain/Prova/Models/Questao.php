<?php

namespace App\Domain\Prova\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questao extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'questoes';

    protected $fillable = [
        'prova_id',
        'numero',
        'anulada',
        'peso',
    ];

    protected function casts(): array
    {
        return [
            'anulada' => 'boolean',
            'peso'    => 'decimal:2',
            'numero'  => 'integer',
        ];
    }

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class);
    }

    public function alternativas(): HasMany
    {
        return $this->hasMany(Alternativa::class)->orderBy('letra');
    }
}
