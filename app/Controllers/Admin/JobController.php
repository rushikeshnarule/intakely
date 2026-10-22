<?php
/**
 * Intakely — Super-Admin Job Queue Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\QueueService;

final class JobController
{
    private Database $db;
    private QueueService $queue;

    public function __construct(?Database $db = null, ?QueueService $queue = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->queue = $queue ?? new QueueService($this->db);
    }

    public function index(Request $request): Response
    {
        $stats = $this->queue->getStats();
        $recentJobs = $this->db->fetchAll('SELECT * FROM jobs ORDER BY created_at DESC LIMIT 20');

        return Response::view('admin/jobs', [
            'title' => 'Job Queue & Health — Super-Admin',
            'activeNav' => 'admin_jobs',
            'stats' => $stats,
            'recentJobs' => $recentJobs,
        ]);
    }

    /**
     * "Run now" button for demo purposes: processes all pending jobs immediately.
     */
    public function runNow(Request $request): Response
    {
        $processed = $this->queue->processAllPending('default', 25);
        $count = count($processed);

        flash('success', "Processed {$count} queued background jobs synchronously.");
        return Response::redirect('/admin/jobs');
    }
}
