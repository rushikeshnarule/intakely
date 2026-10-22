<?php
/**
 * Intakely — Super-Admin Controllers
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;
use App\Services\QueueService;

final class AdminDashboardController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        // Platform overview metrics
        $totalTenants = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM tenants') ?: 0;
        $activeTenants = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM tenants WHERE is_active = true') ?: 0;
        $totalLeadsPlatform = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM leads') ?: 0;

        // Estimated MRR based on plans
        $growthTenants = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM tenants WHERE plan = \'growth\' AND is_active = true') ?: 0;
        $scaleTenants = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM tenants WHERE plan = \'scale\' AND is_active = true') ?: 0;
        $mrr = ($growthTenants * 149) + ($scaleTenants * 299);

        // Queue status
        $queueService = new QueueService($this->db);
        $queueStats = $queueService->getStats();

        // Recent tenants
        $tenants = $this->db->fetchAll('SELECT * FROM tenants ORDER BY created_at DESC LIMIT 10');

        return Response::view('admin/dashboard', [
            'title' => 'Super-Admin Console — Intakely',
            'activeNav' => 'admin_dashboard',
            'stats' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'total_leads' => $totalLeadsPlatform,
                'mrr' => $mrr,
            ],
            'queueStats' => $queueStats,
            'tenants' => $tenants,
        ]);
    }
}
