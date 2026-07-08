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
        // Procura pelo token no cabeçalho customizado (X-Auth-Token), Bearer token, ou cookie
        $token = $request->header('X-Auth-Token')
                ?: $request->bearerToken()
                ?: ($request->hasCookie('api_token') ? $request->cookie('api_token') : null);

        // Se não houver token válido, retornar erro de autenticação
        if (!$token) {
            \Log::debug('CustomAuthMiddleware - Token validation failed', [
                'has_cookie' => $request->hasCookie('api_token'),
                'token_value' => is_string($token) ? $token : null,
            ]);

            // Se for uma request AJAX/JSON, retornar JSON
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Autenticação necessária. Envie X-Auth-Token no cabeçalho.',
                    'error_code' => 401
                ], 401);
            }

            // Se for uma request web normal, redirecionar para login
            return redirect('/ui/login');
        }

        // Valida o token e se o utilizador está ativo
        // Com eager loading do perfil para evitar N+1 queries
        $user = User::with('profile')->where('api_token', $token)->where('active', true)->first();

        if (!$user) {
            \Log::debug('CustomAuthMiddleware - Invalid or inactive user token', [
                'has_cookie' => $request->hasCookie('api_token'),
                'token_value' => is_string($token) ? $token : null,
            ]);

            // Token inválido ou utilizador inativo - limpar cookie se existir
            if ($request->hasCookie('api_token')) {
                return redirect('/ui/login')->withCookie(cookie()->forget('api_token'));
            }
            
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Token inválido ou utilizador inativo.',
                    'error_code' => 401,
                    'errors' => [
                        'api_token' => ['Invalid or user is inactive.']
                    ]
                ], 401);
            }

            return redirect('/ui/login');
        }

        // Verifica se o utilizador tem um perfil válido (role definido)
        if (!$user->profile_id || !$user->profile?->name) {
            \Log::debug('CustomAuthMiddleware - User has no valid profile', [
                'has_cookie' => $request->hasCookie('api_token'),
                'token_value' => is_string($token) ? $token : null,
                'profile_id' => $user->profile_id ?? null,
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Perfil inválido.',
                    'error_code' => 403,
                    'errors' => [
                        'profile_id' => ['User must have a valid profile assigned.']
                    ]
                ], 403);
            }

            return redirect('/ui/login');
        }

        // Registra a autenticação usando Laravel's Auth::login() (melhor prática e mais seguro que shouldUse)
        \Auth::guard('api')->login($user, true);

        return $next($request);
    }
}
