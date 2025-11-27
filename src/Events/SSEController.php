<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Events;

use Collegeman\CollaborativeTravelJournal\Rest\CollaboratorsController;
use WP_REST_Request;

final class SSEController
{
    public const NAMESPACE = 'ctj/v1';

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/trips/(?P<trip_id>\d+)/events', [
            'methods' => 'GET',
            'callback' => [self::class, 'streamEvents'],
            'permission_callback' => [CollaboratorsController::class, 'canViewTrip'],
            'args' => [
                'trip_id' => [
                    'validate_callback' => fn($p) => is_numeric($p),
                ],
                'since' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'mode' => [
                    'default' => 'sse',
                    'validate_callback' => fn($p) => in_array($p, ['sse', 'poll'], true),
                ],
            ],
        ]);
    }

    public static function streamEvents(WP_REST_Request $request): void
    {
        $tripId = (int) $request->get_param('trip_id');
        $since = $request->get_param('since');
        $mode = $request->get_param('mode');

        // Default to 30 seconds ago if no since provided
        if (empty($since)) {
            $since = gmdate('Y-m-d H:i:s', time() - 30);
        } else {
            // Convert ISO 8601 to MySQL datetime
            $timestamp = strtotime($since);
            $since = $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : gmdate('Y-m-d H:i:s', time() - 30);
        }

        if ($mode === 'sse') {
            self::handleSSE($tripId, $since);
        } else {
            self::handlePoll($tripId, $since);
        }
    }

    private static function handleSSE(int $tripId, string $since): void
    {
        // Disable any output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        // Send initial connection event
        echo "event: connected\n";
        echo "data: " . wp_json_encode(['time' => gmdate('c')]) . "\n\n";
        flush();

        // Short poll loop (max 25 seconds to stay under typical 30s timeouts)
        $startTime = time();
        $maxDuration = 25;
        $pollInterval = 2;

        while ((time() - $startTime) < $maxDuration) {
            if (connection_aborted()) {
                break;
            }

            $events = EventStore::getEventsSince($tripId, $since);

            foreach ($events as $event) {
                echo "id: {$event['id']}\n";
                echo "event: {$event['event_type']}\n";
                echo "data: " . wp_json_encode($event) . "\n\n";
                flush();

                // Update since to latest event
                $since = $event['created_at'];
            }

            // Send heartbeat to keep connection alive
            echo ": heartbeat " . time() . "\n\n";
            flush();

            sleep($pollInterval);
        }

        exit;
    }

    private static function handlePoll(int $tripId, string $since): void
    {
        header('Content-Type: application/json');

        $events = EventStore::getEventsSince($tripId, $since);
        $serverTime = gmdate('c');

        echo wp_json_encode([
            'events' => $events,
            'serverTime' => $serverTime,
        ]);
        exit;
    }
}
