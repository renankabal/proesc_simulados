<?php

namespace App\Http\Middleware;

use App\Support\Enums\RoleUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->ativo) {
            abort(403, 'Acesso negado.');
        }

        if (empty($roles)) {
            return $next($request);
        }

        $userRole = $user->role?->value;

        if (!in_array($userRole, $roles, true)) {
            abort(403, 'Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }
}
