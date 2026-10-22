<?php
/**
 * Intakely — Billing & Subscriptions Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\BillingService;

final class BillingController
{
    private Database $db;
    private BillingService $billing;

    public function __construct(?Database $db = null, ?BillingService $billing = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->billing = $billing ?? new BillingService($this->db);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $tenant = $this->db->fetchOne('SELECT * FROM tenants WHERE id = $1', [$tenantId]);

        $currentPlan = $tenant['plan'] ?? 'trial';
        $limits = json_decode($tenant['plan_limits'] ?? '{}', true);

        // Current usage
        $userCount = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM users WHERE tenant_id = $1 AND is_active = true', [$tenantId]);
        $leadCountMonth = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM leads WHERE tenant_id = $1 AND created_at >= date_trunc(\'month\', NOW())', [$tenantId]);
        $autoCount = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM automations WHERE tenant_id = $1 AND is_active = true', [$tenantId]);

        return Response::view('settings/billing', [
            'title' => 'Billing & Subscription — Intakely',
            'activeNav' => 'settings',
            'tenant' => $tenant,
            'currentPlan' => $currentPlan,
            'plans' => BillingService::PLANS,
            'limits' => $limits,
            'usage' => [
                'users' => $userCount,
                'leads_month' => $leadCountMonth,
                'automations' => $autoCount,
            ],
            'upgraded' => $request->query('upgraded'),
        ]);
    }

    public function checkout(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $plan = $request->input('plan', 'growth');

        $session = $this->billing->createCheckoutSession($tenantId, $plan);

        return Response::redirect($session['url']);
    }

    public function portal(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $url = $this->billing->createPortalSession($tenantId);

        return Response::redirect($url);
    }
}
