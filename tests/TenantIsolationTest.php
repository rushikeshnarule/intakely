<?php
/**
 * Intakely — Automated Test: PostgreSQL Row-Level Security (Tenant Isolation)
 * Proves that cross-tenant reads, writes, and ID-guessing fail at the database engine level.
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use RuntimeException;

final class TenantIsolationTest
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function run(): void
    {
        echo "Running TenantIsolationTest (PostgreSQL RLS verification)...\n";

        $pdo = $this->db->pdo();

        // Fetch two different tenant IDs
        $tenants = $this->db->fetchAll('SELECT id, name FROM tenants ORDER BY created_at ASC LIMIT 2');
        if (count($tenants) < 2) {
            throw new RuntimeException("Need at least 2 seeded tenants to test isolation. Run php bin/seed.php first.");
        }

        $tenantA = $tenants[0]['id'];
        $tenantB = $tenants[1]['id'];

        // -------------------------------------------------------------
        // TEST 1: Tenant A context must ONLY see Tenant A leads
        // -------------------------------------------------------------
        $this->db->beginTransaction();
        $this->db->setLocal('app.tenant_id', $tenantA);

        $leadsA = $this->db->fetchAll('SELECT id, tenant_id FROM leads');
        foreach ($leadsA as $l) {
            if ($l['tenant_id'] !== $tenantA) {
                throw new RuntimeException("[FAIL] Tenant A saw data belonging to {$l['tenant_id']}!");
            }
        }
        $this->db->commit();
        echo "  [PASS] Tenant A context returned only Tenant A leads (" . count($leadsA) . " leads verified).\n";

        // -------------------------------------------------------------
        // TEST 2: ID-guessing attack across tenants fails
        // -------------------------------------------------------------
        // Find a lead belonging to Tenant B
        $this->db->beginTransaction();
        $this->db->setLocal('app.tenant_id', $tenantB);
        $leadB = $this->db->fetchOne('SELECT id FROM leads LIMIT 1');
        $this->db->commit();

        if ($leadB) {
            $secretId = $leadB['id'];

            // Now switch context to Tenant A and try to query that exact ID without tenant filter
            $this->db->beginTransaction();
            $this->db->setLocal('app.tenant_id', $tenantA);

            $stolen = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$secretId]);
            if ($stolen !== null) {
                throw new RuntimeException("[FAIL] Security Breach! Tenant A was able to read Tenant B's lead via direct ID guessing!");
            }

            // Also test cross-tenant UPDATE fails
            $updatedRows = $this->db->execute('UPDATE leads SET name = \'HACKED\' WHERE id = $1', [$secretId]);
            if ($updatedRows > 0) {
                throw new RuntimeException("[FAIL] Security Breach! Tenant A was able to update Tenant B's lead!");
            }

            // Also test cross-tenant DELETE fails
            $deletedRows = $this->db->execute('DELETE FROM leads WHERE id = $1', [$secretId]);
            if ($deletedRows > 0) {
                throw new RuntimeException("[FAIL] Security Breach! Tenant A was able to delete Tenant B's lead!");
            }

            $this->db->commit();
            echo "  [PASS] Direct ID-guessing cross-tenant read/update/delete blocked by PostgreSQL RLS.\n";
        }

        // -------------------------------------------------------------
        // TEST 3: Spoofed INSERT across tenants rejected by WITH CHECK policy
        // -------------------------------------------------------------
        $this->db->beginTransaction();
        $this->db->setLocal('app.tenant_id', $tenantA);

        $insertFailed = false;
        try {
            // Attempt to insert a lead belonging to tenant B while in tenant A context
            $this->db->execute(
                'INSERT INTO leads (id, tenant_id, name, created_at, updated_at)
                 VALUES (gen_random_uuid(), $1, \'Spoofed Lead\', NOW(), NOW())',
                [$tenantB]
            );
        } catch (\Throwable $e) {
            $insertFailed = true; // Expected: RLS WITH CHECK violation
        }

        $this->db->rollBack();

        if (!$insertFailed) {
            throw new RuntimeException("[FAIL] Tenant A was able to insert a row with tenant_id = Tenant B! WITH CHECK policy missing or bypassed!");
        }

        echo "  [PASS] Spoofed cross-tenant INSERT correctly rejected by PostgreSQL RLS WITH CHECK policy.\n";
    }
}
