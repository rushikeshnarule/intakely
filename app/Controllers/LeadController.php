<?php
/**
 * Intakely — Lead Management Controller
 * Table view, lead creation, comprehensive detail view (unified timeline, AI sidebar,
 * one-click replies, duplicate merge, compliance checklist), CSV export and global search.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;
use App\Services\MessagingService;
use App\Services\ComplianceService;
use App\Services\AiService;

final class LeadController
{
    private Database $db;
    private LeadService $leadService;
    private MessagingService $messaging;
    private ComplianceService $compliance;

    public function __construct(
        ?Database $db = null,
        ?LeadService $leadService = null,
        ?MessagingService $messaging = null,
        ?ComplianceService $compliance = null
    ) {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->leadService = $leadService ?? new LeadService($this->db);
        $this->messaging = $messaging ?? new MessagingService($this->db);
        $this->compliance = $compliance ?? new ComplianceService($this->db);
    }

    /**
     * All Leads — Table View with pagination and filters.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $status = $request->query('status', 'open');
        $search = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT l.*, ps.name as stage_name, ps.color as stage_color, u.name as assignee_name
                FROM leads l
                LEFT JOIN pipeline_stages ps ON l.stage_id = ps.id
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE l.tenant_id = $1';
        $params = [$tenantId];
        $paramIdx = 2;

        if ($status !== 'all') {
            $sql .= ' AND l.status = $' . $paramIdx;
            $params[] = $status;
            $paramIdx++;
        }

        if ($search !== '') {
            $sql .= ' AND (l.name ILIKE $' . $paramIdx . ' OR l.email ILIKE $' . $paramIdx . ' OR l.phone ILIKE $' . $paramIdx . ')';
            $params[] = '%' . $search . '%';
            $paramIdx++;
        }

        $countSql = 'SELECT COUNT(*) FROM leads l WHERE l.tenant_id = $1';
        $totalCount = (int) $this->db->fetchColumn($countSql, [$tenantId]);

        $sql .= ' ORDER BY l.created_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $leads = $this->db->fetchAll($sql, $params);

        return Response::view('leads/index', [
            'title' => 'All Leads — Intakely',
            'activeNav' => 'leads',
            'leads' => $leads,
            'status' => $status,
            'search' => $search,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($totalCount / $limit)),
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * Show lead creation form.
     */
    public function create(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $pipelines = $this->db->fetchAll('SELECT * FROM pipelines WHERE tenant_id = $1 ORDER BY sort_order ASC', [$tenantId]);
        $users = $this->db->fetchAll('SELECT id, name FROM users WHERE tenant_id = $1 AND is_active = true', [$tenantId]);

        $tenant = $this->db->fetchOne('SELECT settings FROM tenants WHERE id = $1', [$tenantId]);
        $settings = json_decode($tenant['settings'] ?? '{}', true);
        $services = $settings['services'] ?? ['General Enquiry'];

        return Response::view('leads/create', [
            'title' => 'Create New Lead — Intakely',
            'activeNav' => 'leads',
            'pipelines' => $pipelines,
            'users' => $users,
            'services' => $services,
            'selectedPipelineId' => $request->query('pipeline_id'),
        ]);
    }

    /**
     * Store new lead / matter.
     */
    public function store(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $isAjax = $request->isJson() 
            || str_contains((string) $request->header('accept', ''), 'application/json')
            || $request->header('x-requested-with') === 'XMLHttpRequest';

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'company' => $request->input('company'),
            'service' => $request->input('service'),
            'value' => $request->input('value'),
            'priority' => $request->input('priority', 'normal'),
            'pipeline_id' => $request->input('pipeline_id'),
            'assigned_to' => $request->input('assigned_to'),
            'source' => $request->input('source', 'manual'),
            'custom_fields' => $request->input('custom_fields', []),
        ];

        try {
            $lead = $this->leadService->createLead($tenantId, $data);
            
            if ($isAjax) {
                return Response::json([
                    'success' => true,
                    'message' => 'Matter created successfully.',
                    'lead' => $lead,
                ]);
            }

            flash('success', 'Lead created successfully.');
            $redirectTo = $request->input('redirect_to', '/leads/' . $lead['id']);
            return Response::redirect($redirectTo);
        } catch (\Throwable $e) {
            if ($isAjax) {
                return Response::json([
                    'success' => false,
                    'error' => 'Error creating matter: ' . $e->getMessage(),
                ], 422);
            }

            flash('error', 'Error creating lead: ' . $e->getMessage());
            return Response::redirect('/leads/create');
        }
    }

    /**
     * Show lead details: unified timeline, AI suggestions, compliance checklist, duplicates.
     */
    public function show(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $leadId = $request->getAttribute('id');

        $lead = $this->db->fetchOne(
            'SELECT l.*, ps.name as stage_name, ps.color as stage_color, ps.required_fields,
                    u.name as assignee_name, u.email as assignee_email
             FROM leads l
             LEFT JOIN pipeline_stages ps ON l.stage_id = ps.id
             LEFT JOIN users u ON l.assigned_to = u.id
             WHERE l.id = $1 AND l.tenant_id = $2',
            [$leadId, $tenantId]
        );

        if (!$lead) {
            flash('error', 'Lead not found.');
            return Response::redirect('/leads');
        }

        // Timeline activities
        $activities = $this->db->fetchAll(
            'SELECT a.*, u.name as user_name
             FROM lead_activities a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.lead_id = $1 AND a.tenant_id = $2
             ORDER BY a.created_at DESC',
            [$leadId, $tenantId]
        );

        // Open tasks
        $tasks = $this->db->fetchAll(
            'SELECT t.*, u.name as assignee_name
             FROM tasks t
             LEFT JOIN users u ON t.assigned_to = u.id
             WHERE t.lead_id = $1 AND t.tenant_id = $2
             ORDER BY t.due_at ASC',
            [$leadId, $tenantId]
        );

        // Pipeline stages for stage changer
        $stages = $this->db->fetchAll(
            'SELECT id, name, color, sort_order FROM pipeline_stages WHERE pipeline_id = $1 AND tenant_id = $2 ORDER BY sort_order ASC',
            [$lead['pipeline_id'], $tenantId]
        );

        // Check for duplicates
        $duplicates = $this->leadService->findDuplicates($tenantId, $lead['name'], $lead['email'], $lead['phone']);
        // Exclude self from duplicates list
        $duplicates = array_filter($duplicates, fn($d) => $d['id'] !== $leadId);

        // Team members for reassign
        $users = $this->db->fetchAll('SELECT id, name FROM users WHERE tenant_id = $1 AND is_active = true', [$tenantId]);

        if ($request->isJson() || $request->query('format') === 'json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return Response::json([
                'success' => true,
                'lead' => $lead,
                'activities' => $activities,
                'tasks' => $tasks,
                'stages' => $stages,
                'duplicates' => array_values($duplicates),
                'users' => $users,
            ]);
        }

        return Response::view('leads/show', [
            'title' => $lead['name'] . ' — Lead Details',
            'activeNav' => 'leads',
            'lead' => $lead,
            'activities' => $activities,
            'tasks' => $tasks,
            'stages' => $stages,
            'duplicates' => $duplicates,
            'users' => $users,
        ]);
    }

    /**
     * Add a note to timeline.
     */
    public function addNote(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $userId = $request->getAttribute('_user_id');
        $leadId = $request->getAttribute('id');
        $content = trim((string) $request->input('content', ''));

        if (!empty($content)) {
            $this->db->execute(
                'INSERT INTO lead_activities (id, tenant_id, lead_id, user_id, type, content, created_at)
                 VALUES (gen_random_uuid(), $1, $2, $3, \'note\', $4, NOW())',
                [$tenantId, $leadId, $userId, $content]
            );
            flash('success', 'Note added to timeline.');

            if ($request->isJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                $user = $this->db->fetchOne('SELECT name FROM users WHERE id = $1', [$userId]);
                return Response::json([
                    'success' => true,
                    'message' => 'Note added to timeline.',
                    'activity' => [
                        'type' => 'note',
                        'content' => $content,
                        'user_name' => $user['name'] ?? 'Counsel',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]
                ]);
            }
        }

        if ($request->isJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return Response::json(['success' => false, 'error' => 'Note content cannot be empty.'], 422);
        }

        return Response::redirect('/leads/' . $leadId);
    }

    /**
     * Quick Update for Drawer / Instant Board actions.
     */
    public function quickUpdate(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $userId = $request->getAttribute('_user_id');
        $leadId = $request->getAttribute('id');

        $lead = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1 AND tenant_id = $2', [$leadId, $tenantId]);
        if (!$lead) {
            return Response::json(['success' => false, 'error' => 'Matter not found.'], 404);
        }

        $custom = is_string($lead['custom_fields'] ?? null) ? json_decode($lead['custom_fields'], true) : ($lead['custom_fields'] ?? []);
        if (!is_array($custom)) $custom = [];

        $updates = [];
        $params = [];
        $pIdx = 1;

        if ($request->has('value')) {
            $updates[] = 'value = $' . $pIdx++;
            $params[] = (float) $request->input('value');
        }
        if ($request->has('priority')) {
            $updates[] = 'priority = $' . $pIdx++;
            $params[] = $request->input('priority');
        }
        if ($request->has('assigned_to')) {
            $updates[] = 'assigned_to = $' . $pIdx++;
            $params[] = $request->input('assigned_to') ?: null;
        }
        if ($request->has('toggle_conflict')) {
            $custom['conflict_check_passed'] = empty($custom['conflict_check_passed']);
            $updates[] = 'custom_fields = $' . $pIdx++;
            $params[] = json_encode($custom);

            $this->db->execute(
                'INSERT INTO lead_activities (id, tenant_id, lead_id, user_id, type, content, created_at)
                 VALUES (gen_random_uuid(), $1, $2, $3, \'system\', $4, NOW())',
                [$tenantId, $leadId, $userId, $custom['conflict_check_passed'] ? 'SRA Conflict of Interest check CLEARED.' : 'SRA Conflict of Interest check marked PENDING.']
            );
        }
        if ($request->has('toggle_aml')) {
            $custom['id_aml_verified'] = empty($custom['id_aml_verified']);
            $hasCustomInUpdates = false;
            foreach ($updates as $u) {
                if (str_starts_with($u, 'custom_fields')) { $hasCustomInUpdates = true; break; }
            }
            if (!$hasCustomInUpdates) {
                $updates[] = 'custom_fields = $' . $pIdx++;
                $params[] = json_encode($custom);
            }
        }

        if (!empty($updates)) {
            $updates[] = 'updated_at = NOW()';
            $sql = 'UPDATE leads SET ' . implode(', ', $updates) . ' WHERE id = $' . $pIdx++ . ' AND tenant_id = $' . $pIdx;
            $params[] = $leadId;
            $params[] = $tenantId;
            $this->db->execute($sql, $params);
        }

        return Response::json([
            'success' => true,
            'message' => 'Matter updated successfully.',
            'conflict_cleared' => !empty($custom['conflict_check_passed']),
            'aml_verified' => !empty($custom['id_aml_verified']),
        ]);
    }

    /**
     * Add task for lead.
     */
    public function addTask(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $userId = $request->getAttribute('_user_id');
        $leadId = $request->getAttribute('id');
        $title = trim((string) $request->input('title', ''));
        $dueAt = $request->input('due_at') ?: date('Y-m-d H:i:s', strtotime('+2 days'));
        $assignedTo = $request->input('assigned_to') ?: $userId;

        if (!empty($title)) {
            $this->db->execute(
                'INSERT INTO tasks (id, tenant_id, lead_id, assigned_to, title, due_at, created_by, created_at, updated_at)
                 VALUES (gen_random_uuid(), $1, $2, $3, $4, $5, $6, NOW(), NOW())',
                [$tenantId, $leadId, $assignedTo, $title, $dueAt, $userId]
            );
            flash('success', 'Task created.');
        }

        return Response::redirect('/leads/' . $leadId);
    }

    /**
     * One-click outbound reply (Email, SMS, WhatsApp).
     */
    public function reply(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $userId = $request->getAttribute('_user_id');
        $leadId = $request->getAttribute('id');
        $channel = $request->input('channel', 'email');
        $message = trim((string) $request->input('message', ''));
        $subject = $request->input('subject', 'Response from our team');

        $lead = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1 AND tenant_id = $2', [$leadId, $tenantId]);
        if (!$lead) {
            flash('error', 'Lead not found.');
            return Response::redirect('/leads');
        }

        $recipient = match ($channel) {
            'email' => $lead['email'],
            'sms', 'whatsapp' => $lead['phone'],
            default => null,
        };

        if (empty($recipient) || empty($message)) {
            flash('error', 'Recipient contact details or message content missing.');
            return Response::redirect('/leads/' . $leadId);
        }

        $result = match ($channel) {
            'email' => $this->messaging->sendEmail($tenantId, $leadId, $recipient, $subject, $message, $userId),
            'sms' => $this->messaging->sendSms($tenantId, $leadId, $recipient, $message, $userId),
            'whatsapp' => $this->messaging->sendWhatsApp($tenantId, $leadId, $recipient, $message, $userId),
            default => ['success' => false],
        };

        if ($result['success'] ?? false) {
            flash('success', ucfirst($channel) . ' message logged and dispatched.');
        } else {
            flash('error', $result['reason'] ?? 'Failed to send message.');
        }

        return Response::redirect('/leads/' . $leadId);
    }

    /**
     * Update lead stage.
     */
    public function updateStage(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $userId = $request->getAttribute('_user_id');
        $leadId = $request->getAttribute('id');
        $stageId = $request->input('stage_id');

        $res = $this->leadService->updateStage($leadId, $stageId, $userId);
        if (!$res['success']) {
            flash('error', $res['error']);
        } else {
            flash('success', 'Stage updated to ' . $res['stage_name']);
        }

        return Response::redirect('/leads/' . $leadId);
    }

    /**
     * Merge duplicate lead.
     */
    public function merge(Request $request): Response
    {
        $leadId = $request->getAttribute('id');
        $duplicateId = $request->input('duplicate_id');
        $userId = $request->getAttribute('_user_id');

        if ($this->leadService->mergeLeads($leadId, $duplicateId, $userId)) {
            flash('success', 'Duplicate lead successfully merged into this record.');
        } else {
            flash('error', 'Could not merge records.');
        }

        return Response::redirect('/leads/' . $leadId);
    }

    /**
     * Global Search (JSON).
     */
    public function search(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $query = $request->query('q', '');

        $results = $this->leadService->search($tenantId, $query, 8);

        $html = '';
        if (empty($results)) {
            $html = '<div style="padding:0.75rem 1rem;font-size:0.8125rem;color:var(--text-tertiary);">No matching leads or notes found.</div>';
        } else {
            foreach ($results as $r) {
                $html .= '<a href="/leads/' . e($r['id']) . '" class="search-item" style="text-decoration:none;">';
                $html .= '<div>';
                $html .= '<div style="font-weight:600;font-size:0.875rem;color:var(--text-primary);">' . e($r['name']) . '</div>';
                $html .= '<div style="font-size:0.75rem;color:var(--text-tertiary);">' . e($r['service'] ?: 'General Enquiry') . ' • ' . e($r['email'] ?: $r['phone']) . '</div>';
                $html .= '</div>';
                $html .= '<span class="badge badge-neutral">' . e($r['stage_name']) . '</span>';
                $html .= '</a>';
            }
        }

        return Response::json(['html' => $html, 'count' => count($results)]);
    }

    /**
     * CSV Export.
     */
    public function export(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $leads = $this->db->fetchAll(
            'SELECT l.id, l.name, l.email, l.phone, l.company, l.service, l.value,
                    l.priority, l.score, l.status, ps.name as stage_name, l.created_at
             FROM leads l
             LEFT JOIN pipeline_stages ps ON l.stage_id = ps.id
             WHERE l.tenant_id = $1
             ORDER BY l.created_at DESC',
            [$tenantId]
        );

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Company', 'Service', 'Value', 'Priority', 'Score', 'Status', 'Stage', 'Created At']);

        foreach ($leads as $l) {
            fputcsv($output, [
                $l['id'], $l['name'], $l['email'], $l['phone'], $l['company'],
                $l['service'], $l['value'], $l['priority'], $l['score'],
                $l['status'], $l['stage_name'], $l['created_at']
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="intakely_leads_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
