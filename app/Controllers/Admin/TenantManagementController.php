<?php
/**
 * Intakely — Super-Admin Tenant Management Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;

final class TenantManagementController
{
    private Database $db;
    private AuditService $audit;

    public function __construct(?Database $db = null, ?AuditService $audit = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->audit = $audit ?? new AuditService($this->db);
    }

    public function index(Request $request): Response
    {
        $tenants = $this->db->fetchAll('SELECT * FROM tenants ORDER BY created_at DESC');

        return Response::view('admin/tenants', [
            'title' => 'Tenant Directory — Super-Admin',
            'activeNav' => 'admin_tenants',
            'tenants' => $tenants,
        ]);
    }

    /**
     * Impersonate a tenant owner (strictly audited).
     */
    public function impersonate(Request $request): Response
    {
        $tenantId = $request->getAttribute('id');
        $tenant = $this->db->fetchOne('SELECT * FROM tenants WHERE id = $1', [$tenantId]);

        if (!$tenant) {
            flash('error', 'Tenant not found.');
            return Response::redirect('/admin/tenants');
        }

        // Find owner user for tenant
        $owner = $this->db->fetchOne(
            'SELECT * FROM users WHERE tenant_id = $1 AND role = \'owner\' ORDER BY created_at ASC LIMIT 1',
            [$tenantId]
        );

        if (!$owner) {
            $owner = $this->db->fetchOne(
                'SELECT * FROM users WHERE tenant_id = $1 ORDER BY created_at ASC LIMIT 1',
                [$tenantId]
            );
        }

        if (!$owner) {
            flash('error', 'No users exist for this tenant.');
            return Response::redirect('/admin/tenants');
        }

        // Audit log the impersonation
        $this->audit->log(
            action: 'super_admin_impersonate',
            resourceType: 'tenant',
            resourceId: $tenantId,
            tenantId: $tenantId,
            userId: $owner['id'],
            metadata: ['impersonated_by_admin' => true]
        );

        // Set session
        $_SESSION['_user_id'] = $owner['id'];
        $_SESSION['_user'] = [
            'id' => $owner['id'],
            'name' => $owner['name'],
            'email' => $owner['email'],
            'role' => $owner['role'],
            'tenant_id' => $tenantId,
        ];
        $_SESSION['_tenant'] = [
            'id' => $tenantId,
            'name' => $tenant['name'],
            'slug' => $tenant['slug'],
        ];

        flash('success', "Now impersonating {$owner['name']} ({$tenant['name']}). All actions are audited.");
        return Response::redirect('/pipeline');
    }

    /**
     * Plan Override.
     */
    public function updatePlan(Request $request): Response
    {
        $tenantId = $request->getAttribute('id');
        $newPlan = $request->input('plan', 'growth');

        $this->db->execute(
            'UPDATE tenants SET plan = $1, updated_at = NOW() WHERE id = $2',
            [$newPlan, $tenantId]
        );

        flash('success', "Tenant plan updated to {$newPlan}.");
        return Response::redirect('/admin/tenants');
    }
}
