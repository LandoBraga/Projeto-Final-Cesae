<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Userprofile as UserProfile;

class RoleMiddleware
{
    /**
     * The authentication factory instance.
     */
    protected AuthFactory $auth;

    /**
     * Create a new middleware instance.
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request and delegate to the next middleware in the chain.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string...$roles The roles that are allowed to access this route
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...:roles): Response
    {
        // Get the authenticated user via API token or session (CustomAuthMiddleware handles initial auth)
        $token = $request->header('X-Auth-Token') ?: $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Autenticação necessária.',
            ], 401);
        }

        // Get user with profile eager loading to avoid N+1 queries
        try {
            $user = User::where('api_token', $token)
                ->with('profile')
                ->first();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Autenticação necessária.',
            ], 401);
        }

        if (!$user || !$user->active) {
            // Clear the API token cookie if it exists
            if ($request->hasCookie('api_token')) {
                $response = redirect('/ui/login')->withCookie(cookie()->forget('api_token'));
                return $response;
            }

            return response()->json([
                'message' => 'Autenticação necessária.',
            ], 401);
        }

        // Ensure user has a valid profile
        if (!$user->profile) {
            $defaultProfile = UserProfile::where('name', User::ROLE_USER)->first();
            
            if ($defaultProfile && !$user->profile_id) {
                $user->update(['profile_id' => $defaultProfile->id]);
            } else {
                return response()->json([
                    'message' => 'Perfil inválido.',
                ], 403);
            }
        }

        // Verify if the user's role is in the allowed roles for this route
        if (!in_array($user->profile->name, $roles)) {
            return response()->json([
                'message' => 'Acesso proibido para o seu perfil.',
            ], 403);
        }

        // Proceed with request
        return $next($request);
    }
}
