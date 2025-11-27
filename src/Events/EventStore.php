<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Events;

final class EventStore
{
    public const TABLE_NAME = 'ctj_events';

    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    public static function createTable(): void
    {
        global $wpdb;
        $table = self::getTableName();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            trip_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            object_type VARCHAR(50) NOT NULL,
            object_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            payload JSON,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trip_created (trip_id, created_at),
            KEY created_at (created_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function recordEvent(
        int $tripId,
        string $eventType,
        string $objectType,
        int $objectId,
        int $userId,
        array $payload = []
    ): int {
        global $wpdb;

        $wpdb->insert(
            self::getTableName(),
            [
                'trip_id' => $tripId,
                'event_type' => $eventType,
                'object_type' => $objectType,
                'object_id' => $objectId,
                'user_id' => $userId,
                'payload' => wp_json_encode($payload),
                'created_at' => gmdate('Y-m-d H:i:s'),
            ],
            ['%d', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public static function getEventsSince(
        int $tripId,
        string $since,
        int $limit = 100
    ): array {
        global $wpdb;
        $table = self::getTableName();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE trip_id = %d AND created_at > %s
                 ORDER BY created_at ASC
                 LIMIT %d",
                $tripId,
                $since,
                $limit
            ),
            ARRAY_A
        );

        return array_map(function ($row) {
            $row['id'] = (int) $row['id'];
            $row['trip_id'] = (int) $row['trip_id'];
            $row['object_id'] = (int) $row['object_id'];
            $row['user_id'] = (int) $row['user_id'];
            $row['payload'] = json_decode($row['payload'], true) ?? [];
            return $row;
        }, $results ?: []);
    }

    public static function cleanup(int $olderThanHours = 24): int
    {
        global $wpdb;
        $table = self::getTableName();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($olderThanHours * 3600));

        return (int) $wpdb->query(
            $wpdb->prepare("DELETE FROM $table WHERE created_at < %s", $cutoff)
        );
    }
}
