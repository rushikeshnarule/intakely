<?php
/**
 * Intakely — Scoring & SLA Service
 * Calculates lead score (0-100), sets priority (High/Normal/Low),
 * and computes SLA deadlines.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;

final class ScoringService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    /**
     * Compute lead score (0-100) based on signals.
     */
    public function computeScore(string $tenantId, array $leadData): array
    {
        $score = 20; // Base score
        $signals = [];

        // 1. Source quality signal
        $sourceScores = [
            'referral' => 25,
            'google_ads' => 20,
            'web_form' => 15,
            'callback' => 25,
            'whatsapp' => 20,
            'facebook' => 10,
            'instagram' => 10,
            'manual' => 15,
            'email' => 15,
            'csv_import' => 5,
        ];
        $source = $leadData['source'] ?? 'web_form';
        $sourceBoost = $sourceScores[$source] ?? 10;
        $score += $sourceBoost;
        $signals['source'] = $sourceBoost;

        // 2. Value / Budget signal
        $value = (float) ($leadData['value'] ?? 0);
        if ($value >= 10000) {
            $score += 25;
            $signals['value'] = 25;
        } elseif ($value >= 3000) {
            $score += 15;
            $signals['value'] = 15;
        } elseif ($value > 0) {
            $score += 10;
            $signals['value'] = 10;
        }

        // 3. Contact completeness signal
        if (!empty($leadData['email']) && !empty($leadData['phone'])) {
            $score += 15;
            $signals['contact_complete'] = 15;
        } elseif (!empty($leadData['phone'])) {
            $score += 10;
            $signals['phone_provided'] = 10;
        }

        // 4. Urgency keywords check
        $tenant = $this->db->fetchOne('SELECT settings FROM tenants WHERE id = $1', [$tenantId]);
        $settings = json_decode($tenant['settings'] ?? '{}', true);
        $urgencyKeywords = $settings['urgency_keywords'] ?? [
            'urgent', 'emergency', 'asap', 'immediately', 'deadline', 'hearing', 'court', 'injunction', 'eviction'
        ];

        $contentToScan = strtolower(
            ($leadData['name'] ?? '') . ' ' .
            ($leadData['service'] ?? '') . ' ' .
            ($leadData['source_detail'] ?? '') . ' ' .
            json_encode($leadData['custom_fields'] ?? []) . ' ' .
            json_encode($leadData['form_data'] ?? [])
        );

        $hasUrgency = false;
        foreach ($urgencyKeywords as $keyword) {
            if (str_contains($contentToScan, strtolower($keyword))) {
                $hasUrgency = true;
                $score += 25;
                $signals['urgency_keyword'] = $keyword;
                break;
            }
        }

        // 5. Custom tenant scoring rules from database
        $rules = $this->db->fetchAll(
            'SELECT * FROM scoring_rules WHERE tenant_id = $1 AND is_active = true',
            [$tenantId]
        );
        foreach ($rules as $rule) {
            $field = $rule['field'];
            $val = $leadData[$field] ?? $leadData['custom_fields'][$field] ?? null;
            if ($val !== null && strcasecmp((string) $val, (string) $rule['value']) === 0) {
                $score += (int) $rule['points'];
                $signals['rule_' . $rule['name']] = (int) $rule['points'];
            }
        }

        // Cap score between 0 and 100
        $finalScore = max(0, min(100, $score));

        // Priority calculation
        $priority = match (true) {
            $finalScore >= 70 || $hasUrgency => 'high',
            $finalScore >= 40 => 'normal',
            default => 'low',
        };

        return [
            'score' => $finalScore,
            'priority' => $priority,
            'has_urgency' => $hasUrgency,
            'signals' => $signals,
        ];
    }

    /**
     * Calculate SLA deadline.
     * High priority = 2 hours, Normal = 24 hours, Low = 48 hours (or stage SLA hours).
     */
    public function computeSlaDeadline(string $priority, ?int $stageSlaHours = null): string
    {
        $hours = $stageSlaHours ?: match ($priority) {
            'high' => 2,
            'normal' => 24,
            'low' => 48,
            default => 24,
        };

        return date('Y-m-d H:i:s', time() + ($hours * 3600));
    }
}
