<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Cria uma nova conta de utilizador para o sistema.
     */
    public function register(Request $request)
    {
        $data = $request->only(['name', 'email', 'password', 'profile_id']);

        $validator = Validator::make($data, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'profile_id' => ['nullable', 'integer', 'exists:user_profiles,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar se o perfil existe e está ativo
        if (isset($data['profile_id'])) {
            $profileId = $data['profile_id'];
        } else {
            $defaultProfile = UserProfile::where('name', User::ROLE_USER)->first();
            $profileId = $defaultProfile?->id;
        }
        
        if (!$profileId) {
            return response()->json(['message' => 'Perfil inválido'], 422);
        }

        // Cifragem explícita da password recorrendo à Facade Hash.
        // Previne o armazenamento de credenciais em texto limpo na base de dados.
        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'profile_id' => $profileId,
            'active'    => true,
            'api_token' => Str::random(60),
        ]);

        return response()->json(['user' => $user, 'token' => $user->api_token], 201);
    }

    /**
     * Inicia uma sessão para um utilizador existente.
     */
    public function login(Request $request)
    {
        $data = $request->only(['email', 'password']);

        $validator = Validator::make($data, [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Procura pelo utilizador ativo correspondente ao email fornecido
        $user = User::where('email', $data['email'])->where('active', true)->first();

        // Valida se o utilizador existe e se a password introduzida coincide com o hash gravado
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        // Garantir que o utilizador tem um perfil válido
        if (!$user->profile_id) {
            $defaultProfile = UserProfile::where('name', User::ROLE_USER)->first();
            if ($defaultProfile) {
                $user->profile_id = $defaultProfile->id;
            }
        }

        // Regenera o API Token a cada novo início de sessão bem-sucedido
        $user->api_token = Str::random(60);
        $user->save();

        // Definir cookie com o token para autenticação em páginas web
        // Path '/' para que o cookie esteja disponível em todas as páginas
        // SameSite: Lax para permitir em links internos
        // HttpOnly: false para que o JavaScript possa ler o cookie (necessário para o frontend)
        $cookie = cookie('api_token', $user->api_token, 60 * 24 * 30, '/', null, false, false);

        return response()->json(['user' => $user, 'token' => $user->api_token])
            ->withCookie($cookie);
    }

    /**
     * Termina a sessão atual do utilizador autenticado.
     */
    public function logout(Request $request)
    {
        // Utiliza o método consistente centralizado da API (authenticatedUser)
        // em vez de '$request->user()' para obter corretamente a instância do modelo.
        $user = $this->authenticatedUser($request);

        // Revoga o token atual limpando o campo na base de dados
        $user->api_token = null;
        $user->save();
        
        // Clear any remember token as well
        $user->setRememberToken(null);
        $user->save();

        // Remover cookie com o mesmo path e parâmetros
        $cookie = cookie('api_token', null, -1, '/', null, false, false);

        return response()->json(['message' => 'Sessão terminada com sucesso.'])
            ->withCookie($cookie);
    }

    /**
     * Altera a password do utilizador atualmente autenticado.
     */
    public function changePassword(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $data = $request->only(['current_password', 'new_password']);

        $validator = Validator::make($data, [
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verifica se a password atual introduzida corresponde à password em vigor
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Password atual incorreta'], 403);
        }

        // Aplica a cifragem obrigatória com 'Hash::make' à nova password definida.
        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['message' => 'Password alterada com sucesso.']);
    }
}
