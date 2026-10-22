<?php
/**
 * Intakely — Automated Test: SLA Deadlines & Breach Logic
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Services\ScoringService;
use RuntimeException;

final class SlaCalculationTest
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function run(): void
    {
        echo "Running SlaCalculationTest...\n";

        $scoring = new ScoringService($this->db);

        // 1. High Priority SLA = 2 hours
        $highDeadline = $scoring->computeSlaDeadline('high');
        $expectedHigh = time() + 7200;
        $actualHigh = strtotime($highDeadline);

        if (abs($actualHigh - $expectedHigh) > 5) {
            throw new RuntimeException("[FAIL] High priority SLA deadline was not 2 hours from now.");
        }
        echo "  [PASS] High priority SLA deadline correctly set to 2 hours.\n";

        // 2. Normal Priority SLA = 24 hours
        $normalDeadline = $scoring->computeSlaDeadline('normal');
        $expectedNormal = time() + 86400;
        $actualNormal = strtotime($normalDeadline);

        if (abs($actualNormal - $expectedNormal) > 5) {
            throw new RuntimeException("[FAIL] Normal priority SLA deadline was not 24 hours from now.");
        }
        echo "  [PASS] Normal priority SLA deadline correctly set to 24 hours.\n";
    }
}
