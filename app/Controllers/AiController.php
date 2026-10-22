<?php
/**
 * Intakely — AI Assistant Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AiService;

final class AiController
{
    private AiService $ai;

    public function __construct(?AiService $ai = null)
    {
        $this->ai = $ai ?? new AiService();
    }

    public function summarize(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $input = $request->json();
        $leadId = $input['lead_id'] ?? $request->input('lead_id');

        if (!$leadId) {
            return Response::json(['error' => 'Missing lead_id'], 400);
        }

        try {
            $data = $this->ai->summarizeLead($tenantId, $leadId);
            return Response::json($data);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function suggestAction(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $input = $request->json();
        $leadId = $input['lead_id'] ?? $request->input('lead_id');

        if (!$leadId) {
            return Response::json(['error' => 'Missing lead_id'], 400);
        }

        try {
            $data = $this->ai->suggestNextAction($tenantId, $leadId);
            return Response::json($data);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function draftReply(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $input = $request->json();
        $leadId = $input['lead_id'] ?? $request->input('lead_id');
        $tone = $input['tone'] ?? 'professional';

        if (!$leadId) {
            return Response::json(['error' => 'Missing lead_id'], 400);
        }

        try {
            $data = $this->ai->draftReply($tenantId, $leadId, $tone);
            return Response::json($data);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function categorize(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $text = $request->input('text', '');

        $data = $this->ai->categorize($tenantId, $text);
        return Response::json($data);
    }

    public function translate(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $text = $request->input('text', '');
        $lang = $request->input('lang', 'en');

        $data = $this->ai->translate($tenantId, $text, $lang);
        return Response::json($data);
    }
}
