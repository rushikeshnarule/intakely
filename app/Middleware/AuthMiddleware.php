<?php
/**
 * Intakely — Auth Middleware
 * Authenticates requests via session (web) or bearer token (API).
 * Blocks unauthenticated access to protected routes.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware
{
    /**
     * Routes that never require authentication.
     */
    private const PUBLIC_PATHS = [
        '/',
        '/pricing',
        '/signup',
        '/login',
        '/register',
    ];

    /**
     * Route prefix patterns that are public.
     */
    private const PUBLIC_PREFIXES = [
        '/admin/',         // Admin setup endpoints protected by secret key
        '/f/',             // Public intake forms
        '/book/',          // Public booking pages
        '/api/v1/webhooks/',
        '/api/v1/public/',
        '/sse/',
    ];

    /**
     * Maximum failed login attempts before throttling.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds (15 minutes).
     */
    private const LOCKOUT_SECONDS = 900;

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        // Skip auth for public routes
        if ($this->isPublicRoute($path)) {
            return $next($request);
        }

        $db = Container::getInstance()->get(Database::class);

        // API authentication via bearer token
        if ($request->isApi()) {
            return $this->authenticateApi($request, $db, $next);
        }

        // Web authentication via session
        return $this->authenticateSession($request, $db, $next);
    }

    /**
     * Authenticate an API request using a bearer token (API key) or active web session.
     */
    private function authenticateApi(Request $request, Database $db, callable $next): Response
    {
        // Internal AJAX requests from authenticated web session
        if (!empty($_SESSION['_user_id'])) {
            return $this->authenticateSession($request, $db, $next);
        }

        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return Response::json([
                'error'   => 'Unauthenticated',
                'message' => 'A valid API key is required.',
            ], 401);
        }

        // Hash the token for lookup (API keys are stored hashed)
        $hashedToken = hash('sha256', $token);

        $apiKey = $db->fetchOne(
            'SELECT ak.id, ak.tenant_id, ak.name, ak.permissions, ak.rate_limit, ak.expires_at,
                    ak.last_used_at, ak.is_active
             FROM api_keys ak
             WHERE ak.key_hash = $1',
            [$hashedToken]
        );

        if ($apiKey === null || !$apiKey['is_active']) {
            return Response::json([
                'error'   => 'Unauthenticated',
                'message' => 'Invalid or revoked API key.',
            ], 401);
        }

        // Check expiry
        if ($apiKey['expires_at'] !== null && strtotime($apiKey['expires_at']) < time()) {
            return Response::json([
                'error'   => 'Unauthenticated',
                'message' => 'API key has expired.',
            ], 401);
        }

        // Verify tenant match
        $tenantId = $request->getAttribute('_tenant_id');
        if ($tenantId !== null && $apiKey['tenant_id'] !== $tenantId) {
            return Response::json([
                'error'   => 'Forbidden',
                'message' => 'API key does not belong to this organization.',
            ], 403);
        }

        // Update last_used_at
        $db->execute(
            'UPDATE api_keys SET last_used_at = NOW() WHERE id = $1',
            [$apiKey['id']]
        );

        // Store auth context
        $request->setAttribute('_api_key', $apiKey);
        $request->setAttribute('_auth_method', 'api_key');

        return $next($request);
    }

    /**
     * Authenticate a web request using the session.
     */
    private function authenticateSession(Request $request, Database $db, callable $next): Response
    {
        $userId = $_SESSION['_user_id'] ?? null;

        if ($userId === null) {
            // Store intended URL for post-login redirect
            $_SESSION['_intended_url'] = $request->uri();
            return Response::redirect('/login');
        }

        // Check login throttle
        if ($this->isThrottled()) {
            $remaining = $this->throttleRemainingSeconds();
            flash('error', "Too many login attempts. Please try again in {$remaining} seconds.");
            return Response::redirect('/login');
        }

        $user = $this->loadUser($userId, $db);

        if ($user === null) {
            // Session refers to a deleted user; clear session
            unset($_SESSION['_user_id']);
            return Response::redirect('/login');
        }

        if (!$user['is_active']) {
            unset($_SESSION['_user_id']);
            flash('error', 'Your account has been deactivated. Please contact your administrator.');
            return Response::redirect('/login');
        }

        // Verify tenant match
        $tenantId = $request->getAttribute('_tenant_id');
        if ($tenantId !== null && $user['tenant_id'] !== $tenantId) {
            unset($_SESSION['_user_id']);
            flash('error', 'You do not have access to this organization.');
            return Response::redirect('/login');
        }

        // Store auth context
        $request->setAttribute('_user', $user);
        $request->setAttribute('_user_id', $user['id']);
        $request->setAttribute('_auth_method', 'session');

        return $next($request);
    }

    /**
     * Load a user by ID.
     */
    private function loadUser(string $userId, Database $db): ?array
    {
        return $db->fetchOne(
            'SELECT id, tenant_id, email, name, role,
                    is_active, permissions, last_login_at, created_at
             FROM users
             WHERE id = $1',
            [$userId]
        );
    }

    /**
     * Check if the current session is login-throttled.
     */
    private function isThrottled(): bool
    {
        $attempts  = $_SESSION['_login_attempts'] ?? 0;
        $lockedAt  = $_SESSION['_login_locked_until'] ?? 0;

        if ($attempts >= self::MAX_ATTEMPTS && time() < $lockedAt) {
            return true;
        }

        // Reset if lockout has expired
        if ($lockedAt > 0 && time() >= $lockedAt) {
            unset($_SESSION['_login_attempts'], $_SESSION['_login_locked_until']);
        }

        return false;
    }

    /**
     * Get remaining seconds until throttle expires.
     */
    private function throttleRemainingSeconds(): int
    {
        $lockedUntil = $_SESSION['_login_locked_until'] ?? 0;
        return max(0, $lockedUntil - time());
    }

    /**
     * Record a failed login attempt. Called by AuthService.
     */
    public static function recordFailedAttempt(): void
    {
        $attempts = ($_SESSION['_login_attempts'] ?? 0) + 1;
        $_SESSION['_login_attempts'] = $attempts;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $_SESSION['_login_locked_until'] = time() + self::LOCKOUT_SECONDS;
        }
    }

    /**
     * Reset login attempt counter. Called on successful login.
     */
    public static function resetAttempts(): void
    {
        unset($_SESSION['_login_attempts'], $_SESSION['_login_locked_until']);
    }

    private function isPublicRoute(string $path): bool
    {
        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return true;
        }

        foreach (self::PUBLIC_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
