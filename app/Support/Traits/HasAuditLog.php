<?php

namespace App\Support\Traits;

use App\Infrastructure\Models\LogProcessamento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditLog
{
    public static function log(
        string $evento,
        string $nivel = 'info',
        ?string $entidade = null,
        ?string $entidadeId = null,
        array $payload = []
    ): void {
        LogProcessamento::create([
            'evento'      => $evento,
            'nivel'       => $nivel,
            'entidade'    => $entidade,
            'entidade_id' => $entidadeId,
            'user_id'     => Auth::id(),
            'payload'     => empty($payload) ? null : $payload,
            'ip'          => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }

    public static function logInfo(string $evento, ?string $entidade = null, ?string $id = null, array $payload = []): void
    {
        static::log($evento, 'info', $entidade, $id, $payload);
    }

    public static function logWarning(string $evento, ?string $entidade = null, ?string $id = null, array $payload = []): void
    {
        static::log($evento, 'warning', $entidade, $id, $payload);
    }

    public static function logError(string $evento, ?string $entidade = null, ?string $id = null, array $payload = []): void
    {
        static::log($evento, 'error', $entidade, $id, $payload);
    }
}
