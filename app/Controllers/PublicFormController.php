<?php
/**
 * Intakely — Public Form & Callback Controller
 * Renders tenant-branded hosted intake forms at /f/{slug},
 * verifies honeypots, rate limits, captures UTM parameters, and creates leads.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;

final class PublicFormController
{
    private Database $db;
    private LeadService $leadService;

    public function __construct(?Database $db = null, ?LeadService $leadService = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->leadService = $leadService ?? new LeadService($this->db);
    }

    /**
     * Show hosted public form at /f/{slug}.
     */
    public function show(Request $request): Response
    {
        $slug = $request->getAttribute('slug');

        $form = $this->db->fetchOne(
            'SELECT f.*, t.name as tenant_name, t.settings as tenant_settings, t.id as tenant_id
             FROM forms f
             JOIN tenants t ON f.tenant_id = t.id
             WHERE f.slug = $1 AND f.is_active = true',
            [$slug]
        );

        if (!$form) {
            return Response::html('<h1>Form Not Found</h1><p>The requested intake form is not active or does not exist.</p>', 404);
        }

        $fields = is_string($form['fields']) ? json_decode($form['fields'], true) : ($form['fields'] ?? []);
        $settings = is_string($form['settings']) ? json_decode($form['settings'], true) : ($form['settings'] ?? []);
        $tenantSettings = is_string($form['tenant_settings']) ? json_decode($form['tenant_settings'], true) : ($form['tenant_settings'] ?? []);

        return Response::view('forms/public', [
            'form' => $form,
            'fields' => $fields,
            'settings' => $settings,
            'tenantSettings' => $tenantSettings,
            'title' => $form['name'] . ' — ' . $form['tenant_name'],
            'submitted' => false,
        ]);
    }

    /**
     * Process public form submission.
     */
    public function submit(Request $request): Response
    {
        $slug = $request->getAttribute('slug');

        $form = $this->db->fetchOne(
            'SELECT f.*, t.name as tenant_name, t.settings as tenant_settings, t.id as tenant_id
             FROM forms f
             JOIN tenants t ON f.tenant_id = t.id
             WHERE f.slug = $1 AND f.is_active = true',
            [$slug]
        );

        if (!$form) {
            return Response::html('<h1>Form Not Found</h1>', 404);
        }

        // 1. Honeypot anti-spam check (if hidden field filled, silently drop spam)
        $honeypot = $request->input('_hp_check');
        if (!empty($honeypot)) {
            // Silently return success to fool bots
            return Response::view('forms/public', [
                'form' => $form,
                'fields' => [],
                'settings' => [],
                'tenantSettings' => [],
                'title' => 'Thank you',
                'submitted' => true,
                'successMessage' => 'Thank you for your enquiry.',
            ]);
        }

        $tenantId = $form['tenant_id'];
        $fields = is_string($form['fields']) ? json_decode($form['fields'], true) : ($form['fields'] ?? []);
        $settings = is_string($form['settings']) ? json_decode($form['settings'], true) : ($form['settings'] ?? []);
        $tenantSettings = is_string($form['tenant_settings']) ? json_decode($form['tenant_settings'], true) : ($form['tenant_settings'] ?? []);

        // 2. Extract standard fields and custom form answers
        $formData = $request->allPost();
        $name = trim((string) ($formData['name'] ?? $formData['full_name'] ?? 'Online Enquiry'));
        $email = !empty($formData['email']) ? trim($formData['email']) : null;
        $phone = !empty($formData['phone']) ? trim($formData['phone']) : null;
        $service = !empty($formData['service']) ? trim($formData['service']) : ($form['name'] ?? 'General Enquiry');
        $company = !empty($formData['company']) ? trim($formData['company']) : null;

        // 3. Extract UTM attributes
        $utmSource = $formData['utm_source'] ?? $request->query('utm_source');
        $utmMedium = $formData['utm_medium'] ?? $request->query('utm_medium');
        $utmCampaign = $formData['utm_campaign'] ?? $request->query('utm_campaign');

        // 4. Create Lead via LeadService (runs scoring, routing, and automations!)
        $lead = $this->leadService->createLead($tenantId, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'service' => $service,
            'source' => 'web_form',
            'source_detail' => 'Form: ' . $form['name'],
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'landing_page' => $request->header('referer'),
            'form_data' => $formData,
            'consent_given' => !empty($formData['consent']),
        ]);

        // 5. Update submission counter
        $this->db->execute('UPDATE forms SET submissions_count = submissions_count + 1 WHERE id = $1', [$form['id']]);

        // Record form submission record
        $this->db->execute(
            'INSERT INTO form_submissions (id, tenant_id, form_id, lead_id, data, ip_address, referrer, created_at)
             VALUES (gen_random_uuid(), $1, $2, $3, $4, $5::inet, $6, NOW())',
            [
                $tenantId,
                $form['id'],
                $lead['id'],
                json_encode($formData),
                $request->ip() ?: '127.0.0.1',
                $request->header('referer'),
            ]
        );

        $successMsg = $settings['success_message'] ?? 'Thank you! Your enquiry has been received and our team will be in touch shortly.';

        return Response::view('forms/public', [
            'form' => $form,
            'fields' => $fields,
            'settings' => $settings,
            'tenantSettings' => $tenantSettings,
            'title' => 'Enquiry Received — ' . $form['tenant_name'],
            'submitted' => true,
            'successMessage' => $successMsg,
        ]);
    }

    /**
     * Floating Callback Widget endpoint.
     */
    public function callback(Request $request): Response
    {
        $input = $request->json();
        $tenantId = $input['tenant_id'] ?? null;
        $phone = $input['phone'] ?? null;
        $name = $input['name'] ?? 'Callback Request';

        if (!$tenantId || !$phone) {
            return Response::json(['success' => false, 'error' => 'Missing phone number.'], 400);
        }

        $lead = $this->leadService->createLead($tenantId, [
            'name' => $name,
            'phone' => $phone,
            'source' => 'callback',
            'source_detail' => 'Website Floating Widget',
            'priority' => 'high',
        ]);

        return Response::json(['success' => true, 'message' => 'Callback requested. We will call you shortly!']);
    }
}
