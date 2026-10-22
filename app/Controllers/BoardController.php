<?php
/**
 * Intakely — Board API Controller
 * Provides AJAX drag-and-drop endpoints for Kanban board movements.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;

final class BoardController
{
    private Database $db;
    private LeadService $leadService;

    public function __construct(?Database $db = null, ?LeadService $leadService = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->leadService = $leadService ?? new LeadService($this->db);
    }

    /**
     * Move a lead to a new stage via drag and drop.
     */
    public function move(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $userId = $request->getAttribute('_user_id');

        $input = $request->json();
        $leadId = $input['lead_id'] ?? null;
        $targetStageId = $input['stage_id'] ?? null;

        if (!$leadId || !$targetStageId) {
            return Response::json(['success' => false, 'error' => 'Missing lead_id or stage_id.'], 400);
        }

        try {
            $result = $this->leadService->updateStage($leadId, $targetStageId, $userId);

            if (!$result['success']) {
                return Response::json([
                    'success' => false,
                    'error' => $result['error'],
                    'missing_fields' => $result['missing_fields'] ?? [],
                ], 422);
            }

            return Response::json([
                'success' => true,
                'lead_id' => $leadId,
                'stage_id' => $targetStageId,
                'stage_name' => $result['stage_name'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
