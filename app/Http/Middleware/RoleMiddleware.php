<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Restringe o acesso com base nos papéis (roles) configurados na rota.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Se o utilizador não estiver autenticado ou o seu perfil não constar nos permitidos
        if (!$user || !$user->profile || !in_array($user->profile->name, $roles, true)) {
            return response()->json([
                'message' => 'Acesso proibido para o seu perfil.'
            ], 403);
        }

        return $next($request);
    }
}
