<?php
/**
 * Intakely — Analytics & Reporting Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class AnalyticsController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        // 1. Sources breakdown
        $sources = $this->db->fetchAll(
            'SELECT source, COUNT(*) as count, COALESCE(SUM(value), 0) as total_val
             FROM leads
             WHERE tenant_id = $1
             GROUP BY source
             ORDER BY count DESC',
            [$tenantId]
        );

        // 2. Team leaderboard
        $team = $this->db->fetchAll(
            'SELECT u.name, u.role, u.department,
                    COUNT(l.id) as assigned_count,
                    COUNT(CASE WHEN l.status = \'won\' THEN 1 END) as won_count,
                    COALESCE(SUM(CASE WHEN l.status = \'won\' THEN l.value END), 0) as won_val
             FROM users u
             LEFT JOIN leads l ON u.id = l.assigned_to
             WHERE u.tenant_id = $1 AND u.is_active = true
             GROUP BY u.id, u.name, u.role, u.department
             ORDER BY won_count DESC, assigned_count DESC',
            [$tenantId]
        );

        // 3. Stage drop-off counts
        $funnel = $this->db->fetchAll(
            'SELECT ps.name, ps.sort_order, COUNT(l.id) as count
             FROM pipeline_stages ps
             LEFT JOIN leads l ON ps.id = l.stage_id
             WHERE ps.tenant_id = $1
             GROUP BY ps.id, ps.name, ps.sort_order
             ORDER BY ps.sort_order ASC',
            [$tenantId]
        );

        return Response::view('analytics/index', [
            'title' => 'Analytics & Intelligence — Intakely',
            'activeNav' => 'analytics',
            'sources' => $sources,
            'team' => $team,
            'funnel' => $funnel,
        ]);
    }
}
