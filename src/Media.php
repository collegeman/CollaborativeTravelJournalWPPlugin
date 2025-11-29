<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal;

final class Media
{
    public static function register(): void
    {
        add_filter('wp_read_image_metadata', [self::class, 'addGpsToImageMetadata'], 10, 5);
        add_filter('wp_generate_attachment_metadata', [self::class, 'extractGpsOnUpload'], 10, 3);
        add_action('rest_api_init', [self::class, 'registerRestFields']);
        add_filter('upload_mimes', [self::class, 'allowHeicUploads']);
        add_filter('wp_check_filetype_and_ext', [self::class, 'fixHeicFiletype'], 10, 5);
    }

    /**
     * Allow HEIC/HEIF uploads
     */
    public static function allowHeicUploads(array $mimes): array
    {
        $mimes['heic'] = 'image/heic';
        $mimes['heif'] = 'image/heif';
        return $mimes;
    }

    /**
     * Fix HEIC filetype detection (WordPress doesn't recognize it by default)
     */
    public static function fixHeicFiletype(array $data, string $file, string $filename, ?array $mimes, string $realMime): array
    {
        if (!empty($data['ext']) && !empty($data['type'])) {
            return $data;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'heic') {
            $data['ext'] = 'heic';
            $data['type'] = 'image/heic';
            $data['proper_filename'] = $filename;
        } elseif ($ext === 'heif') {
            $data['ext'] = 'heif';
            $data['type'] = 'image/heif';
            $data['proper_filename'] = $filename;
        }

        return $data;
    }

    public static function registerRestFields(): void
    {
        register_post_meta('attachment', 'latitude', [
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('attachment', 'longitude', [
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_post_meta('attachment', 'captured_at', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Add GPS coordinates to the image metadata array that WordPress reads
     */
    public static function addGpsToImageMetadata(array $meta, string $file, int $sourceImageType, ?array $iptc, ?array $exif): array
    {
        if (empty($exif)) {
            return $meta;
        }

        $gps = self::extractGpsFromExif($exif);
        if ($gps) {
            $meta['latitude'] = $gps['latitude'];
            $meta['longitude'] = $gps['longitude'];
        }

        return $meta;
    }

    /**
     * Extract GPS data on upload and save to post meta
     */
    public static function extractGpsOnUpload(array $metadata, int $attachmentId, string $context): array
    {
        $file = get_attached_file($attachmentId);
        if (!$file || !file_exists($file)) {
            return $metadata;
        }

        $mimeType = get_post_mime_type($attachmentId);

        // Handle images
        if (str_starts_with($mimeType, 'image/') && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file, 'EXIF', true);
            if ($exif) {
                $gps = self::extractGpsFromExif($exif);
                if ($gps) {
                    update_post_meta($attachmentId, 'latitude', $gps['latitude']);
                    update_post_meta($attachmentId, 'longitude', $gps['longitude']);

                    // Also store in the metadata array
                    if (!isset($metadata['image_meta'])) {
                        $metadata['image_meta'] = [];
                    }
                    $metadata['image_meta']['latitude'] = $gps['latitude'];
                    $metadata['image_meta']['longitude'] = $gps['longitude'];
                }

                // Extract capture date
                $capturedAt = self::extractCaptureDate($exif);
                if ($capturedAt) {
                    update_post_meta($attachmentId, 'captured_at', $capturedAt);
                    $metadata['image_meta']['captured_at'] = $capturedAt;
                }
            }
        }

        // Handle videos - requires ffprobe/ffmpeg for full metadata extraction
        // For now, we'll attempt to read with getID3 if available
        if (str_starts_with($mimeType, 'video/')) {
            $videoGps = self::extractGpsFromVideo($file);
            if ($videoGps) {
                update_post_meta($attachmentId, 'latitude', $videoGps['latitude']);
                update_post_meta($attachmentId, 'longitude', $videoGps['longitude']);
            }
        }

        return $metadata;
    }

    /**
     * Extract GPS coordinates from EXIF data
     */
    private static function extractGpsFromExif(array $exif): ?array
    {
        // GPS data can be in different locations depending on how exif_read_data was called
        $gpsData = $exif['GPS'] ?? $exif;

        if (empty($gpsData['GPSLatitude']) || empty($gpsData['GPSLongitude'])) {
            return null;
        }

        $latitude = self::gpsToDecimal(
            $gpsData['GPSLatitude'],
            $gpsData['GPSLatitudeRef'] ?? 'N'
        );

        $longitude = self::gpsToDecimal(
            $gpsData['GPSLongitude'],
            $gpsData['GPSLongitudeRef'] ?? 'E'
        );

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * Convert GPS coordinates from EXIF format (degrees, minutes, seconds) to decimal
     */
    private static function gpsToDecimal(array $coordinate, string $hemisphere): ?float
    {
        if (count($coordinate) !== 3) {
            return null;
        }

        $degrees = self::fractionToFloat($coordinate[0]);
        $minutes = self::fractionToFloat($coordinate[1]);
        $seconds = self::fractionToFloat($coordinate[2]);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($hemisphere === 'S' || $hemisphere === 'W') {
            $decimal *= -1;
        }

        return round($decimal, 7);
    }

    /**
     * Convert EXIF fraction string (e.g., "40/1") to float
     */
    private static function fractionToFloat(string $fraction): ?float
    {
        $parts = explode('/', $fraction);
        if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
            return (float) $parts[0] / (float) $parts[1];
        }
        if (count($parts) === 1) {
            return (float) $parts[0];
        }
        return null;
    }

    /**
     * Extract capture date from EXIF data
     */
    private static function extractCaptureDate(array $exif): ?string
    {
        $dateFields = [
            'DateTimeOriginal',
            'DateTimeDigitized',
            'DateTime',
        ];

        $exifSection = $exif['EXIF'] ?? $exif;

        foreach ($dateFields as $field) {
            if (!empty($exifSection[$field])) {
                // EXIF date format: "2024:01:15 14:30:00"
                $date = $exifSection[$field];
                // Convert to ISO format
                $date = str_replace(':', '-', substr($date, 0, 10)) . substr($date, 10);
                return $date;
            }
        }

        return null;
    }

    /**
     * Attempt to extract GPS from video files
     * Note: This requires the file to have embedded GPS metadata (common in smartphone videos)
     */
    private static function extractGpsFromVideo(string $file): ?array
    {
        // WordPress doesn't include video metadata extraction by default
        // This would require ffprobe or a library like getID3
        // For now, return null - can be enhanced later
        return null;
    }
}
