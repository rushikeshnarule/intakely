<?php
/**
 * Intakely — Appointment & Booking Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class AppointmentController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function index(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');

        $appointments = $this->db->fetchAll(
            'SELECT a.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone,
                    u.name as user_name
             FROM appointments a
             LEFT JOIN leads l ON a.lead_id = l.id
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.tenant_id = $1
             ORDER BY a.start_at ASC',
            [$tenantId]
        );

        return Response::view('appointments/index', [
            'title' => 'Appointments & Consultations — Intakely',
            'activeNav' => 'appointments',
            'appointments' => $appointments,
        ]);
    }

    /**
     * Download .ics iCalendar file for appointment.
     */
    public function ics(Request $request): Response
    {
        $tenantId = $request->getAttribute('_tenant_id');
        $id = $request->getAttribute('id');

        $apt = $this->db->fetchOne(
            'SELECT a.*, l.name as lead_name, l.email as lead_email, u.name as user_name
             FROM appointments a
             LEFT JOIN leads l ON a.lead_id = l.id
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.id = $1 AND a.tenant_id = $2',
            [$id, $tenantId]
        );

        if (!$apt) {
            return Response::error('Appointment not found', 404);
        }

        $start = gmdate('Ymd\THis\Z', strtotime($apt['start_at']));
        $end = gmdate('Ymd\THis\Z', strtotime($apt['end_at']));
        $summary = $apt['title'];

        $ics = "BEGIN:VCALENDAR\r\n" .
               "VERSION:2.0\r\n" .
               "PRODID:-//Intakely//Calendar//EN\r\n" .
               "BEGIN:VEVENT\r\n" .
               "UID:{$apt['id']}@intakely.com\r\n" .
               "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n" .
               "DTSTART:{$start}\r\n" .
               "DTEND:{$end}\r\n" .
               "SUMMARY:{$summary}\r\n" .
               "DESCRIPTION:Client: {$apt['lead_name']} ({$apt['lead_email']})\r\n" .
               "STATUS:CONFIRMED\r\n" .
               "END:VEVENT\r\n" .
               "END:VCALENDAR\r\n";

        return new Response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="appointment-' . $apt['id'] . '.ics"',
        ]);
    }
}
