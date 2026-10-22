<?php
/**
 * Intakely — Automated Test: Automation Engine & Conditions
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Services\AutomationEngine;
use App\Services\MessagingService;
use RuntimeException;

final class AutomationEngineTest
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function run(): void
    {
        echo "Running AutomationEngineTest...\n";

        $tenant = $this->db->fetchOne('SELECT id FROM tenants WHERE slug = \'apex-law\'');
        $tenantId = $tenant['id'];
        $this->db->pdo()->exec("SET app.tenant_id = '{$tenantId}'");

        $realLead = $this->db->fetchOne('SELECT id, name, value FROM leads WHERE tenant_id = $1 LIMIT 1', [$tenantId]);
        if (!$realLead) {
            throw new RuntimeException("No leads found for Apex Law.");
        }

        $automation = new AutomationEngine($this->db);

        // 1. Test condition evaluation and workflow execution
        $testAutomation = [
            'id' => '00000000-0000-0000-0000-000000000001',
            'tenant_id' => $tenantId,
            'name' => 'Test High Value Task Creation',
            'conditions' => [
                ['field' => 'value', 'operator' => 'greater_than', 'value' => 5000]
            ],
            'actions' => [
                ['type' => 'create_task', 'title' => 'Review VIP Matter: {{name}}', 'due_hours' => 24]
            ],
            'is_active' => true,
        ];

        // Should NOT trigger when value <= 5000
        $skippedResult = $automation->runAutomation($testAutomation, [
            'lead' => ['id' => $realLead['id'], 'name' => 'Low Value', 'value' => 1000]
        ]);
        if ($skippedResult['status'] !== 'skipped') {
            throw new RuntimeException("[FAIL] Automation condition failed: condition should have skipped execution.");
        }
        echo "  [PASS] Automation condition correctly skipped execution for non-matching lead.\n";

        // Should trigger when value > 5000
        $successResult = $automation->runAutomation($testAutomation, [
            'lead' => ['id' => $realLead['id'], 'name' => 'VIP Client', 'value' => 12000]
        ]);
        if ($successResult['status'] !== 'success') {
            throw new RuntimeException("[FAIL] Automation failed to execute action.");
        }
        echo "  [PASS] Automation triggered and executed task creation for matching lead.\n";

        // 2. Test Opt-out / STOP suppression
        $messaging = new MessagingService($this->db);
        // Lead with do_not_contact = true
        $lead = $this->db->fetchOne('SELECT id FROM leads WHERE tenant_id = $1 LIMIT 1', [$tenantId]);
        if ($lead) {
            $this->db->execute('UPDATE leads SET do_not_contact = true WHERE id = $1', [$lead['id']]);

            $suppressed = $messaging->sendEmail($tenantId, $lead['id'], 'test@example.com', 'Test Subject', 'Body text');
            if ($suppressed['status'] !== 'suppressed') {
                throw new RuntimeException("[FAIL] Messaging failed to suppress outbound message to opted-out lead!");
            }
            echo "  [PASS] Outbound message correctly suppressed for lead marked as Do Not Contact (opted-out).\n";

            // Restore lead
            $this->db->execute('UPDATE leads SET do_not_contact = false WHERE id = $1', [$lead['id']]);
        }
    }
}
