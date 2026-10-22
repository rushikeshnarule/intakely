<?php
/**
 * Intakely — Pipeline & Kanban Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class PipelineController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        // Fetch all pipelines for tenant
        $pipelines = $this->db->fetchAll(
            'SELECT * FROM pipelines WHERE tenant_id = $1 ORDER BY sort_order ASC',
            [$tenantId]
        );

        if (empty($pipelines)) {
            return Response::view('pipeline/empty', [
                'title' => 'No Pipeline Configured — Intakely',
                'activeNav' => 'pipeline',
            ]);
        }

        $defaultPipeline = null;
        foreach ($pipelines as $p) {
            if ($p['is_default']) {
                $defaultPipeline = $p;
                break;
            }
        }
        $selectedPipeline = $defaultPipeline ?? $pipelines[0];

        return $this->renderPipeline($request, $selectedPipeline['id'], $pipelines);
    }

    public function show(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $pipelineId = $request->getAttribute('id');

        $pipelines = $this->db->fetchAll(
            'SELECT * FROM pipelines WHERE tenant_id = $1 ORDER BY sort_order ASC',
            [$tenantId]
        );

        return $this->renderPipeline($request, $pipelineId, $pipelines);
    }

    private function renderPipeline(Request $request, string $pipelineId, array $pipelines): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        // Fetch current pipeline
        $pipeline = $this->db->fetchOne(
            'SELECT * FROM pipelines WHERE id = $1 AND tenant_id = $2',
            [$pipelineId, $tenantId]
        );

        if (!$pipeline) {
            return Response::redirect('/pipeline');
        }

        // Fetch stages
        $stages = $this->db->fetchAll(
            'SELECT * FROM pipeline_stages WHERE pipeline_id = $1 AND tenant_id = $2 ORDER BY sort_order ASC',
            [$pipelineId, $tenantId]
        );

        // Fetch leads for this pipeline
        $filterPriority = $request->query('priority');
        $filterAssignee = $request->query('assignee');
        $filterSource = $request->query('source');

        $sql = 'SELECT l.*, u.name as assignee_name, u.avatar_url as assignee_avatar
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE l.tenant_id = $1 AND l.pipeline_id = $2 AND l.status != \'archived\'';
        $params = [$tenantId, $pipelineId];
        $paramIdx = 3;

        if ($filterPriority) {
            $sql .= ' AND l.priority = $' . $paramIdx;
            $params[] = $filterPriority;
            $paramIdx++;
        }
        if ($filterAssignee) {
            $sql .= ' AND l.assigned_to = $' . $paramIdx;
            $params[] = $filterAssignee;
            $paramIdx++;
        }
        if ($filterSource) {
            $sql .= ' AND l.source = $' . $paramIdx;
            $params[] = $filterSource;
            $paramIdx++;
        }

        $sql .= ' ORDER BY l.created_at DESC';
        $leads = $this->db->fetchAll($sql, $params);

        // Group leads by stage_id
        $leadsByStage = [];
        $stageTotals = [];
        foreach ($stages as $stage) {
            $leadsByStage[$stage['id']] = [];
            $stageTotals[$stage['id']] = ['count' => 0, 'value' => 0.0];
        }

        foreach ($leads as $lead) {
            $sid = $lead['stage_id'];
            if (isset($leadsByStage[$sid])) {
                $leadsByStage[$sid][] = $lead;
                $stageTotals[$sid]['count']++;
                $stageTotals[$sid]['value'] += (float) ($lead['value'] ?? 0);
            }
        }

        // Fetch users for assignee filter
        $users = $this->db->fetchAll(
            'SELECT id, name, role FROM users WHERE tenant_id = $1 AND is_active = true ORDER BY name ASC',
            [$tenantId]
        );

        return Response::view('pipeline/index', [
            'title' => $pipeline['name'] . ' — Pipeline Board',
            'activeNav' => 'pipeline',
            'pipeline' => $pipeline,
            'pipelines' => $pipelines,
            'stages' => $stages,
            'leadsByStage' => $leadsByStage,
            'stageTotals' => $stageTotals,
            'users' => $users,
            'filterPriority' => $filterPriority,
            'filterAssignee' => $filterAssignee,
            'filterSource' => $filterSource,
        ]);
    }
}
