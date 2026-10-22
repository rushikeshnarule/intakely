<?php
/**
 * Intakely — Security Headers Middleware
 * Sets security-related HTTP headers on every response.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

final class SecurityHeadersMiddleware
{
    /**
     * Standard security headers applied to every response.
     */
    private const HEADERS = [
        'X-Content-Type-Options'  => 'nosniff',
        'X-Frame-Options'         => 'DENY',
        'X-XSS-Protection'       => '1; mode=block',
        'Referrer-Policy'         => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data: blob:; "
            . "connect-src 'self'",
    ];

    public function handle(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $path = $request->path();
        $isEmbeddable = str_starts_with($path, '/f/') || str_starts_with($path, '/api/v1/public/');

        // Apply standard security headers
        foreach (self::HEADERS as $name => $value) {
            if ($name === 'X-Frame-Options' && $isEmbeddable) {
                continue; // Allow iframes for public forms and callback widgets
            }
            if ($name === 'Content-Security-Policy' && $isEmbeddable) {
                // Allow iframe framing from any origin for embedded forms
                $value .= "; frame-ancestors *";
            }
            $response = $response->withHeader($name, $value);
        }

        // HSTS only in production (served over HTTPS)
        if ($this->isProduction()) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Prevent caching of authenticated pages
        if ($request->hasAttribute('_user')) {
            $response = $response
                ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, private')
                ->withHeader('Pragma', 'no-cache');
        }

        return $response;
    }

    private function isProduction(): bool
    {
        return config('app.env', 'production') === 'production';
    }
}
