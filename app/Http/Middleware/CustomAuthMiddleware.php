<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthMiddleware
{
    /**
     * Trata uma rota protegida validando o token customizado da aplicação.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Procura pelo token no cabeçalho customizado, Bearer, ou cookie
        $token = $request->header('X-Auth-Token') 
                ?: $request->bearerToken() 
                ?: ($request->hasCookie('api_token') ? $request->cookie('api_token') : null);

        if (!is_string($token) || $token === '') {
            // Se for uma request AJAX/JSON, retornar JSON
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Autenticação necessária. Envie X-Auth-Token no cabeçalho.'
                ], 401);
            }
            // Se for uma request web normal, redirecionar para login
            return redirect('/ui/login');
        }

        // Valida o token e se o utilizador está ativo
        // Com eager loading do perfil para evitar N+1 queries
        $user = User::with('profile')->where('api_token', $token)->where('active', true)->first();

        if (!$user) {
            // Token inválido - limpar cookie se existir
            if ($request->hasCookie('api_token')) {
                $response = redirect('/ui/login')->withCookie(cookie()->forget('api_token'));
                return $response;
            }
            
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Token inválido ou utilizador inativo.'
                ], 401);
            }
            
            return redirect('/ui/login');
        }

        // Define o utilizador no Request para ficar disponível globalmente via $request->user() ou auth()->user()
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
