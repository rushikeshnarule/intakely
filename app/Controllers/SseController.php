<?php
/**
 * Intakely — Server-Sent Events (SSE) Controller
 * Streams real-time pipeline events (lead created, moved, assigned) to browser clients
 * using PostgreSQL LISTEN/NOTIFY.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class SseController
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Container::getInstance()->get(Database::class);
    }

    public function leads(Request $request): Response
    {
        // Release session lock so other user requests are never blocked
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Disable output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('X-Accel-Buffering: no');

        // Instruct browser EventSource to reconnect smoothly without worker starvation
        echo "retry: 4000\n\n";

        $pdo = $this->db->pdo();
        $pdo->exec('LISTEN leads');

        // Check for any pending notification with a short 100ms window
        $notify = false;
        if (method_exists($pdo, 'pgsqlGetNotify')) {
            $notify = $pdo->pgsqlGetNotify(\PDO::FETCH_ASSOC, 100);
        }

        if ($notify) {
            $payload = json_decode($notify['payload'] ?? '{}', true);
            $event = $payload['event'] ?? 'lead_moved';
            echo "event: {$event}\n";
            echo "data: " . json_encode($payload) . "\n\n";
        } else {
            echo "event: ping\ndata: " . json_encode(['connected' => true, 'time' => time()]) . "\n\n";
        }

        flush();
        exit;
    }
}
