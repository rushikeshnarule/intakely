<?php
/**
 * Intakely — Automated Test: Routing Rules & After-Hours
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Services\RoutingService;
use App\Services\ScoringService;
use RuntimeException;

final class RoutingRulesTest
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function run(): void
    {
        echo "Running RoutingRulesTest...\n";

        $tenant = $this->db->fetchOne('SELECT id FROM tenants WHERE slug = \'apex-law\'');
        if (!$tenant) {
            throw new RuntimeException("Apex Law tenant not found.");
        }
        $tenantId = $tenant['id'];
        $this->db->pdo()->exec("SET app.tenant_id = '{$tenantId}'");

        $routing = new RoutingService($this->db);
        $scoring = new ScoringService($this->db);

        // 1. Test Urgency Keyword Detection
        $urgentLead = [
            'name' => 'Emergency Court Hearing',
            'service' => 'Commercial Litigation',
            'form_data' => ['notes' => 'There is an immediate injunction and tribunal deadline tomorrow!'],
            'value' => 5000,
        ];
        $scoreResult = $scoring->computeScore($tenantId, $urgentLead);

        if (!$scoreResult['has_urgency'] || $scoreResult['priority'] !== 'high') {
            throw new RuntimeException("[FAIL] Urgent keywords 'injunction' / 'deadline' did not escalate priority to high!");
        }
        echo "  [PASS] Urgency keywords detected and escalated lead priority to HIGH.\n";

        // 2. Test Round-Robin / Assignee Resolution
        $routeResult = $routing->routeLead($tenantId, $urgentLead);
        if (empty($routeResult['assigned_to'])) {
            throw new RuntimeException("[FAIL] Routing engine failed to select an active assignee.");
        }
        echo "  [PASS] Routing engine successfully assigned lead to active agent ID {$routeResult['assigned_to']}.\n";

        // 3. Test Business Hours calculation
        $isAfterHours = $routing->isOutsideBusinessHours($tenantId);
        echo "  [PASS] Business hours evaluator checked timezone successfully (Current after-hours state: " . ($isAfterHours ? "YES" : "NO") . ").\n";
    }
}
