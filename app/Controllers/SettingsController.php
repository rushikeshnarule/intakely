<?php
/**
 * Intakely — Tenant Settings, Team & Compliance Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;
use App\Services\ComplianceService;

final class SettingsController
{
    private Database $db;
    private AuditService $audit;
    private ComplianceService $compliance;

    public function __construct(
        ?Database $db = null,
        ?AuditService $audit = null,
        ?ComplianceService $compliance = null
    ) {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->audit = $audit ?? new AuditService($this->db);
        $this->compliance = $compliance ?? new ComplianceService($this->db);
    }

    /**
     * General Practice Settings.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $tenant = $this->db->fetchOne('SELECT * FROM tenants WHERE id = $1', [$tenantId]);
        $settings = json_decode($tenant['settings'] ?? '{}', true);

        return Response::view('settings/index', [
            'title' => 'General Settings — Intakely',
            'activeNav' => 'settings',
            'tenant' => $tenant,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $tenant = $this->db->fetchOne('SELECT * FROM tenants WHERE id = $1', [$tenantId]);
        $settings = json_decode($tenant['settings'] ?? '{}', true);

        $settings['business_name'] = $request->input('business_name', $tenant['name']);
        $settings['email_from_name'] = $request->input('email_from_name', $tenant['name']);
        $settings['email_from_address'] = $request->input('email_from_address', '');
        $settings['timezone'] = $request->input('timezone', 'Europe/London');
        $settings['currency'] = $request->input('currency', 'GBP');

        $this->db->execute(
            'UPDATE tenants SET name = $1, settings = $2, updated_at = NOW() WHERE id = $3',
            [$settings['business_name'], json_encode($settings), $tenantId]
        );

        flash('success', 'Practice settings updated.');
        return Response::redirect('/settings');
    }

    /**
     * Team Members Management.
     */
    public function team(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        $users = $this->db->fetchAll(
            'SELECT * FROM users WHERE tenant_id = $1 ORDER BY created_at ASC',
            [$tenantId]
        );

        return Response::view('settings/team', [
            'title' => 'Team & Roles — Intakely',
            'activeNav' => 'settings',
            'users' => $users,
        ]);
    }

    public function invite(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $name = trim((string) $request->input('name', ''));
        $email = trim(strtolower((string) $request->input('email', '')));
        $role = $request->input('role', 'agent');
        $team = $request->input('team', 'General');
        $department = $request->input('department', 'Intake');

        if (empty($name) || empty($email)) {
            flash('error', 'Name and email are required.');
            return Response::redirect('/settings/team');
        }

        $passwordHash = password_hash('password123', PASSWORD_ARGON2ID);

        $this->db->execute(
            'INSERT INTO users (id, tenant_id, name, email, password_hash, role, team, department, permissions, is_active, created_at, updated_at)
             VALUES (gen_random_uuid(), $1, $2, $3, $4, $5, $6, $7, \'["*"]\'::jsonb, true, NOW(), NOW())',
            [$tenantId, $name, $email, $passwordHash, $role, $team, $department]
        );

        flash('success', "Team member {$name} invited. Initial password: password123");
        return Response::redirect('/settings/team');
    }

    /**
     * Compliance & Immutable Audit Log.
     */
    public function compliance(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        $logs = $this->audit->getLogs($tenantId, 50);

        return Response::view('settings/compliance', [
            'title' => 'Compliance & Audit Trail — Intakely',
            'activeNav' => 'settings',
            'logs' => $logs,
        ]);
    }

    /**
     * One-click DSAR export.
     */
    public function export(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $identifier = trim((string) $request->input('identifier', ''));

        if (empty($identifier)) {
            flash('error', 'Please provide an email or phone number to export.');
            return Response::redirect('/settings/compliance');
        }

        $data = $this->compliance->generateDsarExport($tenantId, $identifier, $request->getAttribute('_user_id'));

        return Response::json($data);
    }

    /**
     * Right to Erasure / Anonymization.
     */
    public function erasure(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $leadId = $request->input('lead_id');

        if ($this->compliance->anonymiseLead($leadId, $request->getAttribute('_user_id'))) {
            flash('success', 'Lead record has been irreversibly anonymized in compliance with GDPR.');
        } else {
            flash('error', 'Could not locate record to anonymize.');
        }

        return Response::redirect('/settings/compliance');
    }
}
