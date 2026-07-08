<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Resolve e valida o utilizador atualmente autenticado com base no token fornecido.
     * Procura o token prioritariamente no cabeçalho customizado 'X-Auth-Token'
     * ou, em alternativa, no cabeçalho padrão 'Authorization' (Bearer Token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\User
     * * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authenticatedUser(Request $request): User
    {
        // Obtém o token a partir do cabeçalho customizado, Bearer Token ou cookie
        $token = $request->header('X-Auth-Token') 
                ?: $request->bearerToken() 
                ?: ($request->hasCookie('api_token') ? $request->cookie('api_token') : null);

        // Valida se o token foi enviado e se é uma string válida
        if (!is_string($token) || $token === '') {
            abort(401, 'Autenticação necessária. Envie X-Auth-Token no cabeçalho.');
        }

        // Procura na base de dados pelo utilizador que possui o respetivo token e se encontra ativo
        // Com eager loading do perfil para evitar N+1 queries
        $user = User::with('profile')->where('api_token', $token)->where('active', true)->first();

        // Se o utilizador não for encontrado ou estiver inativo, interrompe o pedido
        if (!$user) {
            abort(401, 'Token inválido ou utilizador inativo.');
        }

        return $user;
    }

    /**
     * Garante programaticamente que o utilizador possui um dos perfis/papéis permitidos.
     * Caso o perfil não corresponda, lança uma exceção HTTP 403 (Acesso Proibido).
     *
     * @param  \App\Models\User  $user
     * @param  array  $roles
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function requireRole(User $user, array $roles): void
    {
        // Verifica de forma estrita se o perfil do utilizador consta no grupo de permissões aceites
        if (!$user->profile || !in_array($user->profile->name, $roles, true)) {
            abort(403, 'Acesso proibido para o seu perfil.');
        }
    }
}
