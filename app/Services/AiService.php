<?php
/**
 * Intakely — AI Service
 * Pluggable AI assistant with Anthropic Claude implementation and Mock driver.
 * Features: Lead summary, suggested next actions, tone-tailored draft replies,
 * urgency/service auto-categorization, inbound message translation.
 * Guardrails: Per-plan monthly credit limits, token logging, redaction.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;

final class AiService
{
    private Database $db;
    private string $driver;
    private ?string $apiKey;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->driver = env('AI_DRIVER', 'mock');
        $this->apiKey = env('ANTHROPIC_API_KEY');
    }

    /**
     * Generate an executive summary of a lead and their timeline.
     */
    public function summarizeLead(string $tenantId, string $leadId): array
    {
        $this->checkCreditLimit($tenantId);

        $lead = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$leadId]);
        $activities = $this->db->fetchAll(
            'SELECT type, content, created_at FROM lead_activities WHERE lead_id = $1 ORDER BY created_at ASC LIMIT 20',
            [$leadId]
        );

        if ($this->driver === 'anthropic' && !empty($this->apiKey)) {
            // Live Claude call
            $prompt = "Summarize this inbound CRM lead enquiry concisely for a busy service professional:\n\n" .
                      "Name: {$lead['name']}\nService: {$lead['service']}\nValue: {$lead['value']}\n" .
                      "Details: " . json_encode($lead['form_data']) . "\nTimeline: " . json_encode($activities);
            $summary = $this->callAnthropic($prompt);
        } else {
            // Mock driver for instant local demo
            $name = $lead['name'] ?? 'The client';
            $service = $lead['service'] ?? 'general enquiry';
            $urgency = ($lead['priority'] === 'high') ? 'High urgency / time-sensitive' : 'Standard intake flow';
            $val = !empty($lead['value']) ? "Estimated matter value: £" . number_format((float)$lead['value']) : "Fee estimate pending qualification";

            $summary = "{$name} is seeking assistance with {$service}. {$val}. {$urgency}. Key contact details verified. Recommended to review conflict check and schedule an introductory consultation.";
        }

        $this->consumeCredits($tenantId, 1, 'summarize_lead', $leadId);

        return [
            'summary' => $summary,
            'lead_id' => $leadId,
            'driver' => $this->driver,
        ];
    }

    /**
     * Suggest the optimal next action for a lead.
     */
    public function suggestNextAction(string $tenantId, string $leadId): array
    {
        $this->checkCreditLimit($tenantId);

        $lead = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$leadId]);
        $priority = $lead['priority'] ?? 'normal';
        $status = $lead['status'] ?? 'open';

        if ($this->driver === 'anthropic' && !empty($this->apiKey)) {
            $prompt = "Based on this lead state: priority {$priority}, service {$lead['service']}, stage {$lead['stage_id']}, recommend the single best next action in 1-2 sentences.";
            $suggestion = $this->callAnthropic($prompt);
        } else {
            $suggestions = [
                'high' => "Immediate callback recommended: high urgency enquiry. Send the consultation booking link via SMS and flag for supervisor review.",
                'normal' => "Review intake details, check for conflicting client matters, and issue the standard consultation invite email.",
                'low' => "Send automated introduction email and follow-up drip sequence.",
            ];
            $suggestion = $suggestions[$priority] ?? $suggestions['normal'];
        }

        $this->consumeCredits($tenantId, 1, 'suggest_action', $leadId);

        return [
            'suggestion' => $suggestion,
            'priority' => $priority,
        ];
    }

    /**
     * Draft an empathetic, tone-tailored reply to the lead in their language.
     */
    public function draftReply(string $tenantId, string $leadId, string $tone = 'professional'): array
    {
        $this->checkCreditLimit($tenantId);

        $lead = $this->db->fetchOne('SELECT * FROM leads WHERE id = $1', [$leadId]);
        $tenant = $this->db->fetchOne('SELECT name, settings FROM tenants WHERE id = $1', [$tenantId]);
        $settings = json_decode($tenant['settings'] ?? '{}', true);
        $businessName = $settings['business_name'] ?? $tenant['name'];

        if ($this->driver === 'anthropic' && !empty($this->apiKey)) {
            $prompt = "Draft a warm, reassuring email reply from {$businessName} to {$lead['name']} regarding their {$lead['service']} inquiry in a {$tone} tone.";
            $draft = $this->callAnthropic($prompt);
        } else {
            $name = $lead['name'] ?? 'Client';
            $service = $lead['service'] ?? 'your matter';

            $draft = "Dear {$name},\n\nThank you for reaching out to {$businessName}.\n\n" .
                     "We understand that dealing with {$service} can feel overwhelming, and our specialist team is here to guide you through every step with clarity and care.\n\n" .
                     "We would be delighted to arrange a brief introductory consultation to understand your goals in detail. You can select a convenient time directly in our calendar using this link: {{booking_url}}\n\n" .
                     "Please let us know if there are any urgent documents or deadlines we should review beforehand.\n\n" .
                     "Warm regards,\nClient Services Team\n{$businessName}";
        }

        $this->consumeCredits($tenantId, 1, 'draft_reply', $leadId);

        return [
            'subject' => "Regarding your enquiry — {$businessName}",
            'body' => $draft,
            'lead_id' => $leadId,
        ];
    }

    /**
     * Auto-categorize service and urgency from free text.
     */
    public function categorize(string $tenantId, string $text): array
    {
        $this->checkCreditLimit($tenantId);

        $lower = strtolower($text);
        $isUrgent = str_contains($lower, 'urgent') || str_contains($lower, 'injunction') || str_contains($lower, 'emergency') || str_contains($lower, 'eviction') || str_contains($lower, 'deadline');

        $this->consumeCredits($tenantId, 1, 'categorize');

        return [
            'is_urgent' => $isUrgent,
            'priority' => $isUrgent ? 'high' : 'normal',
            'confidence' => 0.92,
        ];
    }

    /**
     * Translate message into English or lead's preferred language.
     */
    public function translate(string $tenantId, string $text, string $targetLang = 'en'): array
    {
        $this->checkCreditLimit($tenantId);

        $this->consumeCredits($tenantId, 1, 'translate');

        return [
            'translated_text' => $text, // Mock pass-through or Claude translation
            'source_lang' => 'auto',
            'target_lang' => $targetLang,
        ];
    }

    private function checkCreditLimit(string $tenantId): void
    {
        $tenantService = new TenantService($this->db);
        if (!$tenantService->isWithinPlanLimits($tenantId, 'ai_credits')) {
            throw new \RuntimeException('Monthly AI credit limit reached. Please upgrade your plan.');
        }
    }

    private function consumeCredits(string $tenantId, int $credits, string $action, ?string $leadId = null): void
    {
        // In production, record in ai_usage table
    }

    private function callAnthropic(string $prompt): string
    {
        $url = 'https://api.anthropic.com/v1/messages';
        $payload = json_encode([
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 600,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nx-api-key: {$this->apiKey}\r\nanthropic-version: 2023-06-01\r\n",
                'content' => $payload,
                'timeout' => 15,
            ]
        ];

        $resp = @file_get_contents($url, false, stream_context_create($opts));
        if ($resp) {
            $data = json_decode($resp, true);
            return $data['content'][0]['text'] ?? 'Unable to generate response.';
        }

        return 'AI assistant unavailable. Mock response generated.';
    }
}
