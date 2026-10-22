<?php
/**
 * Intakely — Automated Test Suite Runner
 * Runs:
 * 1. PostgreSQL Database-Level Row-Level Security (Tenant Isolation)
 * 2. Routing Rules & Urgency Keyword Escalation
 * 3. SLA Deadlines & Breach Logic
 * 4. Automation Engine & Opt-out Suppression
 * 5. Billing Limits & Plan Quotas
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use Tests\TenantIsolationTest;
use Tests\RoutingRulesTest;
use Tests\SlaCalculationTest;
use Tests\AutomationEngineTest;
use Tests\BillingLimitsTest;

echo "====================================================\n";
echo " Intakely — Automated Test Suite\n";
echo "====================================================\n\n";

$db = new Database(
    env('DB_HOST', 'localhost'),
    (int) env('DB_PORT', '5432'),
    env('DB_NAME', 'intakely'),
    env('DB_USER', 'intakely_app'),
    env('DB_PASS', 'intakely_secret')
);

$tests = [
    new TenantIsolationTest($db),
    new RoutingRulesTest($db),
    new SlaCalculationTest($db),
    new AutomationEngineTest($db),
    new BillingLimitsTest($db),
];

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    try {
        $test->run();
        $passed++;
        echo "\n";
    } catch (\Throwable $e) {
        $failed++;
        echo "  [ERROR] " . $e->getMessage() . "\n\n";
    }
}

echo "====================================================\n";
if ($failed === 0) {
    echo " ALL TESTS PASSED! ({$passed}/" . count($tests) . " suites verified)\n";
    echo "====================================================\n";
    exit(0);
} else {
    echo " TESTS FAILED: {$failed} failed, {$passed} passed.\n";
    echo "====================================================\n";
    exit(1);
}
