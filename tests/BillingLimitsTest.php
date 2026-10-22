<?php
/**
 * Intakely — Automated Test: Plan Limits & Upgrading
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Services\TenantService;
use App\Services\BillingService;
use RuntimeException;

final class BillingLimitsTest
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function run(): void
    {
        echo "Running BillingLimitsTest...\n";

        $tenantService = new TenantService($this->db);
        $billing = new BillingService($this->db);

        // 1. Create a limited test tenant with 2 users max
        $tenant = $tenantService->create([
            'name' => 'Limit Test Tenant',
            'slug' => 'limit-test-' . bin2hex(random_bytes(3)),
            'plan' => 'starter',
            'plan_limits' => [
                'users' => 2,
                'leads_per_month' => 5,
                'automations' => 1,
            ],
        ]);
        $tenantId = $tenant['id'];
        $this->db->pdo()->exec("SET app.tenant_id = '{$tenantId}'");

        // Initial state should be within limits
        if (!$tenantService->isWithinPlanLimits($tenantId, 'users')) {
            throw new RuntimeException("[FAIL] Tenant should be within user limits.");
        }
        echo "  [PASS] Initial tenant state within limits.\n";

        // Upgrade to Scale plan (unlimited automations, 50 users)
        $billing->upgradeTenantPlan($tenantId, 'scale');

        $upgradedTenant = $tenantService->find($tenantId);
        if ($upgradedTenant['plan'] !== 'scale') {
            throw new RuntimeException("[FAIL] Plan upgrade to scale failed.");
        }

        $limits = json_decode($upgradedTenant['plan_limits'], true);
        if ($limits['users'] !== 50 || $limits['automations'] !== -1) {
            throw new RuntimeException("[FAIL] Upgraded plan limits were not applied correctly.");
        }
        echo "  [PASS] Plan upgrade to Scale successfully updated quotas and limits.\n";

        // Cleanup
        $this->db->execute('DELETE FROM tenants WHERE id = $1', [$tenantId]);
    }
}
