<?php
/**
 * Intakely — Automation Engine
 * Evaluates triggers, condition trees (AND/OR), and executes automated workflows:
 * (send email/SMS/WhatsApp, create tasks, assign, stage change, webhooks).
 * Records execution runs in automation_logs table.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;
use Throwable;

final class AutomationEngine
{
    private Database $db;
    private MessagingService $messaging;

    public function __construct(?Database $db = null, ?MessagingService $messaging = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->messaging = $messaging ?? new MessagingService($this->db);
    }

    /**
     * Dispatch an event to all matching automations for a tenant.
     *
     * @param string $tenantId
     * @param string $triggerType (e.g. 'lead_created', 'stage_changed', 'sla_breached', 'form_submitted', 'appointment_no_show')
     * @param array $context Lead and trigger data
     */
    public function dispatch(string $tenantId, string $triggerType, array $context): array
    {
        $automations = $this->db->fetchAll(
            'SELECT * FROM automations
             WHERE tenant_id = $1 AND trigger_type = $2 AND is_active = true',
            [$tenantId, $triggerType]
        );

        $results = [];
        foreach ($automations as $automation) {
            $results[] = $this->runAutomation($automation, $context);
        }

        return $results;
    }

    /**
     * Run a single automation against context.
     */
    public function runAutomation(array $automation, array $context): array
    {
        $startTime = microtime(true);
        $automationId = $automation['id'];
        $tenantId = $automation['tenant_id'];
        $lead = $context['lead'] ?? [];
        $leadId = $lead['id'] ?? $context['lead_id'] ?? null;

        $conditions = is_string($automation['conditions'])
            ? json_decode($automation['conditions'], true)
            : ($automation['conditions'] ?? []);

        // 1. Evaluate conditions
        if (!$this->evaluateConditions($conditions, $context)) {
            return ['status' => 'skipped', 'reason' => 'Conditions not met'];
        }

        // 2. Execute actions
        $actions = is_string($automation['actions'])
            ? json_decode($automation['actions'], true)
            : ($automation['actions'] ?? []);

        $executedActions = [];
        $hasError = false;
        $lastError = null;

        foreach ($actions as $action) {
            try {
                $res = $this->executeAction($tenantId, $leadId, $action, $context);
                $executedActions[] = [
                    'action' => $action['type'] ?? 'unknown',
                    'success' => true,
                    'result' => $res,
                ];
            } catch (Throwable $e) {
                $hasError = true;
                $lastError = $e->getMessage();
                $executedActions[] = [
                    'action' => $action['type'] ?? 'unknown',
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $status = $hasError ? (count($executedActions) > 1 ? 'partial' : 'failed') : 'success';

        // 3. Log to automation_logs table
        try {
            $this->db->execute(
                'INSERT INTO automation_logs (
                    id, tenant_id, automation_id, lead_id, trigger_data,
                    actions_executed, status, error, duration_ms, created_at
                ) VALUES (
                    gen_random_uuid(), $1, $2, $3, $4, $5, $6, $7, $8, NOW()
                )',
                [
                    $tenantId,
                    $automationId,
                    $leadId,
                    json_encode($context),
                    json_encode($executedActions),
                    $status,
                    $lastError,
                    $durationMs,
                ]
            );

            // Update automation stats
            $this->db->execute(
                'UPDATE automations SET run_count = run_count + 1, last_run_at = NOW() WHERE id = $1',
                [$automationId]
            );
        } catch (Throwable $e) {
            error_log('Failed to write automation_log: ' . $e->getMessage());
        }

        return [
            'status' => $status,
            'automation_id' => $automationId,
            'actions_executed' => $executedActions,
            'error' => $lastError,
        ];
    }

    /**
     * Evaluate AND/OR condition groups.
     */
    private function evaluateConditions(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $lead = $context['lead'] ?? [];

        foreach ($conditions as $item) {
            // Check if group
            if (isset($item['operator']) && in_array(strtoupper($item['operator']), ['AND', 'OR'], true)) {
                // Group evaluation
                $groupOp = strtoupper($item['operator']);
                $subConditions = $item['rules'] ?? [];
                $groupPassed = ($groupOp === 'AND');

                foreach ($subConditions as $sub) {
                    $passed = $this->matchSingleCondition($sub, $lead, $context);
                    if ($groupOp === 'AND' && !$passed) {
                        return false;
                    }
                    if ($groupOp === 'OR' && $passed) {
                        $groupPassed = true;
                        break;
                    }
                }
                if (!$groupPassed) {
                    return false;
                }
            } else {
                // Flat rule
                if (!$this->matchSingleCondition($item, $lead, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function matchSingleCondition(array $rule, array $lead, array $context): bool
    {
        $field = $rule['field'] ?? '';
        $op = $rule['operator'] ?? 'equals';
        $expected = $rule['value'] ?? '';

        $actual = $lead[$field] ?? $context[$field] ?? $lead['custom_fields'][$field] ?? null;

        return match ($op) {
            'equals' => strcasecmp((string) $actual, (string) $expected) === 0,
            'not_equals' => strcasecmp((string) $actual, (string) $expected) !== 0,
            'contains' => str_contains(strtolower((string) $actual), strtolower((string) $expected)),
            'greater_than' => (float) $actual > (float) $expected,
            'less_than' => (float) $actual < (float) $expected,
            'is_empty' => empty($actual),
            'is_not_empty' => !empty($actual),
            'in' => in_array($actual, (array) $expected, true),
            default => true,
        };
    }

    /**
     * Execute a specific workflow action.
     */
    private function executeAction(string $tenantId, ?string $leadId, array $action, array $context): mixed
    {
        $actionType = $action['type'] ?? '';
        $lead = $context['lead'] ?? ($leadId ? $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$leadId]) : []);

        switch ($actionType) {
            case 'send_email':
                $templateSlug = $action['template_slug'] ?? null;
                $subject = $action['subject'] ?? 'Message from ' . config('app.name');
                $body = $action['body'] ?? '';

                if ($templateSlug) {
                    $tpl = $this->db->fetchOne('SELECT * FROM email_templates WHERE tenant_id = $1 AND slug = $2', [$tenantId, $templateSlug]);
                    if ($tpl) {
                        $subject = $tpl['subject'];
                        $body = $tpl['body'];
                    }
                }

                if (!empty($lead['email'])) {
                    $body = $this->messaging->renderTemplate($body, $lead);
                    $subject = $this->messaging->renderTemplate($subject, $lead);
                    return $this->messaging->sendEmail($tenantId, $leadId, $lead['email'], $subject, $body, null);
                }
                return false;

            case 'send_sms':
                $body = $action['body'] ?? '';
                if (!empty($lead['phone'])) {
                    $body = $this->messaging->renderTemplate($body, $lead);
                    return $this->messaging->sendSms($tenantId, $leadId, $lead['phone'], $body);
                }
                return false;

            case 'send_whatsapp':
                $body = $action['body'] ?? '';
                if (!empty($lead['phone'])) {
                    $body = $this->messaging->renderTemplate($body, $lead);
                    return $this->messaging->sendWhatsApp($tenantId, $leadId, $lead['phone'], $body);
                }
                return false;

            case 'create_task':
                $title = $this->messaging->renderTemplate($action['title'] ?? 'Follow up on lead', $lead);
                $dueHours = (int) ($action['due_hours'] ?? 24);
                $dueAt = date('Y-m-d H:i:s', time() + ($dueHours * 3600));

                return $this->db->execute(
                    'INSERT INTO tasks (id, tenant_id, lead_id, assigned_to, title, due_at, priority, created_at, updated_at)
                     VALUES (gen_random_uuid(), $1, $2, $3, $4, $5, $6, NOW(), NOW())',
                    [
                        $tenantId,
                        $leadId,
                        $lead['assigned_to'] ?? null,
                        $title,
                        $dueAt,
                        $action['priority'] ?? 'normal',
                    ]
                );

            case 'change_stage':
                $stageSlug = $action['stage_slug'] ?? null;
                $stageId = $action['stage_id'] ?? null;
                if (!$stageId && $stageSlug) {
                    $stage = $this->db->fetchOne(
                        'SELECT id FROM pipeline_stages WHERE tenant_id = $1 AND slug = $2',
                        [$tenantId, $stageSlug]
                    );
                    $stageId = $stage['id'] ?? null;
                }
                if ($stageId && $leadId) {
                    return $this->db->execute(
                        'UPDATE leads SET stage_id = $1, updated_at = NOW() WHERE id = $2',
                        [$stageId, $leadId]
                    );
                }
                return false;

            case 'add_tag':
                $tag = $action['tag'] ?? null;
                if ($tag && $leadId) {
                    $tags = json_decode($lead['tags'] ?? '[]', true) ?: [];
                    if (!in_array($tag, $tags, true)) {
                        $tags[] = $tag;
                        return $this->db->execute(
                            'UPDATE leads SET tags = $1, updated_at = NOW() WHERE id = $2',
                            [json_encode($tags), $leadId]
                        );
                    }
                }
                return true;

            case 'set_priority':
                $priority = $action['priority'] ?? 'normal';
                if ($leadId) {
                    return $this->db->execute(
                        'UPDATE leads SET priority = $1, updated_at = NOW() WHERE id = $2',
                        [$priority, $leadId]
                    );
                }
                return false;

            case 'webhook':
            case 'call_webhook':
                $url = $action['url'] ?? null;
                if ($url) {
                    $payload = json_encode(['lead' => $lead, 'context' => $context]);
                    $opts = [
                        'http' => [
                            'method' => 'POST',
                            'header' => "Content-Type: application/json\r\nUser-Agent: Intakely-Webhook\r\n",
                            'content' => $payload,
                            'timeout' => 5,
                        ],
                    ];
                    @file_get_contents($url, false, stream_context_create($opts));
                    return true;
                }
                return false;

            default:
                return false;
        }
    }
}
