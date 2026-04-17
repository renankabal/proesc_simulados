<?php

namespace App\Domain\Prova\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alternativa extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'alternativas';

    protected $fillable = [
        'questao_id',
        'letra',
        'correta',
        'texto',
    ];

    protected function casts(): array
    {
        return [
            'correta' => 'boolean',
        ];
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }
}
