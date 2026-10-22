<?php
/**
 * Intakely — Dashboard & Analytics Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class DashboardController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        // 1. KPI Stats
        $newThisMonth = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM leads WHERE tenant_id = $1 AND created_at >= date_trunc(\'month\', NOW())',
            [$tenantId]
        );

        $totalOpen = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM leads WHERE tenant_id = $1 AND status = \'open\'',
            [$tenantId]
        );

        $wonCount = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM leads WHERE tenant_id = $1 AND status = \'won\'',
            [$tenantId]
        );

        $lostCount = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM leads WHERE tenant_id = $1 AND status = \'lost\'',
            [$tenantId]
        );

        $totalClosed = $wonCount + $lostCount;
        $conversionRate = $totalClosed > 0 ? round(($wonCount / $totalClosed) * 100, 1) : 0;

        $pipelineValue = (float) $this->db->fetchColumn(
            'SELECT COALESCE(SUM(value), 0) FROM leads WHERE tenant_id = $1 AND status = \'open\'',
            [$tenantId]
        );

        $slaBreaches = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM leads WHERE tenant_id = $1 AND sla_breached = true AND status = \'open\'',
            [$tenantId]
        );

        // 2. Leads by stage
        $stagesBreakdown = $this->db->fetchAll(
            'SELECT ps.name, ps.color, COUNT(l.id) as count, COALESCE(SUM(l.value), 0) as total_val
             FROM pipeline_stages ps
             LEFT JOIN leads l ON ps.id = l.stage_id AND l.status != \'archived\'
             WHERE ps.tenant_id = $1
             GROUP BY ps.id, ps.name, ps.color, ps.sort_order
             ORDER BY ps.sort_order ASC',
            [$tenantId]
        );

        // 3. Recent 5 high priority or open leads
        $recentLeads = $this->db->fetchAll(
            'SELECT l.*, ps.name as stage_name, ps.color as stage_color
             FROM leads l
             LEFT JOIN pipeline_stages ps ON l.stage_id = ps.id
             WHERE l.tenant_id = $1 AND l.status = \'open\'
             ORDER BY l.score DESC, l.created_at DESC
             LIMIT 5',
            [$tenantId]
        );

        // 4. Upcoming Consultations / Hearings
        $upcomingAppointments = $this->db->fetchAll(
            'SELECT a.*, l.name as client_name, l.service, u.name as attorney_name
             FROM appointments a
             LEFT JOIN leads l ON a.lead_id = l.id
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.tenant_id = $1
             ORDER BY a.start_at ASC
             LIMIT 4',
            [$tenantId]
        );

        // 5. Counsel Leaderboard
        $attorneyLeaderboard = $this->db->fetchAll(
            'SELECT u.name, u.role, COUNT(l.id) as active_matters, COALESCE(SUM(l.value), 0) as total_val
             FROM users u
             LEFT JOIN leads l ON u.id = l.assigned_to AND l.status = \'open\'
             WHERE u.tenant_id = $1 AND u.is_active = true
             GROUP BY u.id, u.name, u.role
             ORDER BY active_matters DESC
             LIMIT 4',
            [$tenantId]
        );

        return Response::view('dashboard/index', [
            'title' => 'Executive Overview — Intakely',
            'activeNav' => 'dashboard',
            'kpis' => [
                'new_leads' => $newThisMonth,
                'total_open' => $totalOpen,
                'won_count' => $wonCount,
                'conversion_rate' => $conversionRate,
                'pipeline_value' => $pipelineValue,
                'sla_breaches' => $slaBreaches,
            ],
            'stagesBreakdown' => $stagesBreakdown,
            'recentLeads' => $recentLeads,
            'upcomingAppointments' => $upcomingAppointments,
            'attorneyLeaderboard' => $attorneyLeaderboard,
        ]);
    }
}
