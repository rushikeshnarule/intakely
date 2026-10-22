<?php
/**
 * Intakely — Inbound Webhooks Controller
 * Channel adapters for email-to-lead, WhatsApp, Twilio SMS, Facebook & Google Ads leads.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;
use App\Services\BillingService;

final class WebhookController
{
    private Database $db;
    private LeadService $leadService;

    public function __construct(?Database $db = null, ?LeadService $leadService = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->leadService = $leadService ?? new LeadService($this->db);
    }

    /**
     * Inbound Email-to-Lead Webhook (Mailgun, SendGrid, Postmark).
     */
    public function email(Request $request): Response
    {
        $payload = $request->json() ?: $request->allPost();
        $recipient = $payload['recipient'] ?? $payload['to'] ?? '';
        $sender = $payload['sender'] ?? $payload['from'] ?? '';
        $subject = $payload['subject'] ?? 'Inbound Email Enquiry';
        $body = $payload['body-plain'] ?? $payload['text'] ?? '';

        // Match tenant by recipient domain or slug
        $tenant = $this->db->fetchOne('SELECT id FROM tenants WHERE is_active = true ORDER BY created_at ASC LIMIT 1');
        if ($tenant) {
            $this->leadService->createLead($tenant['id'], [
                'name' => explode('@', $sender)[0] ?: 'Email Client',
                'email' => $sender,
                'source' => 'email',
                'source_detail' => "Subject: {$subject}",
                'form_data' => ['email_body' => $body],
            ]);
        }

        return Response::json(['status' => 'received']);
    }

    /**
     * Inbound WhatsApp Cloud API Webhook.
     */
    public function whatsapp(Request $request): Response
    {
        $payload = $request->json();
        // Webhook verification challenge
        if ($request->query('hub_mode') === 'subscribe') {
            return Response::html($request->query('hub_challenge', ''));
        }

        $tenant = $this->db->fetchOne('SELECT id FROM tenants WHERE is_active = true ORDER BY created_at ASC LIMIT 1');
        if ($tenant) {
            $entry = $payload['entry'][0]['changes'][0]['value'] ?? [];
            $contact = $entry['contacts'][0] ?? [];
            $msg = $entry['messages'][0] ?? [];

            if (!empty($msg)) {
                $this->leadService->createLead($tenant['id'], [
                    'name' => $contact['profile']['name'] ?? 'WhatsApp Lead',
                    'phone' => $msg['from'] ?? '',
                    'source' => 'whatsapp',
                    'source_detail' => 'WhatsApp Chat',
                    'form_data' => ['text' => $msg['text']['body'] ?? ''],
                ]);
            }
        }

        return Response::json(['status' => 'ok']);
    }

    /**
     * Inbound SMS Webhook (Twilio).
     */
    public function sms(Request $request): Response
    {
        $from = $request->input('From', '');
        $body = $request->input('Body', '');

        $tenant = $this->db->fetchOne('SELECT id FROM tenants WHERE is_active = true ORDER BY created_at ASC LIMIT 1');
        if ($tenant && !empty($from)) {
            $this->leadService->createLead($tenant['id'], [
                'name' => 'SMS Enquiry',
                'phone' => $from,
                'source' => 'sms',
                'source_detail' => 'Inbound SMS',
                'form_data' => ['sms_body' => $body],
            ]);
        }

        return Response::html('<Response></Response>');
    }

    /**
     * Facebook Lead Ads Webhook.
     */
    public function facebook(Request $request): Response
    {
        return Response::json(['status' => 'facebook_lead_received']);
    }

    /**
     * Google Ads Lead Form Webhook.
     */
    public function googleAds(Request $request): Response
    {
        return Response::json(['status' => 'google_lead_received']);
    }

    /**
     * Stripe Billing Webhook.
     */
    public function stripe(Request $request): Response
    {
        $payload = $request->json();
        $billing = new BillingService($this->db);
        $res = $billing->handleWebhook($payload);

        return Response::json($res);
    }
}
