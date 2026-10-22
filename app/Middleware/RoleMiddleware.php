<?php
/**
 * Intakely — Role Middleware
 * Enforces role-based and permission-based access control.
 * Used as per-route middleware (not global).
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

final class RoleMiddleware
{
    /**
     * Role hierarchy: higher index = more privileges.
     */
    private const ROLE_HIERARCHY = [
        'viewer'  => 0,
        'agent'   => 1,
        'manager' => 2,
        'admin'   => 3,
        'owner'   => 4,
    ];

    /** @var string[] Required roles (user must have at least one) */
    private array $requiredRoles;

    /** @var string[] Required permissions (user must have all) */
    private array $requiredPermissions;

    /**
     * @param string[]|string $roles       One or more roles required (user needs at least one)
     * @param string[]|string $permissions One or more permissions required (user needs all)
     */
    public function __construct(
        array|string $roles = [],
        array|string $permissions = [],
    ) {
        $this->requiredRoles       = is_string($roles) ? [$roles] : $roles;
        $this->requiredPermissions = is_string($permissions) ? [$permissions] : $permissions;
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $request->getAttribute('_user');

        if ($user === null) {
            if ($request->isApi()) {
                return Response::json([
                    'error'   => 'Unauthenticated',
                    'message' => 'Authentication is required.',
                ], 401);
            }
            return Response::redirect('/login');
        }

        $userRole = $user['role'] ?? 'viewer';

        // Check role-based access
        if (!empty($this->requiredRoles) && !$this->hasRequiredRole($userRole)) {
            return $this->forbidden($request);
        }

        // Check permission-based access
        if (!empty($this->requiredPermissions) && !$this->hasRequiredPermissions($user)) {
            return $this->forbidden($request);
        }

        return $next($request);
    }

    /**
     * Check if the user's role meets the minimum required role level.
     * A higher role implicitly has access to lower-role routes.
     */
    private function hasRequiredRole(string $userRole): bool
    {
        $userLevel = self::ROLE_HIERARCHY[$userRole] ?? -1;

        foreach ($this->requiredRoles as $required) {
            $requiredLevel = self::ROLE_HIERARCHY[$required] ?? PHP_INT_MAX;
            if ($userLevel >= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has ALL required permissions.
     * Owners and admins implicitly have all permissions.
     */
    private function hasRequiredPermissions(array $user): bool
    {
        $userRole = $user['role'] ?? 'viewer';

        // Owners and admins bypass permission checks
        if (in_array($userRole, ['owner', 'admin'], true)) {
            return true;
        }

        // Decode custom permissions from JSONB
        $userPermissions = $user['permissions'] ?? '[]';
        if (is_string($userPermissions)) {
            $userPermissions = json_decode($userPermissions, true) ?: [];
        }

        foreach ($this->requiredPermissions as $perm) {
            if (!in_array($perm, $userPermissions, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper to create a role middleware instance for a minimum role level.
     */
    public static function requireRole(string ...$roles): self
    {
        return new self(roles: $roles);
    }

    /**
     * Helper to create a permission-based middleware instance.
     */
    public static function requirePermission(string ...$permissions): self
    {
        return new self(permissions: $permissions);
    }

    private function forbidden(Request $request): Response
    {
        if ($request->isApi()) {
            return Response::json([
                'error'   => 'Forbidden',
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        return Response::html(
            $this->forbiddenPage(),
            403
        );
    }

    private function forbiddenPage(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Denied — Intakely</title>
            <style>
                *{margin:0;padding:0;box-sizing:border-box}
                body{font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f9fa;color:#1a1a2e}
                .card{text-align:center;max-width:480px;padding:3rem 2rem;background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
                h1{font-size:1.5rem;font-weight:600;margin-bottom:.75rem;color:#dc2626}
                p{color:#64748b;line-height:1.6;margin-bottom:1.5rem}
                a{color:#4f46e5;text-decoration:none;font-weight:500}
                a:hover{text-decoration:underline}
            </style>
        </head>
        <body>
            <div class="card">
                <h1>Access Denied</h1>
                <p>You don't have permission to access this page.
                   Contact your administrator if you believe this is an error.</p>
                <a href="/dashboard">← Back to Dashboard</a>
            </div>
        </body>
        </html>
        HTML;
    }
}
