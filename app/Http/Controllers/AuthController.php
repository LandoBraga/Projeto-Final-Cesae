<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Hashing\HashManager;
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
        $data = $request->only(['name', 'email', 'password']);

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_USER,
            'active' => true,
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $data['email'])->where('active', true)->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json(['user' => $user, 'token' => $user->api_token]);
    }

    /**
     * Termina a sessão atual do utilizador autenticado.
     */
    public function logout(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $user->api_token = null;
        $user->save();

        return response()->json(['message' => 'Sessão terminada.']);
    }

    public function changePassword(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $data = $request->only(['current_password', 'new_password']);

        $validator = Validator::make($data, [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Password atual incorreta'], 403);
        }

        $user->password = $data['new_password'];
        $user->save();

        return response()->json(['message' => 'Password alterada com sucesso.']);
    }
}
