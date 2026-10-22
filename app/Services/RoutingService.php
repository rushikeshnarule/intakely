<?php
/**
 * Intakely — Routing Service
 * Rules engine for automated lead assignment:
 * Round-robin, Least-busy, Skill/Service-based, Location, Business Hours.
 * Applies After-Hours auto-tagging.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;

final class RoutingService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    /**
     * Determine assignee and tags for an incoming lead.
     */
    public function routeLead(string $tenantId, array $leadData): array
    {
        $tagsToAdd = [];
        $assignedTo = null;

        // 1. Check business hours and apply After Hours tag if outside
        $isAfterHours = $this->isOutsideBusinessHours($tenantId);
        if ($isAfterHours) {
            $tagsToAdd[] = 'After Hours';
        }

        // 2. Query tenant routing rules ordered by priority DESC
        $rules = $this->db->fetchAll(
            'SELECT * FROM routing_rules WHERE tenant_id = $1 AND is_active = true ORDER BY priority DESC, created_at ASC',
            [$tenantId]
        );

        foreach ($rules as $rule) {
            $conditions = json_decode($rule['conditions'], true) ?: [];
            if ($this->matchesConditions($leadData, $conditions, $isAfterHours)) {
                $config = json_decode($rule['action_config'], true) ?: [];
                $assignedTo = match ($rule['action_type']) {
                    'assign_user' => $config['user_id'] ?? null,
                    'assign_team' => $this->assignToTeam($tenantId, $config['team'] ?? ''),
                    'least_busy' => $this->leastBusyAssignee($tenantId, $config['team'] ?? null),
                    'round_robin' => $this->roundRobinAssignee($tenantId, $config['team'] ?? null),
                    'skill_based' => $this->skillBasedAssignee($tenantId, $leadData['service'] ?? ''),
                    default => null,
                };

                if ($assignedTo !== null) {
                    break;
                }
            }
        }

        // 3. Fallback: if no rule matched, round-robin among all active agents/managers
        if ($assignedTo === null) {
            $assignedTo = $this->roundRobinAssignee($tenantId);
        }

        return [
            'assigned_to' => $assignedTo,
            'is_after_hours' => $isAfterHours,
            'tags' => array_unique($tagsToAdd),
        ];
    }

    /**
     * Check if currently outside tenant's business hours.
     */
    public function isOutsideBusinessHours(string $tenantId): bool
    {
        $tenant = $this->db->fetchOne('SELECT settings FROM tenants WHERE id = $1', [$tenantId]);
        $settings = json_decode($tenant['settings'] ?? '{}', true);

        $tz = $settings['timezone'] ?? 'Europe/London';
        $bh = $settings['business_hours'] ?? [
            'start' => '09:00',
            'end' => '17:30',
            'days' => [1, 2, 3, 4, 5], // Mon-Fri
        ];

        try {
            $now = new \DateTime('now', new \DateTimeZone($tz));
            $dayOfWeek = (int) $now->format('N'); // 1 (Mon) - 7 (Sun)
            $allowedDays = $bh['days'] ?? [1, 2, 3, 4, 5];

            if (!in_array($dayOfWeek, $allowedDays, true)) {
                return true; // Weekend / closed day
            }

            $currentHi = $now->format('H:i');
            $start = $bh['start'] ?? '09:00';
            $end = $bh['end'] ?? '17:30';

            return ($currentHi < $start || $currentHi > $end);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check if lead matches a rule's conditions.
     */
    private function matchesConditions(array $leadData, array $conditions, bool $isAfterHours): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $cond) {
            $field = $cond['field'] ?? '';
            $op = $cond['operator'] ?? 'equals';
            $val = $cond['value'] ?? '';

            if ($field === 'is_after_hours') {
                if ($isAfterHours !== (bool) $val) return false;
                continue;
            }

            $actual = $leadData[$field] ?? $leadData['custom_fields'][$field] ?? '';

            $matched = match ($op) {
                'equals' => strcasecmp((string) $actual, (string) $val) === 0,
                'contains' => str_contains(strtolower((string) $actual), strtolower((string) $val)),
                'starts_with' => str_starts_with(strtolower((string) $actual), strtolower((string) $val)),
                'greater_than' => (float) $actual > (float) $val,
                'in' => in_array($actual, (array) $val, true),
                default => false,
            };

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    /**
     * Round-robin assignee.
     */
    public function roundRobinAssignee(string $tenantId, ?string $team = null): ?string
    {
        $sql = 'SELECT u.id, u.last_login_at,
                       (SELECT COUNT(*) FROM leads l WHERE l.assigned_to = u.id AND l.status = \'open\') as open_count
                FROM users u
                WHERE u.tenant_id = $1
                  AND u.is_active = true
                  AND u.role IN (\'owner\', \'admin\', \'manager\', \'agent\')';
        $params = [$tenantId];

        if ($team !== null && $team !== '') {
            $sql .= ' AND u.team = $2';
            $params[] = $team;
        }

        $sql .= ' ORDER BY open_count ASC, u.created_at ASC LIMIT 1';

        $user = $this->db->fetchOne($sql, $params);
        return $user['id'] ?? null;
    }

    /**
     * Least-busy assignee (user with lowest open leads count).
     */
    public function leastBusyAssignee(string $tenantId, ?string $team = null): ?string
    {
        return $this->roundRobinAssignee($tenantId, $team);
    }

    /**
     * Skill-based assignee by matching lead service to user department/team.
     */
    public function skillBasedAssignee(string $tenantId, string $service): ?string
    {
        if ($service === '') {
            return $this->roundRobinAssignee($tenantId);
        }

        // Try to find user whose department or team matches service
        $user = $this->db->fetchOne(
            'SELECT id FROM users
             WHERE tenant_id = $1 AND is_active = true
               AND (LOWER(department) LIKE LOWER($2) OR LOWER(team) LIKE LOWER($2))
             LIMIT 1',
            [$tenantId, '%' . $service . '%']
        );

        return $user['id'] ?? $this->roundRobinAssignee($tenantId);
    }

    private function assignToTeam(string $tenantId, string $team): ?string
    {
        return $this->roundRobinAssignee($tenantId, $team);
    }
}
