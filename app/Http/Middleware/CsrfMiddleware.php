<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session as SessionContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CsrfMiddleware
{
    /**
     * The session instance.
     */
    protected SessionContract $session;

    /**
     * CSRF Token configuration from config/csrf.php.
     */
    protected array $config = [
        'token' => '_token',
        'form_token_name' => '_token',
        'token_expire' => 60 * 60, // Default: 1 hour
        'token_expire_on_close' => true,
        'same_site' => 'lax',
    ];

    /**
     * Create a new middleware instance.
     */
    public function __construct(SessionContract $session)
    {
        $this->session = $session;
    }

    /**
     * Handle an incoming request and delegate to the next middleware in the chain.
     * Validates CSRF token from headers, cookies, or session storage.
     */
    public function handle($request, Closure $next): Response
    {
        // Skip CSRF validation for certain HTTP methods and routes
        if ($this->shouldSkipCsrfValidation($request)) {
            return $next($request);
        }

        // Get the CSRF token from various sources (header, cookie, or session)
        $token = $this->getCsrfTokenFromRequest($request);

        // If no valid token is found, reject the request
        if (!$token || !$this->validateCsrfToken($token)) {
            return response()->json([
                'message' => 'CSRF Token inválido ou expirado.',
                'error_code' => 419,
                'errors' => [
                    '_token' => ['The CSRF token is invalid or has expired.']
                ]
            ], 419);
        }

        // Regenerate the session ID to prevent fixation attacks (CSRF protection)
        $this->regenerateSessionId();

        return $next($request);
    }

    /**
     * Determine if CSRF validation should be skipped.
     */
    protected function shouldSkipCsrfValidation($request): bool
    {
        // Skip for GET requests (typically safe)
        if ($request->isMethod('GET')) {
            return true;
        }

        // Skip for internal API routes that use custom authentication
        $routeName = $request->route()->getName();
        
        // Allow authenticated API endpoints to skip CSRF validation
        if (in_array($routeName, [
            'api.auth.login',
            'api.auth.logout',
            'api.user.profile.update',
            'api.ticket.create',
            'api.equipment.create',
            'api.notification.create',
        ])) {
            // Check if user is authenticated via API token (not session)
            $token = $request->header('X-Auth-Token') ?: $request->bearerToken();
            
            if ($token && !empty($this->session->get('_token'))) {
                return true;
            }
        }

        // Skip for AJAX requests with custom auth token (application/json)
        if ($request->expectsJson() || $request->wantsJson()) {
            $hasCustomAuth = $request->header('X-Auth-Token') ?: $request->bearerToken();
            
            if (!empty($hasCustomAuth)) {
                return true;
            }
        }

        // Skip for internal system routes (admin, analytics)
        if ($routeName && Str::startsWith(strtolower($routeName), ['api.admin', 'api.analytics'])) {
            $token = $request->header('X-Admin-Token') ?: $request->bearerToken();
            
            if (!empty($token)) {
                return true;
            }
        }

        // Skip for health check and system routes
        if ($routeName && Str::startsWith(strtolower($routeName), ['api.health', 'api.status'])) {
            return true;
        }

        return false;
    }

    /**
     * Get CSRF token from request (header, cookie, or session).
     */
    protected function getCsrfTokenFromRequest($request): ?string
    {
        // Try to get token from custom header first (X-CSRF-Token)
        $token = $request->header('X-CSRF-Token');

        if ($token && !empty($this->session->get('_token'))) {
            return $token;
        }

        // Fall back to session-based CSRF token
        $token = $this->session->get('_token') ?: null;

        return $token;
    }

    /**
     * Validate the CSRF token.
     */
    protected function validateCsrfToken(string $token): bool
    {
        // Token must not be empty or whitespace-only
        if (empty(trim($token))) {
            return false;
        }

        // Verify token exists in session and matches stored value
        $storedToken = $this->session->get('_token');

        if ($storedToken !== trim($token)) {
            Log::debug('CsrfMiddleware - Token mismatch detected', [
                'provided_token' => substr(trim($token), 0, 8) . '...', // Don't log full token
                'stored_token' => $storedToken ?: null,
            ]);

            return false;
        }

        Log::debug('CsrfMiddleware - Token validation successful', [
            'session_id' => $this->session->getId(),
        ]);

        return true;
    }

    /**
     * Regenerate session ID to prevent CSRF fixation attacks.
     */
    protected function regenerateSessionId(): void
    {
        // Only regenerate if the token is valid and we have a session
        $token = $this->session->get('_token');

        if ($token && !empty($this->session->getId())) {
            try {
                $this->session->regenerate();
                
                Log::debug('CsrfMiddleware - Session ID regenerated', [
                    'old_session_id' => substr(str_replace('_', '', $this->session->getId()), 0, 8) . '...',
                    'new_session_id' => substr(str_replace('_', '', $this->session->regenerateId()), 0, 8) . '...',
                ]);
            } catch (\Exception $e) {
                Log::error('CsrfMiddleware - Failed to regenerate session ID', [
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get CSRF configuration from config/csrf.php.
     */
    protected function getCsrfConfig(): array
    {
        return $this->config;
    }
}
