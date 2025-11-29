<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Rest;

use Collegeman\CollaborativeTravelJournal\PostTypes\Trip;
use Collegeman\CollaborativeTravelJournal\PostTypes\Stop;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class MediaController
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route('ctj/v1', '/trips/(?P<trip_id>\d+)/media', [
            'methods' => 'POST',
            'callback' => [self::class, 'uploadMedia'],
            'permission_callback' => [self::class, 'canUpload'],
            'args' => [
                'trip_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'stop_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    public static function canUpload(WP_REST_Request $request): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $tripId = $request->get_param('trip_id');
        $trip = get_post($tripId);

        if (!$trip || $trip->post_type !== Trip::POST_TYPE) {
            return false;
        }

        // TODO: Check if user is a collaborator on this trip
        return current_user_can('upload_files');
    }

    public static function uploadMedia(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $tripId = (int) $request->get_param('trip_id');
        $stopId = $request->get_param('stop_id') ? (int) $request->get_param('stop_id') : null;

        // Verify trip exists
        $trip = get_post($tripId);
        if (!$trip || $trip->post_type !== Trip::POST_TYPE) {
            return new WP_Error('invalid_trip', 'Trip not found', ['status' => 404]);
        }

        // Verify stop belongs to trip if provided
        if ($stopId) {
            $stop = get_post($stopId);
            if (!$stop || $stop->post_type !== Stop::POST_TYPE) {
                return new WP_Error('invalid_stop', 'Stop not found', ['status' => 404]);
            }
            $stopTripId = get_post_meta($stopId, 'trip_id', true);
            if ((int) $stopTripId !== $tripId) {
                return new WP_Error('invalid_stop', 'Stop does not belong to this trip', ['status' => 400]);
            }
        }

        // Handle file upload
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', 'No file provided', ['status' => 400]);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Upload the file
        $uploadOverrides = ['test_form' => false];
        $uploadedFile = wp_handle_upload($files['file'], $uploadOverrides);

        if (isset($uploadedFile['error'])) {
            return new WP_Error('upload_error', $uploadedFile['error'], ['status' => 400]);
        }

        // Create attachment
        $attachment = [
            'post_mime_type' => $uploadedFile['type'],
            'post_title' => sanitize_file_name(pathinfo($files['file']['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $tripId, // Attach to trip
        ];

        $attachmentId = wp_insert_attachment($attachment, $uploadedFile['file'], $tripId);

        if (is_wp_error($attachmentId)) {
            return $attachmentId;
        }

        // Generate metadata (this triggers our GPS extraction hook)
        $attachmentData = wp_generate_attachment_metadata($attachmentId, $uploadedFile['file']);
        wp_update_attachment_metadata($attachmentId, $attachmentData);

        // Set post_date from EXIF capture date if available
        $capturedAt = get_post_meta($attachmentId, 'captured_at', true);
        if ($capturedAt) {
            wp_update_post([
                'ID' => $attachmentId,
                'post_date' => $capturedAt,
                'post_date_gmt' => get_gmt_from_date($capturedAt),
            ]);
        }

        // Store trip_id in meta
        update_post_meta($attachmentId, 'trip_id', $tripId);

        // Get extracted GPS coordinates
        $latitude = get_post_meta($attachmentId, 'latitude', true);
        $longitude = get_post_meta($attachmentId, 'longitude', true);

        // If no stop_id provided but we have GPS, try to find a matching stop
        if (!$stopId && $latitude && $longitude) {
            $stopId = self::findNearestStop($tripId, (float) $latitude, (float) $longitude);
        }

        // Associate with stop if we have one
        if ($stopId) {
            update_post_meta($attachmentId, 'stop_id', $stopId);
        }

        // Return the media item in REST API format
        $response = self::prepareMediaResponse($attachmentId);

        return new WP_REST_Response($response, 201);
    }

    /**
     * Find the nearest stop to the given coordinates within a reasonable distance
     */
    private static function findNearestStop(int $tripId, float $latitude, float $longitude): ?int
    {
        $stops = get_posts([
            'post_type' => Stop::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'trip_id',
                    'value' => $tripId,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($stops)) {
            return null;
        }

        $nearestStop = null;
        $nearestDistance = PHP_FLOAT_MAX;
        $maxDistanceKm = 5; // Only match stops within 5km

        foreach ($stops as $stop) {
            $stopLat = get_post_meta($stop->ID, 'latitude', true);
            $stopLng = get_post_meta($stop->ID, 'longitude', true);

            if (!$stopLat || !$stopLng) {
                continue;
            }

            $distance = self::haversineDistance(
                $latitude,
                $longitude,
                (float) $stopLat,
                (float) $stopLng
            );

            if ($distance < $nearestDistance && $distance <= $maxDistanceKm) {
                $nearestDistance = $distance;
                $nearestStop = $stop->ID;
            }
        }

        return $nearestStop;
    }

    /**
     * Calculate distance between two points using Haversine formula
     * Returns distance in kilometers
     */
    private static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * Prepare media response in REST API format
     */
    private static function prepareMediaResponse(int $attachmentId): array
    {
        $attachment = get_post($attachmentId);
        $metadata = wp_get_attachment_metadata($attachmentId);

        $response = [
            'id' => $attachmentId,
            'date' => get_the_date('c', $attachment),
            'title' => ['rendered' => $attachment->post_title],
            'source_url' => wp_get_attachment_url($attachmentId),
            'mime_type' => $attachment->post_mime_type,
            'media_type' => wp_attachment_is('image', $attachmentId) ? 'image' :
                           (wp_attachment_is('video', $attachmentId) ? 'video' :
                           (wp_attachment_is('audio', $attachmentId) ? 'audio' : 'file')),
            'media_details' => [
                'sizes' => [],
            ],
            'meta' => [
                'trip_id' => get_post_meta($attachmentId, 'trip_id', true),
                'stop_id' => get_post_meta($attachmentId, 'stop_id', true) ?: null,
                'latitude' => get_post_meta($attachmentId, 'latitude', true) ?: null,
                'longitude' => get_post_meta($attachmentId, 'longitude', true) ?: null,
                'captured_at' => get_post_meta($attachmentId, 'captured_at', true) ?: null,
            ],
        ];

        // Add image sizes
        if (!empty($metadata['sizes'])) {
            $uploadDir = wp_upload_dir();
            $baseUrl = trailingslashit($uploadDir['baseurl']);
            $filePath = dirname($metadata['file'] ?? '');

            foreach ($metadata['sizes'] as $size => $sizeData) {
                $response['media_details']['sizes'][$size] = [
                    'source_url' => $baseUrl . ($filePath ? $filePath . '/' : '') . $sizeData['file'],
                    'width' => $sizeData['width'],
                    'height' => $sizeData['height'],
                ];
            }
        }

        if (!empty($metadata['image_meta'])) {
            $response['media_details']['image_meta'] = $metadata['image_meta'];
        }

        return $response;
    }
}
