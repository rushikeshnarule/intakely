<?php
/**
 * Intakely — Tenant Service
 * Tenant lifecycle management, industry template onboarding, settings, and plan limits.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;
use RuntimeException;

final class TenantService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    /**
     * Create a new tenant record (unscoped, platform-level).
     */
    public function create(array $data): array
    {
        $settings = array_merge([
            'business_name' => $data['name'],
            'logo_url' => null,
            'brand_colors' => [
                'primary' => '#3b82f6',
                'hue' => 220,
            ],
            'timezone' => 'Europe/London',
            'business_hours' => [
                'start' => '09:00',
                'end' => '17:30',
                'days' => [1, 2, 3, 4, 5],
            ],
            'locale' => 'en-GB',
            'currency' => 'GBP',
            'email_from_name' => $data['name'],
            'email_from_address' => 'hello@' . $data['slug'] . '.intakely.com',
            'privacy_url' => '/privacy',
            'terms_url' => '/terms',
        ], $data['settings'] ?? []);

        $planLimits = array_merge([
            'users' => 5,
            'leads_per_month' => 500,
            'automations' => 10,
            'ai_credits' => 100,
            'custom_domain' => false,
            'white_label' => false,
            'api_access' => true,
        ], $data['plan_limits'] ?? []);

        $stmt = $this->db->query(
            'INSERT INTO tenants (
                id, name, slug, domain, industry, settings, plan, plan_limits,
                trial_ends_at, is_active, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), $1, $2, $3, $4, $5, $6, $7,
                $8, true, NOW(), NOW()
            ) RETURNING *',
            [
                $data['name'],
                $data['slug'],
                $data['domain'] ?? null,
                $data['industry'] ?? 'blank',
                json_encode($settings),
                $data['plan'] ?? 'trial',
                json_encode($planLimits),
                $data['trial_ends_at'] ?? date('Y-m-d H:i:s', strtotime('+14 days')),
            ]
        );

        return $stmt->fetch();
    }

    public function find(string $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM tenants WHERE id = $1', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetchOne('SELECT * FROM tenants WHERE slug = $1', [$slug]);
    }

    public function findByDomain(string $domain): ?array
    {
        return $this->db->fetchOne('SELECT * FROM tenants WHERE domain = $1', [$domain]);
    }

    /**
     * Apply an industry template to an existing tenant.
     */
    public function applyTemplate(string $tenantId, string $industrySlug): void
    {
        $templatePath = BASE_PATH . '/database/templates/' . $industrySlug . '.json';
        if (!file_exists($templatePath)) {
            $templatePath = BASE_PATH . '/database/templates/blank.json';
        }

        $template = json_decode(file_get_contents($templatePath), true);
        if (!$template) {
            return;
        }

        // 1. Pipelines and Stages
        foreach ($template['pipelines'] ?? [] as $pIndex => $pipelineDef) {
            $pStmt = $this->db->query(
                'INSERT INTO pipelines (id, tenant_id, name, slug, description, is_default, sort_order, settings, created_at, updated_at)
                 VALUES (gen_random_uuid(), $1, $2, $3, $4, $5, $6, $7, NOW(), NOW())
                 RETURNING id',
                [
                    $tenantId,
                    $pipelineDef['name'],
                    $pipelineDef['slug'],
                    $pipelineDef['description'] ?? '',
                    $pipelineDef['is_default'] ?? ($pIndex === 0),
                    $pIndex + 1,
                    json_encode(['services' => $template['services'] ?? []]),
                ]
            );
            $pipelineId = $pStmt->fetchColumn();

            foreach ($pipelineDef['stages'] ?? [] as $sIndex => $stageDef) {
                $this->db->execute(
                    'INSERT INTO pipeline_stages (
                        id, tenant_id, pipeline_id, name, slug, sort_order, color,
                        is_won, is_lost, required_fields, sla_hours, created_at, updated_at
                    ) VALUES (
                        gen_random_uuid(), $1, $2, $3, $4, $5, $6,
                        $7, $8, $9, $10, NOW(), NOW()
                    )',
                    [
                        $tenantId,
                        $pipelineId,
                        $stageDef['name'],
                        $stageDef['slug'],
                        $stageDef['sort_order'] ?? ($sIndex + 1),
                        $stageDef['color'] ?? '#3b82f6',
                        $stageDef['is_won'] ?? false,
                        $stageDef['is_lost'] ?? false,
                        json_encode($stageDef['required_fields'] ?? []),
                        $stageDef['sla_hours'] ?? 24,
                    ]
                );
            }
        }

        // 2. Tags
        foreach ($template['tags'] ?? [] as $tag) {
            $this->db->execute(
                'INSERT INTO tags (id, tenant_id, name, color, created_at)
                 VALUES (gen_random_uuid(), $1, $2, $3, NOW())
                 ON CONFLICT (tenant_id, name) DO NOTHING',
                [$tenantId, $tag['name'], $tag['color']]
            );
        }

        // 3. Email Templates
        foreach ($template['templates'] ?? [] as $t) {
            $this->db->execute(
                'INSERT INTO email_templates (
                    id, tenant_id, name, slug, subject, body, channel, category, is_system, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), $1, $2, $3, $4, $5, $6, \'general\', true, NOW(), NOW()
                )',
                [
                    $tenantId,
                    $t['name'],
                    $t['slug'],
                    $t['subject'] ?? '',
                    $t['body'] ?? '',
                    $t['channel'] ?? 'email',
                ]
            );
        }

        // 4. Forms
        foreach ($template['forms'] ?? [] as $formDef) {
            $this->db->execute(
                'INSERT INTO forms (
                    id, tenant_id, name, slug, description, fields, settings, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), $1, $2, $3, $4, $5, $6, true, NOW(), NOW()
                )',
                [
                    $tenantId,
                    $formDef['name'],
                    $formDef['slug'],
                    $formDef['description'] ?? '',
                    json_encode($formDef['fields'] ?? []),
                    json_encode([
                        'success_message' => 'Thank you! Your enquiry has been received and our team will be in touch shortly.',
                        'honeypot_enabled' => true,
                        'rate_limit' => 10,
                    ]),
                ]
            );
        }

        // 5. Automations
        foreach ($template['automations'] ?? [] as $autoDef) {
            $this->db->execute(
                'INSERT INTO automations (
                    id, tenant_id, name, trigger_type, conditions, actions, is_active, created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), $1, $2, $3, $4, $5, true, NOW(), NOW()
                )',
                [
                    $tenantId,
                    $autoDef['name'],
                    $autoDef['trigger_type'],
                    json_encode($autoDef['conditions'] ?? []),
                    json_encode($autoDef['actions'] ?? []),
                ]
            );
        }

        // 6. Update tenant industry and urgency keywords
        $tenant = $this->find($tenantId);
        $settings = json_decode($tenant['settings'] ?? '{}', true);
        $settings['urgency_keywords'] = $template['urgency_keywords'] ?? [];
        $settings['services'] = $template['services'] ?? [];

        $this->db->execute(
            'UPDATE tenants SET industry = $1, settings = $2, updated_at = NOW() WHERE id = $3',
            [$industrySlug, json_encode($settings), $tenantId]
        );
    }

    /**
     * Check if a tenant has exceeded their plan limits.
     */
    public function isWithinPlanLimits(string $tenantId, string $resource): bool
    {
        $tenant = $this->find($tenantId);
        if (!$tenant) return false;

        $limits = json_decode($tenant['plan_limits'] ?? '{}', true);
        $max = $limits[$resource] ?? null;

        if ($max === null || $max === -1) {
            return true; // unlimited
        }

        return match ($resource) {
            'users' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM users WHERE tenant_id = $1 AND is_active = true', [$tenantId]) < $max,
            'leads_per_month' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM leads WHERE tenant_id = $1 AND created_at >= date_trunc(\'month\', NOW())', [$tenantId]) < $max,
            'automations' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM automations WHERE tenant_id = $1 AND is_active = true', [$tenantId]) < $max,
            default => true,
        };
    }
}
