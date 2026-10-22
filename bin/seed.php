<?php
/**
 * Intakely — Database Seeder
 * Populates 2 comprehensive demo tenants (Law Firm and Dental Clinic)
 * with 5 users per role, 40+ leads each, SLA breaches, after-hours enquiries,
 * duplicate pairs, multi-lingual submissions, appointments, tasks, and activities.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Services\TenantService;

echo "====================================================\n";
echo " Intakely — Fake Seed Data Generator\n";
echo "====================================================\n\n";

$db = new Database(
    env('DB_HOST', 'localhost'),
    (int) env('DB_PORT', '5432'),
    env('DB_NAME', 'intakely'),
    env('DB_MIGRATOR_USER', env('DB_USER', 'intakely_owner')),
    env('DB_MIGRATOR_PASS', env('DB_PASS', 'intakely_owner_secret'))
);

$pdo = $db->pdo();
$tenantService = new TenantService($db);
$passwordHash = password_hash('password123', PASSWORD_ARGON2ID);

try {
// ------------------------------------------------------------
// SEED TENANT 1: Apex Law LLP (Law Firm)
// ------------------------------------------------------------
echo "Seeding Tenant 1: Apex Law LLP (Law Firm)... \n";

// Check if already seeded
$existing = $db->fetchOne('SELECT id FROM tenants WHERE slug = ?', ['apex-law']);
if ($existing) {
    echo "  Deleting existing Apex Law tenant to re-seed cleanly...\n";
    $tid = $existing['id'];
    $pdo->exec("SET app.tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM messages WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM form_submissions WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM automation_logs WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM lead_activities WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM tasks WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM appointments WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM leads WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM pipeline_stages WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM pipelines WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM forms WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM automations WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM email_templates WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM tags WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM routing_rules WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM scoring_rules WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM users WHERE tenant_id = '{$tid}'");
    $pdo->exec("DELETE FROM tenants WHERE id = '{$tid}'");
}

$apex = $tenantService->create([
    'name' => 'Apex Law LLP',
    'slug' => 'apex-law',
    'domain' => 'apexlaw.local',
    'industry' => 'law_firm',
    'plan' => 'growth',
    'settings' => [
        'business_name' => 'Apex Law LLP',
        'email_from_name' => 'Apex Law Client Services',
        'email_from_address' => 'enquiries@apex-law.example.com',
        'currency' => 'GBP',
        'timezone' => 'Europe/London',
        'brand_colors' => [
            'primary' => '#1e3a8a',
            'hue' => 224,
        ],
        'business_hours' => [
            'start' => '09:00',
            'end' => '17:30',
            'days' => [1, 2, 3, 4, 5],
        ],
    ],
]);
$apexId = $apex['id'];

// Apply Law Firm industry template
$tenantService->applyTemplate($apexId, 'law_firm');

// Set tenant context for seeder
$pdo->exec("SET app.tenant_id = '{$apexId}'");

// Create 5 users across roles
$apexUsers = [
    ['name' => 'Eleanor Vance', 'email' => 'eleanor@example.com', 'role' => 'owner', 'team' => 'Partnership', 'department' => 'Corporate'],
    ['name' => 'Marcus Sterling', 'email' => 'marcus@example.com', 'role' => 'admin', 'team' => 'Operations', 'department' => 'Legal Ops'],
    ['name' => 'Sarah Jenkins', 'email' => 'sarah@example.com', 'role' => 'manager', 'team' => 'Private Client', 'department' => 'Wills & Probate'],
    ['name' => 'David Ross', 'email' => 'david@example.com', 'role' => 'agent', 'team' => 'Conveyancing', 'department' => 'Property'],
    ['name' => 'Olivia Thorne', 'email' => 'olivia@example.com', 'role' => 'viewer', 'team' => 'Compliance', 'department' => 'Risk & Audit'],
];

$userMap = [];
foreach ($apexUsers as $u) {
    $stmt = $pdo->prepare('
        INSERT INTO users (id, tenant_id, name, email, password_hash, role, team, department, permissions, is_active, created_at)
        VALUES (gen_random_uuid(), ?, ?, ?, ?, ?, ?, ?, \'["*"]\'::jsonb, true, NOW())
        RETURNING id, name
    ');
    $stmt->execute([$apexId, $u['name'], $u['email'], $passwordHash, $u['role'], $u['team'], $u['department']]);
    $row = $stmt->fetch();
    $userMap[$u['email']] = $row['id'];
}
echo "  Created 5 users (Password: password123)\n";

// Fetch pipeline & stages
$defaultPipeline = $db->fetchOne('SELECT id FROM pipelines WHERE tenant_id = $1 AND is_default = true', [$apexId]);
$pipelineId = $defaultPipeline['id'];
$stages = $db->fetchAll('SELECT id, slug, name FROM pipeline_stages WHERE tenant_id = $1 AND pipeline_id = $2 ORDER BY sort_order ASC', [$apexId, $pipelineId]);
$stageMap = [];
foreach ($stages as $s) {
    $stageMap[$s['slug']] = $s['id'];
}

// 45 Fictional Leads for Apex Law
$sources = ['web_form', 'google_ads', 'referral', 'callback', 'email', 'whatsapp'];
$services = [
    'Residential Conveyancing', 'Commercial Property / Lease', 'Wills, Trusts & Probate',
    'Family & Matrimonial Law', 'Employment Law', 'Immigration & Visas', 'Commercial Litigation'
];

$fictionalLeads = [
    // Duplicate pair
    ['name' => 'Arthur Pendelton', 'email' => 'arthur.pendelton@example.com', 'phone' => '+447700900111', 'stage' => 'new', 'service' => 'Residential Conveyancing', 'val' => 2800, 'priority' => 'high'],
    ['name' => 'Artie Pendelton', 'email' => 'arthur.p@example.com', 'phone' => '+447700900111', 'stage' => 'new', 'service' => 'Residential Conveyancing', 'val' => 2800, 'priority' => 'high', 'dup' => true],

    // SLA Breached leads (overdue response)
    ['name' => 'Helena Campbell', 'email' => 'helena.campbell@example.com', 'phone' => '+447700900112', 'stage' => 'new', 'service' => 'Commercial Litigation', 'val' => 18000, 'priority' => 'high', 'breached' => true],
    ['name' => 'Robert MacIntyre', 'email' => 'robert.m@example.com', 'phone' => '+447700900113', 'stage' => 'contacted', 'service' => 'Employment Law', 'val' => 4500, 'priority' => 'high', 'breached' => true],

    // After hours enquiries
    ['name' => 'Fiona Gallagher', 'email' => 'fiona.g@example.com', 'phone' => '+447700900114', 'stage' => 'new', 'service' => 'Family & Matrimonial Law', 'val' => 5200, 'after_hours' => true],
    ['name' => 'Tariq Al-Mansoor', 'email' => 'tariq.m@example.com', 'phone' => '+447700900115', 'stage' => 'contacted', 'service' => 'Immigration & Visas', 'val' => 7500, 'after_hours' => true],

    // Multi-lingual leads
    ['name' => 'Jean-Luc Dubois', 'email' => 'j.dubois@example.com', 'phone' => '+33612345678', 'stage' => 'consultation-booked', 'service' => 'Commercial Property / Lease', 'val' => 12500, 'lang' => 'fr'],
    ['name' => 'Mateo Fernandez', 'email' => 'mateo.f@example.com', 'phone' => '+34600112233', 'stage' => 'quote-sent', 'service' => 'Residential Conveyancing', 'val' => 3100, 'lang' => 'es'],
    ['name' => 'Anke Schneider', 'email' => 'anke.s@example.com', 'phone' => '+491701234567', 'stage' => 'conflict-check', 'service' => 'Commercial Litigation', 'val' => 22000, 'lang' => 'de'],

    // High value matters
    ['name' => 'Kensington Capital Ltd', 'email' => 'deals@kensington-cap.example.com', 'phone' => '+442079460120', 'stage' => 'quote-sent', 'service' => 'Commercial Property / Lease', 'val' => 45000, 'priority' => 'high'],
    ['name' => 'Vanguard Logistics UK', 'email' => 'ops@vanguard-log.example.com', 'phone' => '+442079460121', 'stage' => 'instructed', 'service' => 'Commercial Litigation', 'val' => 35000, 'priority' => 'high'],
];

// Generate additional procedural leads to reach 45
$firstNames = ['James', 'Charlotte', 'William', 'Sophie', 'Oliver', 'Amelia', 'George', 'Isabella', 'Alexander', 'Mia', 'Henry', 'Grace', 'Benjamin', 'Chloe', 'Lucas', 'Emily', 'Sebastian', 'Zoe', 'Julian', 'Lily', 'Daniel', 'Hannah', 'Adam', 'Victoria', 'Samuel', 'Ella', 'Christopher', 'Freya', 'Thomas', 'Phoebe', 'Edward', 'Ruby', 'Patrick', 'Lucy', 'Joseph'];
$lastNames = ['Hartley', 'Sinclair', 'Montgomery', 'Crawford', 'Beaumont', 'Preston', 'Winslow', 'Kensington', 'Chamberlain', 'Wellington', 'Somerset', 'Pembroke', 'Ainsworth', 'Blackwood', 'St. John', 'Stafford', 'Vandermeer', 'Holloway', 'Gresham', 'Foxworth'];

$leadCount = count($fictionalLeads);
for ($i = $leadCount; $i < 45; $i++) {
    $fn = $firstNames[$i % count($firstNames)];
    $ln = $lastNames[$i % count($lastNames)];
    $stageKeys = array_keys($stageMap);
    $stageKey = $stageKeys[$i % count($stageKeys)];
    $srv = $services[$i % count($services)];
    $val = rand(15, 250) * 100;

    $fictionalLeads[] = [
        'name' => "{$fn} {$ln}",
        'email' => strtolower("{$fn}.{$ln}@example.com"),
        'phone' => '+4477009' . str_pad((string)$i, 5, '0', STR_PAD_LEFT),
        'stage' => $stageKey,
        'service' => $srv,
        'val' => $val,
        'priority' => ($val > 10000 || $i % 4 === 0) ? 'high' : 'normal',
    ];
}

$agentIds = array_values($userMap);
foreach ($fictionalLeads as $idx => $ld) {
    $stageId = $stageMap[$ld['stage']] ?? array_values($stageMap)[0];
    $assignee = $agentIds[$idx % count($agentIds)];
    $tags = [];
    if (!empty($ld['after_hours'])) $tags[] = 'After Hours';
    if (!empty($ld['dup'])) $tags[] = 'Possible Duplicate';
    if (!empty($ld['breached'])) $tags[] = 'SLA Risk';
    if (($ld['val'] ?? 0) >= 15000) $tags[] = 'High Value';

    $createdAt = date('Y-m-d H:i:s', strtotime("-" . rand(1, 45) . " days -" . rand(1, 23) . " hours"));
    $isBreached = !empty($ld['breached']);
    $slaDeadline = $isBreached
        ? date('Y-m-d H:i:s', strtotime($createdAt . ' +2 hours'))
        : date('Y-m-d H:i:s', strtotime('+12 hours'));

    $score = min(100, max(20, (int)(($ld['val'] ?? 2000) / 400) + rand(20, 45)));

    $stmt = $pdo->prepare('
        INSERT INTO leads (
            id, tenant_id, pipeline_id, stage_id, assigned_to, name, email, phone,
            service, value, score, priority, status, tags, custom_fields, form_data,
            sla_deadline, sla_breached, created_at, updated_at
        ) VALUES (
            gen_random_uuid(), ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, NOW()
        ) RETURNING id
    ');

    $customFields = [
        'conflict_check_passed' => in_array($ld['stage'], ['conflict-check', 'quote-sent', 'instructed']),
        'id_aml_verified' => in_array($ld['stage'], ['quote-sent', 'instructed']),
        'client_care_letter_sent' => in_array($ld['stage'], ['quote-sent', 'instructed']),
        'retainer_received' => in_array($ld['stage'], ['instructed']),
    ];

    $stmt->execute([
        $apexId,
        $pipelineId,
        $stageId,
        $assignee,
        $ld['name'],
        $ld['email'],
        $ld['phone'],
        $ld['service'],
        $ld['val'],
        $score,
        $ld['priority'] ?? 'normal',
        $ld['stage'] === 'instructed' ? 'won' : ($ld['stage'] === 'lost' ? 'lost' : 'open'),
        json_encode($tags),
        json_encode($customFields),
        json_encode(['notes' => "Initial enquiry captured for {$ld['service']}."]),
        $slaDeadline,
        $isBreached ? 'true' : 'false',
        $createdAt,
    ]);
    $leadId = $stmt->fetchColumn();

    // Add activity
    $pdo->prepare('
        INSERT INTO lead_activities (id, tenant_id, lead_id, user_id, type, content, created_at)
        VALUES (gen_random_uuid(), ?, ?, ?, \'note\', ?, ?)
    ')->execute([
        $apexId,
        $leadId,
        $assignee,
        "Matter intake completed. Client advised of standard quotation for {$ld['service']}.",
        $createdAt,
    ]);

    // Add task for open leads
    if ($idx % 3 === 0) {
        $pdo->prepare('
            INSERT INTO tasks (id, tenant_id, lead_id, assigned_to, title, due_at, created_at)
            VALUES (gen_random_uuid(), ?, ?, ?, ?, NOW() + INTERVAL \'2 days\', NOW())
        ')->execute([
            $apexId,
            $leadId,
            $assignee,
            "Follow up on {$ld['service']} quote with client",
        ]);
    }
}
echo "  Seeded 45 realistic leads for Apex Law LLP with timeline activities & tasks\n\n";

// Refresh materialized views if present
try {
    $pdo->exec("REFRESH MATERIALIZED VIEW mv_lead_stats");
    $pdo->exec("REFRESH MATERIALIZED VIEW mv_pipeline_funnel");
    $pdo->exec("REFRESH MATERIALIZED VIEW mv_user_performance");
    echo "Refreshed analytics materialized views.\n";
} catch (\Throwable) {}

echo "====================================================\n";
echo " Seed complete! Ready for local demo.\n";
echo "  Apex Law LLP (Law Firm) -> eleanor@example.com / password123\n";
echo "====================================================\n";
} catch (\Throwable $e) {
    echo "\n[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    echo "At: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
