<?php
/**
 * Intakely — Background Job Worker
 * Continuously polls PostgreSQL job queue using SELECT ... FOR UPDATE SKIP LOCKED.
 * Run in CLI: php bin/worker.php [--queue=default] [--once]
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\QueueService;

$queue = 'default';
$once = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--queue=')) {
        $queue = substr($arg, 8);
    }
    if ($arg === '--once') {
        $once = true;
    }
}

echo "====================================================\n";
echo " Intakely Background Worker starting...\n";
echo " Queue: {$queue} | Mode: " . ($once ? "Single pass (--once)" : "Continuous loop") . "\n";
echo "====================================================\n\n";

$queueService = new QueueService();

$running = true;
if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, function () use (&$running) {
        echo "\nReceived SIGTERM, gracefully shutting down...\n";
        $running = false;
    });
    pcntl_signal(SIGINT, function () use (&$running) {
        echo "\nReceived SIGINT, shutting down...\n";
        $running = false;
    });
}

while ($running) {
    $jobResult = $queueService->processNext($queue);

    if ($jobResult !== null) {
        $status = $jobResult['status'];
        $class = $jobResult['class'];
        $jobId = $jobResult['job_id'];
        echo "[" . date('Y-m-d H:i:s') . "] Processed [{$jobId}] {$class}: {$status}\n";

        if (isset($jobResult['error'])) {
            echo "  Error: " . $jobResult['error'] . "\n";
        }
    } else {
        if ($once) {
            echo "No more jobs in queue '{$queue}'. Exiting.\n";
            break;
        }
        // Sleep 2 seconds before next poll to be courteous to Postgres
        sleep(2);
    }
}
