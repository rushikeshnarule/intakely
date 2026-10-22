<?php
/**
 * Intakely — Tenant Resolution Middleware
 * Resolves the current tenant from subdomain, custom domain, or session fallback.
 * Sets PostgreSQL RLS context via SET LOCAL app.tenant_id.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class TenantResolutionMiddleware
{
    /**
     * Route prefixes that do not require tenant resolution.
     */
    private const PUBLIC_PATHS = [
        '/',
        '/pricing',
        '/features',
        '/signup',
        '/login',
        '/register',
        '/forgot-password',
    ];

    /**
     * Route prefix patterns that are tenant-free.
     */
    private const PUBLIC_PREFIXES = [
        '/api/v1/webhooks/',
        '/admin/',
        '/industry/',
        '/verify-email/',
        '/reset-password/',
    ];

    /**
     * Base domain used for subdomain extraction (e.g. "intakely.com" or "localhost").
     */
    private function baseDomain(): string
    {
        $appUrl = config('app.url', 'http://localhost:8000');
        $parsed = parse_url($appUrl);
        $host   = $parsed['host'] ?? 'localhost';
        // Remove port from host if present
        return strtolower(explode(':', $host)[0]);
    }

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        // Skip tenant resolution for public/admin routes
        if ($this->isPublicRoute($path)) {
            return $next($request);
        }

        $db = Container::getInstance()->get(Database::class);

        // 1. Try subdomain resolution
        $tenant = $this->resolveFromSubdomain($request, $db);

        // 2. Try custom domain resolution
        if ($tenant === null) {
            $tenant = $this->resolveFromCustomDomain($request, $db);
        }

        // 3. Form slug resolution (for public hosted forms /f/{slug})
        if ($tenant === null && str_starts_with($path, '/f/')) {
            $formSlug = trim(substr($path, 3), '/');
            $tenant = $this->resolveFromFormSlug($formSlug, $db);
        }

        // 4. Session fallback
        if ($tenant === null && isset($_SESSION['_tenant']['id'])) {
            $tenant = $this->resolveById($_SESSION['_tenant']['id'], $db);
        }

        // 5. Local development / demo fallback: default to first active tenant if on localhost
        if ($tenant === null && (config('app.demo_mode') || str_contains($request->host(), 'localhost') || str_contains($request->host(), '127.0.0.1'))) {
            $tenant = $db->fetchOne('SELECT id, name, slug, domain, industry, settings, plan, plan_limits, is_active, created_at FROM tenants WHERE is_active = true ORDER BY created_at ASC LIMIT 1');
        }

        // No tenant found — error
        if ($tenant === null) {
            if ($request->isApi()) {
                return Response::json([
                    'error'   => 'Tenant not found',
                    'message' => 'Unable to determine the organization for this request.',
                ], 404);
            }
            return Response::html($this->tenantErrorPage(), 404);
        }

        // Check tenant is active
        if (isset($tenant['is_active']) && !$tenant['is_active']) {
            if ($request->isApi()) {
                return Response::json([
                    'error'   => 'Account suspended',
                    'message' => 'This organization account has been suspended.',
                ], 403);
            }
            return Response::html($this->tenantSuspendedPage(), 403);
        }

        // Set tenant context
        $db->beginTransaction();
        $db->setLocal('app.tenant_id', $tenant['id']);

        // Store in request and session
        $request->setAttribute('_tenant', $tenant);
        $request->setAttribute('_tenant_id', $tenant['id']);
        $_SESSION['_tenant'] = [
            'id'   => $tenant['id'],
            'name' => $tenant['name'],
            'slug' => $tenant['slug'],
        ];

        try {
            $response = $next($request);
            $db->commit();
            return $response;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function isPublicRoute(string $path): bool
    {
        // Exact matches
        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return true;
        }

        // Prefix matches
        foreach (self::PUBLIC_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract subdomain from Host header and look up tenant.
     */
    private function resolveFromSubdomain(Request $request, Database $db): ?array
    {
        $host       = strtolower(explode(':', $request->host())[0]);
        $baseDomain = $this->baseDomain();

        // Host must end with the base domain and have a subdomain prefix
        if ($host === $baseDomain || !str_ends_with($host, '.' . $baseDomain)) {
            return null;
        }

        // Extract subdomain: "acme.app.intakely.com" → "acme"
        $subdomain = substr($host, 0, -(strlen($baseDomain) + 1));

        // Only use the first segment as the slug
        if (str_contains($subdomain, '.')) {
            $subdomain = explode('.', $subdomain)[0];
        }

        if ($subdomain === '' || $subdomain === 'www') {
            return null;
        }

        return $db->fetchOne(
            'SELECT id, name, slug, domain, industry, settings, plan, plan_limits, is_active, created_at
             FROM tenants
             WHERE slug = $1',
            [$subdomain]
        );
    }

    /**
     * Look up tenant by custom domain.
     */
    private function resolveFromCustomDomain(Request $request, Database $db): ?array
    {
        $host = strtolower(explode(':', $request->host())[0]);
        $baseDomain = $this->baseDomain();

        // Skip if this is a subdomain of our base domain
        if ($host === $baseDomain || str_ends_with($host, '.' . $baseDomain)) {
            return null;
        }

        return $db->fetchOne(
            'SELECT id, name, slug, domain, industry, settings, plan, plan_limits, is_active, created_at
             FROM tenants
             WHERE domain = $1',
            [$host]
        );
    }

    /**
     * Look up tenant from form slug.
     */
    private function resolveFromFormSlug(string $formSlug, Database $db): ?array
    {
        return $db->fetchOne(
            'SELECT t.id, t.name, t.slug, t.domain, t.industry, t.settings, t.plan, t.plan_limits, t.is_active, t.created_at
             FROM forms f
             JOIN tenants t ON f.tenant_id = t.id
             WHERE f.slug = $1 AND f.is_active = true',
            [$formSlug]
        );
    }

    /**
     * Look up tenant by ID (session fallback).
     */
    private function resolveById(string $id, Database $db): ?array
    {
        return $db->fetchOne(
            'SELECT id, name, slug, domain, industry, settings, plan, plan_limits, is_active, created_at
             FROM tenants
             WHERE id = $1',
            [$id]
        );
    }

    private function tenantErrorPage(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Organization Not Found — Intakely</title>
            <style>
                *{margin:0;padding:0;box-sizing:border-box}
                body{font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f9fa;color:#1a1a2e}
                .card{text-align:center;max-width:480px;padding:3rem 2rem;background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
                h1{font-size:1.5rem;font-weight:600;margin-bottom:.75rem}
                p{color:#64748b;line-height:1.6;margin-bottom:1.5rem}
                a{color:#4f46e5;text-decoration:none;font-weight:500}
                a:hover{text-decoration:underline}
            </style>
        </head>
        <body>
            <div class="card">
                <h1>Organization Not Found</h1>
                <p>We couldn't find an organization associated with this address.
                   Please check the URL and try again.</p>
                <a href="/">← Back to Intakely</a>
            </div>
        </body>
        </html>
        HTML;
    }

    private function tenantSuspendedPage(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Account Suspended — Intakely</title>
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
                <h1>Account Suspended</h1>
                <p>This organization's account has been suspended.
                   Please contact support for assistance.</p>
                <a href="mailto:support@intakely.com">Contact Support</a>
            </div>
        </body>
        </html>
        HTML;
    }
}
