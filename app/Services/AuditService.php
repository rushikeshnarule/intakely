<?php
/**
 * Intakely — Audit Service
 * Records immutable, append-only audit events into partitioned audit_log table.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;

final class AuditService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    /**
     * Log an audit event.
     */
    public function log(
        string $action,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $tenantId = null,
        ?string $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $metadata = []
    ): void {
        $ip = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');

        // Clean IP for postgres INET type
        if ($ip === '::1' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '127.0.0.1';
        }

        try {
            $this->db->execute(
                'INSERT INTO audit_log (
                    id, tenant_id, user_id, action, resource_type, resource_id,
                    old_data, new_data, ip_address, user_agent, metadata, created_at
                ) VALUES (
                    gen_random_uuid(), $1, $2, $3, $4, $5,
                    $6, $7, $8::inet, $9, $10, NOW()
                )',
                [
                    $tenantId,
                    $userId,
                    $action,
                    $resourceType,
                    $resourceId,
                    $oldData !== null ? json_encode($oldData) : null,
                    $newData !== null ? json_encode($newData) : null,
                    $ip,
                    $ua,
                    json_encode($metadata),
                ]
            );
        } catch (\Throwable $e) {
            // Fail silently or log to PHP error log so audit failure doesn't break main flow
            error_log('AuditLog error: ' . $e->getMessage());
        }
    }

    /**
     * Fetch recent audit logs for a tenant.
     */
    public function getLogs(string $tenantId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, u.name as user_name, u.email as user_email
             FROM audit_log a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.tenant_id = $1
             ORDER BY a.created_at DESC
             LIMIT $2 OFFSET $3',
            [$tenantId, $limit, $offset]
        );
    }

    /**
     * Get platform-wide audit logs (for super-admin).
     */
    public function getPlatformLogs(int $limit = 100, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, t.name as tenant_name, u.name as user_name
             FROM audit_log a
             LEFT JOIN tenants t ON a.tenant_id = t.id
             LEFT JOIN users u ON a.user_id = u.id
             ORDER BY a.created_at DESC
             LIMIT $1 OFFSET $2',
            [$limit, $offset]
        );
    }
}
