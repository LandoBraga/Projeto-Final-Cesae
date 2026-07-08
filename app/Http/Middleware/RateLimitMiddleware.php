<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * The rate limiter instance.
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', int $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if (! $this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->addHeaders(
                $next($request),
                $maxAttempts,
                $this->calculateRemainingAttempts($key, $maxAttempts)
            );
        }

        return $this->buildResponse($key, $maxAttempts, $decayMinutes);
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Para endpoints de autenticação, usar IP + email (se fornecido)
        if ($request->is('login') || $request->is('register')) {
            $email = $request->input('email', '');
            return sha1($request->ip() . '|' . $email);
        }
        
        // Para outros endpoints, usar IP + user_id (se autenticado) ou apenas IP
        $userId = $request->user() ? $request->user()->id : 'guest';
        return sha1($request->ip() . '|' . $userId . '|' . $request->path());
    }

    /**
     * Add the limit header information to the response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->limiter->attempts($key));
    }

    /**
     * Build the response when rate limit is exceeded.
     */
    protected function buildResponse(string $key, int $maxAttempts, int $decayMinutes): Response
    {
        $retryAfter = $this->limiter->availableIn($key);
        
        return response()->json([
            'message' => 'Too Many Attempts.',
            'retry_after' => $retryAfter,
        ], 429);
    }
}
