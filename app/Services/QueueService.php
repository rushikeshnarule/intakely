<?php
/**
 * Intakely — Queue Service
 * High-performance PostgreSQL job queue using SELECT ... FOR UPDATE SKIP LOCKED.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;
use Throwable;

final class QueueService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    /**
     * Push a job onto the queue.
     *
     * @param string $jobClass Fully qualified job class name (implements execute())
     * @param array $payload Data passed to the job
     * @param string $queue Queue name
     * @param int $delaySeconds Delay before job becomes available
     * @param int $maxAttempts Max retries
     * @return string Job UUID
     */
    public function push(
        string $jobClass,
        array $payload = [],
        string $queue = 'default',
        int $delaySeconds = 0,
        int $maxAttempts = 3
    ): string {
        $data = [
            'class' => $jobClass,
            'payload' => $payload,
        ];

        $stmt = $this->db->query(
            'INSERT INTO jobs (id, queue, payload, attempts, max_attempts, available_at, created_at)
             VALUES (gen_random_uuid(), $1, $2, 0, $3, NOW() + ($4 || \' seconds\')::interval, NOW())
             RETURNING id',
            [$queue, json_encode($data), $maxAttempts, $delaySeconds]
        );

        $row = $stmt->fetch();
        return $row['id'];
    }

    /**
     * Reserve and fetch the next available job using SELECT ... FOR UPDATE SKIP LOCKED.
     */
    public function reserveNext(string $queue = 'default'): ?array
    {
        $sql = 'UPDATE jobs
                SET reserved_at = NOW(),
                    attempts = attempts + 1
                WHERE id = (
                    SELECT id FROM jobs
                    WHERE queue = $1
                      AND available_at <= NOW()
                      AND reserved_at IS NULL
                      AND completed_at IS NULL
                      AND failed_at IS NULL
                    ORDER BY available_at ASC
                    FOR UPDATE SKIP LOCKED
                    LIMIT 1
                )
                RETURNING *';

        return $this->db->fetchOne($sql, [$queue]);
    }

    /**
     * Mark a job as completed.
     */
    public function complete(string $jobId): void
    {
        $this->db->execute(
            'UPDATE jobs SET completed_at = NOW(), reserved_at = NULL WHERE id = $1',
            [$jobId]
        );
    }

    /**
     * Mark a job as failed, or re-queue if attempts < max_attempts.
     */
    public function fail(string $jobId, Throwable|string $error, int $retryDelaySeconds = 30): void
    {
        $errorMsg = is_string($error) ? $error : ($error->getMessage() . "\n" . $error->getTraceAsString());
        
        $job = $this->db->fetchOne('SELECT attempts, max_attempts FROM jobs WHERE id = $1', [$jobId]);
        if ($job && $job['attempts'] < $job['max_attempts']) {
            // Re-queue with exponential backoff
            $backoff = $retryDelaySeconds * ($job['attempts']);
            $this->db->execute(
                'UPDATE jobs
                 SET reserved_at = NULL,
                     available_at = NOW() + ($2 || \' seconds\')::interval,
                     error = $3
                 WHERE id = $1',
                [$jobId, $backoff, $errorMsg]
            );
        } else {
            // Permanently failed
            $this->db->execute(
                'UPDATE jobs
                 SET failed_at = NOW(),
                     reserved_at = NULL,
                     error = $2
                 WHERE id = $1',
                [$jobId, $errorMsg]
            );
        }
    }

    /**
     * Process one job.
     */
    public function processNext(string $queue = 'default'): ?array
    {
        $job = $this->reserveNext($queue);
        if (!$job) {
            return null;
        }

        $payload = json_decode($job['payload'], true);
        $jobClass = $payload['class'] ?? null;
        $jobData = $payload['payload'] ?? [];

        try {
            if (!class_exists($jobClass)) {
                throw new RuntimeException("Job class [{$jobClass}] not found.");
            }

            $instance = new $jobClass();
            $instance->handle($jobData);

            $this->complete($job['id']);
            return ['status' => 'success', 'job_id' => $job['id'], 'class' => $jobClass];
        } catch (Throwable $e) {
            $this->fail($job['id'], $e);
            return ['status' => 'failed', 'job_id' => $job['id'], 'class' => $jobClass, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process all currently pending jobs (useful for testing or demo "Run now" button).
     */
    public function processAllPending(string $queue = 'default', int $limit = 50): array
    {
        $results = [];
        for ($i = 0; $i < $limit; $i++) {
            $res = $this->processNext($queue);
            if ($res === null) {
                break;
            }
            $results[] = $res;
        }
        return $results;
    }

    /**
     * Get queue stats.
     */
    public function getStats(): array
    {
        $pending = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM jobs WHERE completed_at IS NULL AND failed_at IS NULL AND reserved_at IS NULL'
        ) ?: 0;

        $running = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM jobs WHERE reserved_at IS NOT NULL AND completed_at IS NULL AND failed_at IS NULL'
        ) ?: 0;

        $completed = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM jobs WHERE completed_at >= NOW() - INTERVAL \'24 hours\''
        ) ?: 0;

        $failed = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM jobs WHERE failed_at >= NOW() - INTERVAL \'24 hours\''
        ) ?: 0;

        return [
            'pending' => (int) $pending,
            'running' => (int) $running,
            'completed_24h' => (int) $completed,
            'failed_24h' => (int) $failed,
        ];
    }
}
