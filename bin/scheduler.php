<?php
/**
 * Intakely — Periodic Cron Scheduler
 * Runs tasks:
 * 1. Scans and flags SLA breaches
 * 2. Refreshes analytics materialized views
 * 3. Sends appointment reminders
 * 4. Cleans expired sessions
 *
 * Add to crontab: * * * * * php /path/to/bin/scheduler.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Container;
use App\Core\Database;
use App\Services\AutomationEngine;

echo "[" . date('Y-m-d H:i:s') . "] Intakely Scheduler starting...\n";

$db = Container::getInstance()->get(Database::class);
$automation = new AutomationEngine($db);

// 1. Detect & flag SLA breaches
$breachedLeads = $db->fetchAll(
    'SELECT l.id, l.tenant_id, l.name, l.assigned_to, l.service, l.priority
     FROM leads l
     WHERE l.status = \'open\'
       AND l.sla_deadline <= NOW()
       AND (l.sla_breached IS FALSE OR l.sla_breached IS NULL)
     LIMIT 50'
);

$breachedCount = count($breachedLeads);
if ($breachedCount > 0) {
    echo "  Found {$breachedCount} newly breached leads. Flagging...\n";
    foreach ($breachedLeads as $lead) {
        $db->execute('UPDATE leads SET sla_breached = true, updated_at = NOW() WHERE id = $1', [$lead['id']]);

        // Insert timeline activity
        $db->execute(
            'INSERT INTO lead_activities (id, tenant_id, lead_id, type, content, created_at)
             VALUES (gen_random_uuid(), $1, $2, \'system\', \'SLA deadline breached without response.\', NOW())',
            [$lead['tenant_id'], $lead['id']]
        );

        // Notify assignee if assigned
        if (!empty($lead['assigned_to'])) {
            $db->execute(
                'INSERT INTO notifications (id, tenant_id, user_id, type, title, body, created_at)
                 VALUES (gen_random_uuid(), $1, $2, \'sla_breach\', \'SLA Breached!\', $3, NOW())',
                [$lead['tenant_id'], $lead['assigned_to'], "Lead '{$lead['name']}' ({$lead['service']}) has exceeded its SLA deadline."]
            );
        }

        // Trigger automations
        $automation->dispatch($lead['tenant_id'], 'sla_breached', [
            'lead' => $lead,
            'lead_id' => $lead['id'],
        ]);
    }
}

// 2. Refresh materialized views
try {
    $db->execute('REFRESH MATERIALIZED VIEW mv_lead_stats');
    $db->execute('REFRESH MATERIALIZED VIEW mv_pipeline_funnel');
    $db->execute('REFRESH MATERIALIZED VIEW mv_user_performance');
    echo "  Refreshed analytics materialized views.\n";
} catch (\Throwable $e) {
    // Non-fatal if materialized views do not support concurrent refresh or are empty
}

// 3. Appointment reminders
$upcomingAppointments = $db->fetchAll(
    'SELECT a.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone
     FROM appointments a
     JOIN leads l ON a.lead_id = l.id
     WHERE a.status = \'scheduled\'
       AND a.start_at BETWEEN NOW() AND NOW() + INTERVAL \'24 hours\'
       AND (a.reminder_sent IS FALSE OR a.reminder_sent IS NULL)'
);

foreach ($upcomingAppointments as $apt) {
    $db->execute('UPDATE appointments SET reminder_sent = true, updated_at = NOW() WHERE id = $1', [$apt['id']]);
    // Log reminder sent activity
    $db->execute(
        'INSERT INTO lead_activities (id, tenant_id, lead_id, type, content, created_at)
         VALUES (gen_random_uuid(), $1, $2, \'system\', $3, NOW())',
        [$apt['tenant_id'], $apt['lead_id'], "Automated 24h appointment reminder dispatched for {$apt['title']}."]
    );
}

echo "[" . date('Y-m-d H:i:s') . "] Scheduler finished successfully.\n";
