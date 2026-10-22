<?php
/**
 * Intakely — CSRF Middleware
 * Validates CSRF tokens for state-changing requests.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    /**
     * HTTP methods that are considered safe (read-only).
     */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, callable $next): Response
    {
        // Safe methods don't need CSRF verification
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        $path = $request->path();

        // Skip for webhooks, public callback widgets, and hosted public forms
        if (str_starts_with($path, '/api/v1/webhooks/') ||
            str_starts_with($path, '/api/v1/public/') ||
            str_starts_with($path, '/f/') ||
            str_starts_with($path, '/book/')) {
            return $next($request);
        }

        // Skip for API routes that use bearer token authentication
        if ($request->isApi() && $request->bearerToken() !== null) {
            return $next($request);
        }

        // Validate the CSRF token
        if (!$this->tokensMatch($request)) {
            if ($request->isApi() || $request->isJson()) {
                return Response::json([
                    'error'   => 'CSRF token mismatch',
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                ], 403);
            }

            // For web requests, flash error and redirect back
            flash('error', 'Your session has expired. Please refresh the page and try again.');

            $referer = $request->header('referer', '/');
            return Response::redirect($referer, 302);
        }

        return $next($request);
    }

    /**
     * Check the request token against the session token.
     */
    private function tokensMatch(Request $request): bool
    {
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if ($sessionToken === '') {
            return false;
        }

        // Check POST body field first, then header
        $requestToken = $request->input('_csrf_token')
            ?? $request->header('x-csrf-token')
            ?? '';

        if ($requestToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $requestToken);
    }
}
