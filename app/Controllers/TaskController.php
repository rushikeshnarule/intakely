<?php
/**
 * Intakely — Tasks & Reminders Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class TaskController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        $tasks = $this->db->fetchAll(
            'SELECT t.*, l.name as lead_name, u.name as assignee_name
             FROM tasks t
             LEFT JOIN leads l ON t.lead_id = l.id
             LEFT JOIN users u ON t.assigned_to = u.id
             WHERE t.tenant_id = $1
             ORDER BY (t.completed_at IS NOT NULL) ASC, t.due_at ASC',
            [$tenantId]
        );

        return Response::view('tasks/index', [
            'title' => 'Tasks & Reminders — Intakely',
            'activeNav' => 'tasks',
            'tasks' => $tasks,
        ]);
    }

    public function complete(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $id = $request->getAttribute('id');

        $this->db->execute(
            'UPDATE tasks SET completed_at = CASE WHEN completed_at IS NULL THEN NOW() ELSE NULL END WHERE id = $1 AND tenant_id = $2',
            [$id, $tenantId]
        );

        flash('success', 'Task status updated.');
        return Response::redirect('/tasks');
    }
}
