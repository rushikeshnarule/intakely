<?php
/**
 * Intakely — Lead Service
 * Central lead intake, duplicate detection via pg_trgm, scoring, routing,
 * compliance checks, timeline logging, and live SSE event broadcasting.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;
use RuntimeException;

final class LeadService
{
    private Database $db;
    private ScoringService $scoring;
    private RoutingService $routing;
    private AutomationEngine $automation;

    public function __construct(
        ?Database $db = null,
        ?ScoringService $scoring = null,
        ?RoutingService $routing = null,
        ?AutomationEngine $automation = null
    ) {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->scoring = $scoring ?? new ScoringService($this->db);
        $this->routing = $routing ?? new RoutingService($this->db);
        $this->automation = $automation ?? new AutomationEngine($this->db);
    }

    /**
     * Ingest a new lead with duplicate detection, scoring, routing, and automation.
     */
    public function createLead(string $tenantId, array $data): array
    {
        // 1. Normalization
        $name = trim($data['name'] ?? 'Unknown Enquiry');
        $email = !empty($data['email']) ? trim(strtolower($data['email'])) : null;
        $phone = !empty($data['phone']) ? preg_replace('/[^\d+]/', '', $data['phone']) : null;
        $service = $data['service'] ?? null;
        $value = isset($data['value']) && $data['value'] !== '' ? (float) $data['value'] : null;

        // 2. Duplicate detection
        $duplicateOf = null;
        $possibleDuplicates = $this->findDuplicates($tenantId, $name, $email, $phone);
        if (!empty($possibleDuplicates)) {
            $duplicateOf = $possibleDuplicates[0]['id'];
        }

        // 3. Resolve default pipeline & first stage if not supplied
        $pipelineId = $data['pipeline_id'] ?? null;
        $stageId = $data['stage_id'] ?? null;

        if (!$pipelineId || !$stageId) {
            $defaultPipeline = $this->db->fetchOne(
                'SELECT id FROM pipelines WHERE tenant_id = $1 AND is_default = true ORDER BY sort_order ASC LIMIT 1',
                [$tenantId]
            );
            if (!$defaultPipeline) {
                $defaultPipeline = $this->db->fetchOne(
                    'SELECT id FROM pipelines WHERE tenant_id = $1 ORDER BY sort_order ASC LIMIT 1',
                    [$tenantId]
                );
            }

            if ($defaultPipeline) {
                $pipelineId = $defaultPipeline['id'];
                $firstStage = $this->db->fetchOne(
                    'SELECT id, sla_hours FROM pipeline_stages WHERE tenant_id = $1 AND pipeline_id = $2 ORDER BY sort_order ASC LIMIT 1',
                    [$tenantId, $pipelineId]
                );
                $stageId = $firstStage['id'] ?? null;
                $stageSlaHours = $firstStage['sla_hours'] ?? 24;
            }
        }

        // 4. Scoring
        $scoringResult = $this->scoring->computeScore($tenantId, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'source' => $data['source'] ?? 'web_form',
            'source_detail' => $data['source_detail'] ?? null,
            'service' => $service,
            'value' => $value,
            'custom_fields' => $data['custom_fields'] ?? [],
            'form_data' => $data['form_data'] ?? [],
        ]);

        $score = $scoringResult['score'];
        $priority = $scoringResult['priority'];

        // 5. Routing (assignee + auto-tags)
        $routingResult = $this->routing->routeLead($tenantId, [
            'name' => $name,
            'service' => $service,
            'source' => $data['source'] ?? 'web_form',
            'custom_fields' => $data['custom_fields'] ?? [],
        ]);

        $assignedTo = $data['assigned_to'] ?? $routingResult['assigned_to'];
        $initialTags = array_merge(
            $data['tags'] ?? [],
            $routingResult['tags'] ?? []
        );

        if ($duplicateOf) {
            $initialTags[] = 'Possible Duplicate';
        }

        // 6. SLA deadline calculation
        $slaDeadline = $this->scoring->computeSlaDeadline($priority, $stageSlaHours ?? null);

        // 7. Insert lead
        $stmt = $this->db->query(
            'INSERT INTO leads (
                id, tenant_id, pipeline_id, stage_id, assigned_to,
                name, email, phone, phone_country, company,
                source, source_detail, utm_source, utm_medium, utm_campaign,
                landing_page, referrer, service, value, currency,
                score, priority, status, tags, custom_fields, form_data,
                consent_given, consent_at, duplicate_of, sla_deadline,
                created_at, updated_at
            ) VALUES (
                gen_random_uuid(), $1, $2, $3, $4,
                $5, $6, $7, $8, $9,
                $10, $11, $12, $13, $14,
                $15, $16, $17, $18, $19,
                $20, $21, \'open\', $22, $23, $24,
                $25, $26, $27, $28,
                NOW(), NOW()
            ) RETURNING *',
            [
                $tenantId,
                $pipelineId,
                $stageId,
                $assignedTo,
                $name,
                $email,
                $phone,
                $data['phone_country'] ?? null,
                $data['company'] ?? null,
                $data['source'] ?? 'web_form',
                $data['source_detail'] ?? null,
                $data['utm_source'] ?? null,
                $data['utm_medium'] ?? null,
                $data['utm_campaign'] ?? null,
                $data['landing_page'] ?? null,
                $data['referrer'] ?? null,
                $service,
                $value,
                $data['currency'] ?? 'GBP',
                $score,
                $priority,
                json_encode(array_values(array_unique($initialTags))),
                json_encode($data['custom_fields'] ?? []),
                json_encode($data['form_data'] ?? []),
                !empty($data['consent_given']),
                !empty($data['consent_given']) ? date('Y-m-d H:i:s') : null,
                $duplicateOf,
                $slaDeadline,
            ]
        );

        $lead = $stmt->fetch();
        $leadId = $lead['id'];

        // 8. Log initial activity
        $sourceDesc = ucfirst(str_replace('_', ' ', $data['source'] ?? 'web form'));
        $this->db->execute(
            'INSERT INTO lead_activities (id, tenant_id, lead_id, type, content, metadata, created_at)
             VALUES (gen_random_uuid(), $1, $2, \'form_submission\', $3, $4, NOW())',
            [
                $tenantId,
                $leadId,
                "Lead captured via {$sourceDesc}." . ($duplicateOf ? " Flagged as possible duplicate of #{$duplicateOf}." : ""),
                json_encode(['source' => $data['source'] ?? 'web_form', 'score' => $score, 'priority' => $priority]),
            ]
        );

        // 9. Dispatch automations (asynchronous or immediate)
        $this->automation->dispatch($tenantId, 'lead_created', [
            'lead' => $lead,
            'lead_id' => $leadId,
            'urgency_detected' => $scoringResult['has_urgency'] ?? false,
        ]);

        // 10. Live broadcast via PostgreSQL NOTIFY
        try {
            $payload = json_encode([
                'event' => 'lead_created',
                'tenant_id' => $tenantId,
                'lead' => [
                    'id' => $lead['id'],
                    'name' => $lead['name'],
                    'service' => $lead['service'],
                    'value' => $lead['value'],
                    'stage_id' => $lead['stage_id'],
                    'priority' => $lead['priority'],
                    'score' => $lead['score'],
                ],
            ]);
            $this->db->query("NOTIFY leads, " . $this->db->pdo()->quote($payload));
        } catch (\Throwable) {
            // Non-blocking if NOTIFY fails
        }

        return $lead;
    }

    /**
     * Move a lead to a new stage, checking compliance checklist.
     */
    public function updateStage(string $leadId, string $newStageId, ?string $userId = null): array
    {
        $lead = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$leadId]);
        if (!$lead) {
            throw new RuntimeException("Lead [{$leadId}] not found.");
        }

        $tenantId = $lead['tenant_id'];

        // Target stage
        $targetStage = $this->db->fetchOne(
            'SELECT * FROM pipeline_stages WHERE id = $1 AND tenant_id = $2',
            [$newStageId, $tenantId]
        );
        if (!$targetStage) {
            throw new RuntimeException("Target stage not found.");
        }

        // Compliance check: verify required_fields for the stage
        $requiredFields = is_string($targetStage['required_fields'])
            ? json_decode($targetStage['required_fields'], true)
            : ($targetStage['required_fields'] ?? []);

        if (!empty($requiredFields)) {
            $customFields = is_string($lead['custom_fields'])
                ? json_decode($lead['custom_fields'], true)
                : ($lead['custom_fields'] ?? []);

            $missing = [];
            foreach ($requiredFields as $field) {
                if (empty($lead[$field]) && empty($customFields[$field])) {
                    $missing[] = ucwords(str_replace('_', ' ', $field));
                }
            }

            if (!empty($missing)) {
                return [
                    'success' => false,
                    'error' => 'Compliance requirement not met: Cannot move to stage "' . $targetStage['name'] . '" without: ' . implode(', ', $missing),
                    'missing_fields' => $missing,
                ];
            }
        }

        // Old stage for activity log
        $oldStageName = 'Unknown';
        if ($lead['stage_id']) {
            $oldStage = $this->db->fetchOne('SELECT name FROM pipeline_stages WHERE id = $1', [$lead['stage_id']]);
            $oldStageName = $oldStage['name'] ?? 'Unknown';
        }

        // Status update (won / lost / open)
        $status = 'open';
        if ($targetStage['is_won']) {
            $status = 'won';
        } elseif ($targetStage['is_lost']) {
            $status = 'lost';
        }

        // Execute stage change
        $this->db->execute(
            'UPDATE leads SET stage_id = $1, status = $2, updated_at = NOW() WHERE id = $3',
            [$newStageId, $status, $leadId]
        );

        // Record activity timeline
        $this->db->execute(
            'INSERT INTO lead_activities (id, tenant_id, lead_id, user_id, type, content, metadata, created_at)
             VALUES (gen_random_uuid(), $1, $2, $3, \'stage_change\', $4, $5, NOW())',
            [
                $tenantId,
                $leadId,
                $userId,
                "Stage changed from {$oldStageName} to {$targetStage['name']}.",
                json_encode(['from_stage_id' => $lead['stage_id'], 'to_stage_id' => $newStageId]),
            ]
        );

        // Dispatch stage_changed automation
        $lead['stage_id'] = $newStageId;
        $lead['status'] = $status;
        $this->automation->dispatch($tenantId, 'stage_changed', [
            'lead' => $lead,
            'lead_id' => $leadId,
            'from_stage' => $oldStageName,
            'to_stage' => $targetStage['name'],
        ]);

        // Live broadcast
        try {
            $payload = json_encode([
                'event' => 'lead_moved',
                'tenant_id' => $tenantId,
                'lead_id' => $leadId,
                'stage_id' => $newStageId,
                'stage_name' => $targetStage['name'],
            ]);
            $this->db->query("NOTIFY leads, " . $this->db->pdo()->quote($payload));
        } catch (\Throwable) {}

        return [
            'success' => true,
            'lead_id' => $leadId,
            'stage_id' => $newStageId,
            'stage_name' => $targetStage['name'],
        ];
    }

    /**
     * Find potential duplicate leads using trigram similarity on name, plus exact match on email and phone.
     */
    public function findDuplicates(string $tenantId, string $name, ?string $email = null, ?string $phone = null): array
    {
        $clauses = [];
        $params = [$tenantId];
        $paramIndex = 2;

        if ($email !== null && $email !== '') {
            $clauses[] = "email = $" . $paramIndex;
            $params[] = $email;
            $paramIndex++;
        }

        if ($phone !== null && $phone !== '') {
            $clauses[] = "phone = $" . $paramIndex;
            $params[] = $phone;
            $paramIndex++;
        }

        // Trigram similarity on name > 0.6
        if (strlen($name) >= 3) {
            $clauses[] = "similarity(name, $" . $paramIndex . ") > 0.6";
            $params[] = $name;
            $paramIndex++;
        }

        if (empty($clauses)) {
            return [];
        }

        $sql = "SELECT id, name, email, phone, company, status, created_at
                FROM leads
                WHERE tenant_id = $1 AND (" . implode(' OR ', $clauses) . ")
                ORDER BY created_at DESC
                LIMIT 5";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Merge duplicate lead into primary lead.
     */
    public function mergeLeads(string $primaryId, string $duplicateId, ?string $userId = null): bool
    {
        $primary = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$primaryId]);
        $duplicate = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$duplicateId]);

        if (!$primary || !$duplicate || $primary['tenant_id'] !== $duplicate['tenant_id']) {
            return false;
        }

        $tenantId = $primary['tenant_id'];

        // Reassign activities, tasks, messages, appointments, files to primary lead
        $this->db->execute('UPDATE lead_activities SET lead_id = $1 WHERE lead_id = $2', [$primaryId, $duplicateId]);
        $this->db->execute('UPDATE tasks SET lead_id = $1 WHERE lead_id = $2', [$primaryId, $duplicateId]);
        $this->db->execute('UPDATE messages SET lead_id = $1 WHERE lead_id = $2', [$primaryId, $duplicateId]);
        $this->db->execute('UPDATE appointments SET lead_id = $1 WHERE lead_id = $2', [$primaryId, $duplicateId]);
        $this->db->execute('UPDATE files SET lead_id = $1 WHERE lead_id = $2', [$primaryId, $duplicateId]);

        // Merge tags
        $primaryTags = json_decode($primary['tags'] ?? '[]', true) ?: [];
        $duplicateTags = json_decode($duplicate['tags'] ?? '[]', true) ?: [];
        $mergedTags = array_values(array_unique(array_merge($primaryTags, $duplicateTags)));

        // Update primary
        $this->db->execute(
            'UPDATE leads SET tags = $1, updated_at = NOW() WHERE id = $2',
            [json_encode($mergedTags), $primaryId]
        );

        // Mark duplicate as archived and link to primary
        $this->db->execute(
            'UPDATE leads SET status = \'archived\', duplicate_of = $1, updated_at = NOW() WHERE id = $2',
            [$primaryId, $duplicateId]
        );

        // Log merge activity
        $this->db->execute(
            'INSERT INTO lead_activities (id, tenant_id, lead_id, user_id, type, content, metadata, created_at)
             VALUES (gen_random_uuid(), $1, $2, $3, \'system\', $4, $5, NOW())',
            [
                $tenantId,
                $primaryId,
                $userId,
                "Merged duplicate lead #{$duplicateId} ({$duplicate['name']}) into this lead.",
                json_encode(['duplicate_id' => $duplicateId, 'duplicate_name' => $duplicate['name']]),
            ]
        );

        return true;
    }

    /**
     * Global search using PostgreSQL tsvector full-text search.
     */
    public function search(string $tenantId, string $query, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        // Full text search across leads, search_vector
        return $this->db->fetchAll(
            'SELECT l.id, l.name, l.email, l.phone, l.company, l.service, l.value,
                    l.priority, l.score, l.status, ps.name as stage_name, ps.color as stage_color
             FROM leads l
             LEFT JOIN pipeline_stages ps ON l.stage_id = ps.id
             WHERE l.tenant_id = $1
               AND (
                 l.search_vector @@ plainto_tsquery(\'english\', $2)
                 OR l.name ILIKE $3
                 OR l.email ILIKE $3
                 OR l.phone ILIKE $3
               )
             ORDER BY l.created_at DESC
             LIMIT $4',
            [$tenantId, $query, '%' . $query . '%', $limit]
        );
    }
}
