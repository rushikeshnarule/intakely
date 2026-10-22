<?php
/**
 * Intakely — Compliance & Trust Service
 * Essential for Law, Medical, and Professional Services:
 * Stage compliance checklists, consent verification, DSAR data export,
 * and GDPR Right to Erasure / auto-anonymization with immutable audit logging.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;

final class ComplianceService
{
    private Database $db;
    private AuditService $audit;

    public function __construct(?Database $db = null, ?AuditService $audit = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->audit = $audit ?? new AuditService($this->db);
    }

    /**
     * Check compliance checklist for a lead entering a stage.
     */
    public function verifyStageChecklist(string $leadId, string $stageId): array
    {
        $stage = $this->db->fetchOne('SELECT name, required_fields FROM pipeline_stages WHERE id = $1', [$stageId]);
        if (!$stage) return ['passed' => true, 'missing' => []];

        $required = is_string($stage['required_fields'])
            ? json_decode($stage['required_fields'], true)
            : ($stage['required_fields'] ?? []);

        if (empty($required)) {
            return ['passed' => true, 'missing' => []];
        }

        $lead = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$leadId]);
        $custom = is_string($lead['custom_fields'] ?? null)
            ? json_decode($lead['custom_fields'], true)
            : ($lead['custom_fields'] ?? []);

        $missing = [];
        foreach ($required as $req) {
            $val = $lead[$req] ?? $custom[$req] ?? null;
            if (empty($val) || $val === false) {
                $missing[] = $req;
            }
        }

        return [
            'passed' => empty($missing),
            'missing' => $missing,
            'stage_name' => $stage['name'],
        ];
    }

    /**
     * Generate full DSAR (Data Subject Access Request) export for an email or phone number.
     */
    public function generateDsarExport(string $tenantId, string $identifier, ?string $actorUserId = null): array
    {
        // Find all matching leads
        $leads = $this->db->fetchAll(
            'SELECT * FROM leads WHERE tenant_id = $1 AND (email = $2 OR phone = $2)',
            [$tenantId, $identifier]
        );

        $leadIds = array_column($leads, 'id');
        $activities = [];
        $messages = [];
        $appointments = [];

        if (!empty($leadIds)) {
            $inClause = implode(',', array_map(fn($id) => $this->db->pdo()->quote($id), $leadIds));
            $activities = $this->db->fetchAll("SELECT * FROM lead_activities WHERE lead_id IN ({$inClause}) ORDER BY created_at ASC");
            $messages = $this->db->fetchAll("SELECT * FROM messages WHERE lead_id IN ({$inClause}) ORDER BY created_at ASC");
            $appointments = $this->db->fetchAll("SELECT * FROM appointments WHERE lead_id IN ({$inClause}) ORDER BY start_at ASC");
        }

        // Record audit
        $this->audit->log(
            action: 'dsar_export',
            resourceType: 'compliance',
            resourceId: null,
            tenantId: $tenantId,
            userId: $actorUserId,
            metadata: ['identifier' => $identifier, 'records_count' => count($leads)]
        );

        return [
            'tenant_id' => $tenantId,
            'requested_identifier' => $identifier,
            'generated_at' => date('Y-m-d H:i:s'),
            'leads' => $leads,
            'activities' => $activities,
            'messages' => $messages,
            'appointments' => $appointments,
        ];
    }

    /**
     * Execute GDPR Right to Erasure / Anonymization.
     */
    public function anonymiseLead(string $leadId, ?string $actorUserId = null): bool
    {
        $lead = $this->db->fetchOne('SELECT id, tenant_id, name, email FROM leads WHERE id = $1', [$leadId]);
        if (!$lead) return false;

        $tenantId = $lead['tenant_id'];

        // Overwrite personal identifiers while preserving aggregate record for reporting
        $this->db->execute(
            'UPDATE leads SET
                name = \'[ANONYMISED]\',
                email = NULL,
                phone = NULL,
                company = NULL,
                landing_page = NULL,
                referrer = NULL,
                custom_fields = \'{}\'::jsonb,
                form_data = \'{}\'::jsonb,
                do_not_contact = true,
                status = \'archived\',
                updated_at = NOW()
             WHERE id = $1',
            [$leadId]
        );

        // Sanitize activities and messages
        $this->db->execute(
            'UPDATE lead_activities SET content = \'[ANONYMISED CONTENT]\' WHERE lead_id = $1',
            [$leadId]
        );
        $this->db->execute(
            'UPDATE messages SET body = \'[ANONYMISED BODY]\', to_address = \'[ANONYMISED]\' WHERE lead_id = $1',
            [$leadId]
        );

        // Immutable audit entry
        $this->audit->log(
            action: 'dsar_erasure',
            resourceType: 'lead',
            resourceId: $leadId,
            tenantId: $tenantId,
            userId: $actorUserId,
            metadata: ['anonymised' => true]
        );

        return true;
    }
}
