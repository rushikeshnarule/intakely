<?php
/**
 * Intakely — Billing & Subscription Service
 * Config-driven SaaS plans (Starter, Growth, Scale), usage limit enforcement,
 * Stripe Checkout, Customer Portal, and FakeBillingProvider for offline local demos.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;

final class BillingService
{
    private Database $db;
    private string $driver;

    public const PLANS = [
        'starter' => [
            'name' => 'Starter',
            'price_monthly' => 49,
            'currency' => 'GBP',
            'limits' => [
                'users' => 3,
                'leads_per_month' => 250,
                'automations' => 5,
                'ai_credits' => 100,
                'custom_domain' => false,
                'white_label' => false,
                'api_access' => false,
            ],
            'features' => [
                '1 Pipeline',
                'Core Lead Capture Forms',
                'Email & SMS Notifications',
                'Basic Analytics',
                'Audit Trail (30 Days)',
            ],
        ],
        'growth' => [
            'name' => 'Growth',
            'price_monthly' => 149,
            'currency' => 'GBP',
            'popular' => true,
            'limits' => [
                'users' => 10,
                'leads_per_month' => 1500,
                'automations' => 25,
                'ai_credits' => 500,
                'custom_domain' => true,
                'white_label' => false,
                'api_access' => true,
            ],
            'features' => [
                'Unlimited Pipelines',
                'Full Automation Builder',
                'Smart Routing & SLAs',
                'AI Lead Summaries & Drafts',
                'Appointment Scheduling',
                'REST API & Webhooks',
            ],
        ],
        'scale' => [
            'name' => 'Scale',
            'price_monthly' => 299,
            'currency' => 'GBP',
            'limits' => [
                'users' => 50,
                'leads_per_month' => 10000,
                'automations' => -1, // Unlimited
                'ai_credits' => 2000,
                'custom_domain' => true,
                'white_label' => true,
                'api_access' => true,
            ],
            'features' => [
                'Everything in Growth',
                'Complete White-Labeling',
                'Custom Field Encryption',
                'Priority 24/7 SLA Support',
                'Unlimited Automations',
                'Dedicated Account Manager',
            ],
        ],
    ];

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->driver = env('BILLING_DRIVER', 'fake');
    }

    /**
     * Create a checkout session (or instantly upgrade in fake mode).
     */
    public function createCheckoutSession(string $tenantId, string $planKey): array
    {
        $plan = self::PLANS[$planKey] ?? self::PLANS['growth'];

        if ($this->driver === 'stripe') {
            // Live Stripe Checkout Session API stub
            return [
                'url' => 'https://checkout.stripe.com/pay/cs_test_' . bin2hex(random_bytes(10)),
                'plan' => $planKey,
            ];
        }

        // Fake provider: instantly upgrade the tenant plan for demo purposes
        $this->upgradeTenantPlan($tenantId, $planKey);

        return [
            'url' => '/settings/billing?upgraded=' . $planKey,
            'success' => true,
            'message' => 'Upgraded successfully to ' . $plan['name'] . ' plan.',
        ];
    }

    /**
     * Create a Stripe customer portal session URL.
     */
    public function createPortalSession(string $tenantId): string
    {
        if ($this->driver === 'stripe') {
            return 'https://billing.stripe.com/p/session_test_' . bin2hex(random_bytes(10));
        }

        return '/settings/billing';
    }

    /**
     * Upgrade tenant to a new plan.
     */
    public function upgradeTenantPlan(string $tenantId, string $planKey): void
    {
        $plan = self::PLANS[$planKey] ?? self::PLANS['growth'];

        $this->db->execute(
            'UPDATE tenants
             SET plan = $1,
                 plan_limits = $2,
                 trial_ends_at = NULL,
                 updated_at = NOW()
             WHERE id = $3',
            [
                $planKey,
                json_encode($plan['limits']),
                $tenantId,
            ]
        );
    }

    /**
     * Handle incoming Stripe webhooks.
     */
    public function handleWebhook(array $payload, ?string $signature = null): array
    {
        $type = $payload['type'] ?? '';

        switch ($type) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $sub = $payload['data']['object'] ?? [];
                $customerId = $sub['customer'] ?? null;
                $status = $sub['status'] ?? 'active';

                if ($customerId) {
                    $this->db->execute(
                        'UPDATE tenants SET billing_subscription_id = $1, is_active = ($2 = \'active\') WHERE billing_customer_id = $3',
                        [$sub['id'] ?? null, $status, $customerId]
                    );
                }
                return ['handled' => true, 'type' => $type];

            case 'customer.subscription.deleted':
                $sub = $payload['data']['object'] ?? [];
                $customerId = $sub['customer'] ?? null;
                if ($customerId) {
                    $this->db->execute(
                        'UPDATE tenants SET plan = \'trial\', is_active = false WHERE billing_customer_id = $1',
                        [$customerId]
                    );
                }
                return ['handled' => true, 'type' => $type];

            case 'invoice.payment_failed':
                // In production: trigger dunning email automation
                return ['handled' => true, 'type' => $type, 'dunning' => true];

            default:
                return ['handled' => false, 'type' => $type];
        }
    }
}
