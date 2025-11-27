<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Events;

use Collegeman\CollaborativeTravelJournal\PostTypes\Trip;

final class EventDispatcher
{
    private const TRACKED_POST_TYPES = [
        'ctj_stop' => 'stop',
        'ctj_entry' => 'entry',
        'ctj_expense' => 'expense',
        'ctj_song' => 'song',
    ];

    public static function register(): void
    {
        add_action('before_delete_post', [self::class, 'onDeletePost']);
        add_action('wp_trash_post', [self::class, 'onDeletePost']);

        // Media (attachment) events
        add_action('add_attachment', [self::class, 'onAddAttachment']);
        add_action('delete_attachment', [self::class, 'onDeleteAttachment']);

        // Cleanup cron
        add_action('ctj_cleanup_events', [EventStore::class, 'cleanup']);
        if (!wp_next_scheduled('ctj_cleanup_events')) {
            wp_schedule_event(time(), 'hourly', 'ctj_cleanup_events');
        }
    }

    public static function onDeletePost(int $postId): void
    {
        $post = get_post($postId);
        if (!$post || !isset(self::TRACKED_POST_TYPES[$post->post_type])) {
            return;
        }

        $objectType = self::TRACKED_POST_TYPES[$post->post_type];
        $tripId = self::getTripIdForPost($post);

        if (!$tripId) {
            return;
        }

        EventStore::recordEvent(
            $tripId,
            "{$objectType}.deleted",
            $objectType,
            $postId,
            get_current_user_id(),
            ['title' => $post->post_title]
        );
    }

    public static function onAddAttachment(int $attachmentId): void
    {
        $attachment = get_post($attachmentId);
        if (!$attachment) {
            return;
        }

        $tripId = self::getTripIdForAttachment($attachment);
        if (!$tripId) {
            return;
        }

        EventStore::recordEvent(
            $tripId,
            'media.created',
            'media',
            $attachmentId,
            get_current_user_id(),
            [
                'filename' => basename(get_attached_file($attachmentId) ?: ''),
                'mime_type' => $attachment->post_mime_type,
            ]
        );
    }

    public static function onDeleteAttachment(int $attachmentId): void
    {
        $attachment = get_post($attachmentId);
        if (!$attachment) {
            return;
        }

        $tripId = self::getTripIdForAttachment($attachment);
        if (!$tripId) {
            return;
        }

        EventStore::recordEvent(
            $tripId,
            'media.deleted',
            'media',
            $attachmentId,
            get_current_user_id(),
            [
                'filename' => basename(get_attached_file($attachmentId) ?: ''),
            ]
        );
    }

    private static function getTripIdForAttachment(\WP_Post $attachment): ?int
    {
        // Check if attached directly to a Trip
        if ($attachment->post_parent) {
            $parent = get_post($attachment->post_parent);
            if ($parent && $parent->post_type === Trip::POST_TYPE) {
                return $parent->ID;
            }

            // If parent is a Stop/Entry/etc, get trip_id from its meta
            if ($parent && isset(self::TRACKED_POST_TYPES[$parent->post_type])) {
                $tripId = get_post_meta($parent->ID, 'trip_id', true);
                return $tripId ? (int) $tripId : null;
            }
        }

        // Check for trip_id in attachment meta as fallback
        $tripId = get_post_meta($attachment->ID, 'trip_id', true);
        return $tripId ? (int) $tripId : null;
    }

    private static function getTripIdForPost(\WP_Post $post): ?int
    {
        if ($post->post_type === Trip::POST_TYPE) {
            return $post->ID;
        }

        $tripId = get_post_meta($post->ID, 'trip_id', true);
        return $tripId ? (int) $tripId : null;
    }
}
