<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthMiddleware
{
    /**
     * Trata uma rota protegida validando o token customizado da aplicação.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Auth-Token')
            ?: $request->bearerToken()
            ?: $request->cookie('api_token')
            ?: $request->session()->get('api_token');

        $hasCookie = $request->cookies->has('api_token');
        $hasSessionToken = is_string($request->session()->get('api_token')) && $request->session()->get('api_token') !== '';

        if (!$hasCookie && !$hasSessionToken) {
            Log::debug('Cookie api_token nao encontrado. Headers: ', $request->header());
        } else {
            Log::debug('Cookie api_token encontrado: ' . ($request->cookie('api_token') ?? 'session-token'));
        }

        if (!is_string($token) || $token === '') {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Autenticação necessária. Envie X-Auth-Token no cabeçalho.',
                    'error_code' => 401
                ], 401);
            }

            return redirect('/ui/login');
        }

        // Valida o token e se o utilizador está ativo
        $user = User::with('profile')->where('api_token', $token)->where('active', true)->first();

        if (!$user) {
            Log::debug('CustomAuthMiddleware - Invalid or inactive user token', [
                'has_cookie' => $hasCookie,
                'token_value' => is_string($token) ? $token : null,
            ]);

            // 1ª Prioridade: Se a rota espera JSON, responde SEMPRE com JSON
            if ($request->expectsJson() || $request->wantsJson()) {
                $jsonResponse = response()->json([
                    'message' => 'Token inválido ou utilizador inativo.',
                    'error_code' => 401,
                    'errors' => [
                        'api_token' => ['Invalid or user is inactive.']
                    ]
                ], 401);

                // Se mesmo na API veio um cookie inválido, limpamo-lo na resposta
                return $hasCookie ? $jsonResponse->withCookie(cookie()->forget('api_token')) : $jsonResponse;
            }

            // 2ª Prioridade: Rotas Web normais
            if ($hasCookie) {
                return redirect('/ui/login')->withCookie(cookie()->forget('api_token'));
            }

            return redirect('/ui/login');
        }

        // Verifica se o utilizador tem um perfil válido
        if (!$user->profile_id || !$user->profile?->name) {
            Log::debug('CustomAuthMiddleware - User has no valid profile', [
                'has_cookie' => $hasCookie,
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

        // Define o utilizador autenticado no Guard para a requisição atual
        Auth::guard('api')->setUser($user);

        // Opcional: Garante que qualquer chamada futura a Auth::user() nesta requisição use o guard 'api' automaticamente
        Auth::shouldUse('api');

        return $next($request);
    }
}
