<?php
/**
 * Intakely — Public Booking Controller (/book/{user})
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;

final class PublicBookingController
{
    private Database $db;
    private LeadService $leadService;

    public function __construct(?Database $db = null, ?LeadService $leadService = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
        $this->leadService = $leadService ?? new LeadService($this->db);
    }

    public function show(Request $request): Response
    {
        $userIdentifier = $request->getAttribute('user');

        // Look up advisor by ID or email prefix
        $user = $this->db->fetchOne(
            'SELECT u.*, t.name as tenant_name, t.settings as tenant_settings, t.id as tenant_id
             FROM users u
             JOIN tenants t ON u.tenant_id = t.id
             WHERE (u.id::text = $1 OR split_part(u.email::text, \'@\', 1) = $1)
               AND u.is_active = true',
            [$userIdentifier]
        );

        if (!$user) {
            return Response::html('<h1>Advisor Not Found</h1><p>The requested booking page does not exist.</p>', 404);
        }

        // Available slots for the next 5 business days
        $slots = [];
        for ($i = 1; $i <= 5; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $slots[$date] = ['09:30', '11:00', '14:00', '15:30', '16:30'];
        }

        return Response::view('appointments/public_book', [
            'user' => $user,
            'slots' => $slots,
            'title' => 'Book Consultation with ' . $user['name'],
            'booked' => false,
        ]);
    }

    public function book(Request $request): Response
    {
        $userIdentifier = $request->getAttribute('user');
        $user = $this->db->fetchOne(
            'SELECT u.*, t.id as tenant_id, t.name as tenant_name
             FROM users u
             JOIN tenants t ON u.tenant_id = t.id
             WHERE (u.id::text = $1 OR split_part(u.email::text, \'@\', 1) = $1)',
            [$userIdentifier]
        );

        if (!$user) {
            return Response::html('<h1>Not Found</h1>', 404);
        }

        $tenantId = $user['tenant_id'];
        $name = trim((string) $request->input('name', 'Booking Client'));
        $email = trim((string) $request->input('email', ''));
        $phone = trim((string) $request->input('phone', ''));
        $date = $request->input('date');
        $time = $request->input('time', '10:00');
        $service = $request->input('service', 'Consultation');

        $startAt = date('Y-m-d H:i:s', strtotime("{$date} {$time}"));
        $endAt = date('Y-m-d H:i:s', strtotime("{$startAt} +45 minutes"));

        // 1. Create or match lead
        $lead = $this->leadService->createLead($tenantId, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'service' => $service,
            'source' => 'web_form',
            'source_detail' => 'Public Booking: ' . $user['name'],
            'assigned_to' => $user['id'],
            'priority' => 'high',
        ]);

        // 2. Insert appointment
        $this->db->execute(
            'INSERT INTO appointments (id, tenant_id, lead_id, user_id, title, start_at, end_at, status, created_at, updated_at)
             VALUES (gen_random_uuid(), $1, $2, $3, $4, $5, $6, \'scheduled\', NOW(), NOW())',
            [$tenantId, $lead['id'], $user['id'], "Consultation: {$service}", $startAt, $endAt]
        );

        return Response::view('appointments/public_book', [
            'user' => $user,
            'slots' => [],
            'title' => 'Consultation Booked — ' . $user['tenant_name'],
            'booked' => true,
            'startAt' => $startAt,
            'service' => $service,
        ]);
    }
}
