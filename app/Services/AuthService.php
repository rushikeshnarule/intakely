<?php
/**
 * Intakely — Auth Service
 * User registration, authentication with Argon2id, session lifecycle,
 * permission checks, and password recovery.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;
use RuntimeException;

final class AuthService
{
    private Database $db;
    private AuditService $audit;

    public function __construct(?Database $db = null, ?AuditService $audit = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->audit = $audit ?? new AuditService($this->db);
    }

    /**
     * Authenticate user with email and password.
     */
    public function login(string $email, string $password, ?string $tenantId = null): array
    {
        $email = trim(strtolower($email));

        // Query user (note: tenant isolation policy might be active if tenantId is set)
        $sql = 'SELECT u.*, t.name as tenant_name, t.slug as tenant_slug, t.is_active as tenant_active
                FROM users u
                JOIN tenants t ON u.tenant_id = t.id
                WHERE u.email = $1';
        $params = [$email];

        if ($tenantId !== null) {
            $sql .= ' AND u.tenant_id = $2';
            $params[] = $tenantId;
        }

        $user = $this->db->fetchOne($sql, $params);

        if (!$user) {
            $this->recordFailedAttempt();
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Check if user is locked
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'error' => 'Account is temporarily locked. Please try again later.'];
        }

        // Check if tenant or user is deactivated
        if (!$user['tenant_active']) {
            return ['success' => false, 'error' => 'Organization account is inactive. Please contact support.'];
        }
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Your account has been deactivated.'];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt();
            $attempts = (int) ($user['login_attempts'] ?? 0) + 1;
            $lockedUntil = null;
            if ($attempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + 900); // 15 min lock
            }
            $this->db->execute(
                'UPDATE users SET login_attempts = $1, locked_until = $2 WHERE id = $3',
                [$attempts, $lockedUntil, $user['id']]
            );
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Reset login attempts on success
        $this->db->execute(
            'UPDATE users SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = $1',
            [$user['id']]
        );

        // Regenerate session ID to prevent session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Set session
        $_SESSION['_user_id'] = $user['id'];
        $_SESSION['_user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tenant_id' => $user['tenant_id'],
        ];
        $_SESSION['_tenant'] = [
            'id' => $user['tenant_id'],
            'name' => $user['tenant_name'],
            'slug' => $user['tenant_slug'],
        ];

        // Audit log
        $this->audit->log(
            action: 'login',
            resourceType: 'user',
            resourceId: $user['id'],
            tenantId: $user['tenant_id'],
            userId: $user['id']
        );

        return [
            'success' => true,
            'user' => $user,
            'redirect' => '/pipeline',
        ];
    }

    /**
     * Register a new tenant account and initial owner user.
     */
    public function register(
        string $businessName,
        string $ownerName,
        string $email,
        string $password,
        string $industry = 'law_firm'
    ): array {
        $email = trim(strtolower($email));
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($businessName)));
        $slug = trim($slug, '-');

        // Check if slug already exists
        $existing = $this->db->fetchOne('SELECT id FROM tenants WHERE slug = $1', [$slug]);
        if ($existing) {
            $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 5);
        }

        // Hash password with Argon2id
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        // 1. Create tenant
        $tenantService = new TenantService($this->db);
        $tenant = $tenantService->create([
            'name' => $businessName,
            'slug' => $slug,
            'industry' => $industry,
            'plan' => 'trial',
            'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
            'settings' => [
                'business_name' => $businessName,
                'email_from_name' => $businessName,
                'email_from_address' => 'hello@' . $slug . '.intakely.com',
                'currency' => 'GBP',
                'timezone' => 'Europe/London',
            ],
        ]);

        $tenantId = $tenant['id'];

        // 2. Set tenant context for user insert
        $this->db->beginTransaction();
        $this->db->setLocal('app.tenant_id', $tenantId);

        $stmt = $this->db->query(
            'INSERT INTO users (
                id, tenant_id, email, password_hash, name, role, permissions, is_active, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), $1, $2, $3, $4, \'owner\', \'["*"]\'::jsonb, true, NOW(), NOW()
            ) RETURNING *',
            [$tenantId, $email, $hash, $ownerName]
        );
        $user = $stmt->fetch();

        // 3. Apply industry template
        $tenantService->applyTemplate($tenantId, $industry);

        $this->db->commit();

        // Auto login
        $_SESSION['_user_id'] = $user['id'];
        $_SESSION['_user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => 'owner',
            'tenant_id' => $tenantId,
        ];
        $_SESSION['_tenant'] = [
            'id' => $tenantId,
            'name' => $tenant['name'],
            'slug' => $tenant['slug'],
        ];

        return [
            'success' => true,
            'tenant' => $tenant,
            'user' => $user,
        ];
    }

    /**
     * Terminate the session.
     */
    public function logout(): void
    {
        if (isset($_SESSION['_user_id'])) {
            $this->audit->log(
                action: 'logout',
                resourceType: 'user',
                resourceId: $_SESSION['_user_id'],
                tenantId: $_SESSION['_tenant']['id'] ?? null,
                userId: $_SESSION['_user_id']
            );
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Check if a user has a specific permission.
     */
    public function checkPermission(array $user, string $permission): bool
    {
        $role = $user['role'] ?? 'viewer';
        if ($role === 'owner' || $role === 'admin') {
            return true;
        }

        $permissions = is_string($user['permissions'] ?? null)
            ? json_decode($user['permissions'], true)
            : ($user['permissions'] ?? []);

        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return true;
        }

        return false;
    }

    private function recordFailedAttempt(): void
    {
        $_SESSION['_login_attempts'] = ($_SESSION['_login_attempts'] ?? 0) + 1;
        if ($_SESSION['_login_attempts'] >= 5) {
            $_SESSION['_login_locked_until'] = time() + 900;
        }
    }
}
