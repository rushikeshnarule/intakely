<?php
/**
 * Intakely — Automation Builder Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class AutomationController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        $automations = $this->db->fetchAll(
            'SELECT * FROM automations WHERE tenant_id = $1 ORDER BY created_at ASC',
            [$tenantId]
        );

        $recentLogs = $this->db->fetchAll(
            'SELECT al.*, a.name as automation_name, l.name as lead_name
             FROM automation_logs al
             JOIN automations a ON al.automation_id = a.id
             LEFT JOIN leads l ON al.lead_id = l.id
             WHERE al.tenant_id = $1
             ORDER BY al.created_at DESC
             LIMIT 15',
            [$tenantId]
        );

        return Response::view('automations/index', [
            'title' => 'Automations & Workflows — Intakely',
            'activeNav' => 'automations',
            'automations' => $automations,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function toggle(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $id = $request->getAttribute('id');

        $automation = $this->db->fetchOne(
            'SELECT is_active FROM automations WHERE id = $1 AND tenant_id = $2',
            [$id, $tenantId]
        );

        if ($automation) {
            $newStatus = !$automation['is_active'];
            $this->db->execute(
                'UPDATE automations SET is_active = $1, updated_at = NOW() WHERE id = $2',
                [$newStatus, $id]
            );
            flash('success', 'Automation ' . ($newStatus ? 'activated' : 'paused') . '.');
        }

        return Response::redirect('/automations');
    }
}
