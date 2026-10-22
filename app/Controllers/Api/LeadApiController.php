<?php
/**
 * Intakely — Public REST API v1 Leads Controller
 * Authenticated via Bearer API keys.
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;

final class LeadApiController
{
    private Database $db;
    private LeadService $leadService;

    public function __construct(?Database $db = null, ?LeadService $leadService = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->leadService = $leadService ?? new LeadService($this->db);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $leads = $this->db->fetchAll(
            'SELECT id, name, email, phone, company, service, value, score, priority, status, created_at
             FROM leads WHERE tenant_id = $1 ORDER BY created_at DESC LIMIT 50',
            [$tenantId]
        );

        return Response::json(['data' => $leads]);
    }

    public function store(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $input = $request->json();

        if (empty($input['name'])) {
            return Response::json(['error' => 'Validation error', 'message' => 'The name field is required.'], 422);
        }

        try {
            $lead = $this->leadService->createLead($tenantId, [
                'name' => $input['name'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'company' => $input['company'] ?? null,
                'service' => $input['service'] ?? null,
                'value' => $input['value'] ?? null,
                'source' => $input['source'] ?? 'api',
                'custom_fields' => $input['custom_fields'] ?? [],
            ]);

            return Response::json(['data' => $lead], 201);
        } catch (\Throwable $e) {
            return Response::json(['error' => 'Creation failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $id = $request->getAttribute('id');

        $lead = $this->db->fetchOne(
            'SELECT * FROM leads WHERE id = $1 AND tenant_id = $2',
            [$id, $tenantId]
        );

        if (!$lead) {
            return Response::json(['error' => 'Not Found'], 404);
        }

        return Response::json(['data' => $lead]);
    }

    public function update(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $id = $request->getAttribute('id');
        $input = $request->json();

        $this->db->execute(
            'UPDATE leads SET name = COALESCE($1, name), email = COALESCE($2, email), phone = COALESCE($3, phone), updated_at = NOW()
             WHERE id = $4 AND tenant_id = $5',
            [$input['name'] ?? null, $input['email'] ?? null, $input['phone'] ?? null, $id, $tenantId]
        );

        return Response::json(['message' => 'Updated successfully']);
    }
}
