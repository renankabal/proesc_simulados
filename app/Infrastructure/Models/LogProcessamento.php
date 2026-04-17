<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class LogProcessamento extends Model
{
    public $timestamps = false;

    protected $table = 'logs_processamento';

    protected $fillable = [
        'evento',
        'nivel',
        'entidade',
        'entidade_id',
        'user_id',
        'payload',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
