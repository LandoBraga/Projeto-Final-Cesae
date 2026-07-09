<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/register',
        tags: ['Auth'],
        summary: 'Registar utilizador',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'João Silva'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'profile_id', type: 'integer', nullable: true, example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilizador criado',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'abc123'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function register(Request $request)
    {
        // Trabalhamos apenas com os campos previstos para evitar efeitos colaterais no request.
        $data = $request->only(['name', 'email', 'password', 'password_confirmation', 'profile_id']);

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'profile_id' => ['nullable', 'integer', 'exists:user_profiles,id'],
        ]);

        // Qualquer falha de validação devolve a lista completa de erros para o frontend.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile = isset($data['profile_id'])
            ? UserProfile::find($data['profile_id'])
            : UserProfile::where('name', User::ROLE_USER)->first();

        if (!$profile) {
            $profile = UserProfile::firstOrCreate(['name' => User::ROLE_USER]);
        }

        $profileId = $profile->id;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'profile_id' => $profileId,
            'active' => true,
            'api_token' => Str::random(60),
        ]);

        return response()->json(['user' => $user, 'token' => $user->api_token], 201)
            ->cookie('api_token', $user->api_token, 60 * 24 * 30, '/', null, false, false);
    }

    #[OA\Post(
        path: '/login',
        tags: ['Auth'],
        summary: 'Autenticar utilizador',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autenticado com sucesso',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'abc123'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Credenciais inválidas')
        ]
    )]
    public function login(Request $request)
    {
        // O login só aceita os campos essenciais.
        $data = $request->only(['email', 'password']);

        $validator = Validator::make($data, [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Rejeita logo pedidos incompletos ou mal estruturados.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Só utilizadores ativos podem autenticar-se.
        $user = User::where('email', $data['email'])->where('active', true)->first();

        // Não distinguimos email inexistente de password errada por segurança.
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        // Garante que, se o registo ficou sem perfil, o acesso volta a usar o perfil base.
        if (!$user->profile_id) {
            $defaultProfile = UserProfile::firstOrCreate(['name' => User::ROLE_USER]);
            $user->profile_id = $defaultProfile->id;
        }

        // Renovar o token invalida acessos antigos e simplifica a gestão da sessão.
        $user->api_token = Str::random(60);
        $user->save();

        return response()->json(['user' => $user, 'token' => $user->api_token])
            ->cookie('api_token', $user->api_token, 60 * 24 * 30, '/', null, false, false);
    }

    public function logout(Request $request)
    {
        // O logout limpa token persistido e cookie para fechar a sessão em todos os canais.
        $user = $this->authenticatedUser($request);
        $user->api_token = null;
        $user->save();
        $user->setRememberToken(null);
        $user->save();

        $cookie = cookie('api_token', null, -1, '/', null, false, false, false, 'Lax');

        return response()->json(['message' => 'Sessão terminada com sucesso.'])->withCookie($cookie);
    }

    public function changePassword(Request $request)
    {
        $user = $this->authenticatedUser($request);

        // Mudamos apenas a password, mantendo o resto do perfil intacto.
        $data = $request->only(['current_password', 'new_password']);

        $validator = Validator::make($data, [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        // Erros de validação são devolvidos para o frontend poder corrigir o formulário.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Confirmamos a password antiga antes de autorizar a alteração.
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Password atual incorreta'], 403);
        }

        // Guardamos sempre a nova password com hash.
        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['message' => 'Password alterada com sucesso.']);
    }
}
