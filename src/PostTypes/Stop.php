<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\PostTypes;

final class Stop {
    public const POST_TYPE = 'ctj_stop';

    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Stops', 'collaborative-travel-journal'),
                'singular_name' => __('Stop', 'collaborative-travel-journal'),
                'add_new' => __('Add New', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Stop', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Stop', 'collaborative-travel-journal'),
                'new_item' => __('New Stop', 'collaborative-travel-journal'),
                'view_item' => __('View Stop', 'collaborative-travel-journal'),
                'search_items' => __('Search Stops', 'collaborative-travel-journal'),
                'not_found' => __('No stops found', 'collaborative-travel-journal'),
                'not_found_in_trash' => __('No stops found in Trash', 'collaborative-travel-journal'),
                'all_items' => __('All Stops', 'collaborative-travel-journal'),
                'menu_name' => __('Stops', 'collaborative-travel-journal'),
            ],
            'description' => __('Locations visited or planned during travel', 'collaborative-travel-journal'),
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => true,
            'rewrite' => ['slug' => 'stops'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'custom-fields'],
            'show_in_rest' => true,
            'rest_base' => 'stops',
        ]);

        self::register_meta_fields();
        self::register_rest_query_params();

        add_filter('rest_ctj_stop_query', [__CLASS__, 'filterRestQuery'], 10, 2);
    }

    private static function register_meta_fields(): void {
        $meta_fields = [
            'trip_id' => [
                'type' => 'integer',
                'description' => 'Associated trip ID',
            ],
            'place_id' => [
                'type' => 'string',
                'description' => 'Google Place ID',
            ],
            'formatted_address' => [
                'type' => 'string',
                'description' => 'Formatted address',
            ],
            'latitude' => [
                'type' => 'number',
                'description' => 'Latitude coordinate',
            ],
            'longitude' => [
                'type' => 'number',
                'description' => 'Longitude coordinate',
            ],
            'date' => [
                'type' => 'string',
                'description' => 'Date of stop',
            ],
            'time' => [
                'type' => 'string',
                'description' => 'Time of stop (optional)',
            ],
            'specify_time' => [
                'type' => 'boolean',
                'description' => 'Whether time was explicitly specified',
            ],
        ];

        foreach ($meta_fields as $key => $args) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => $args['type'],
                'description' => $args['description'],
                'single' => true,
                'show_in_rest' => true,
            ]);
        }
    }

    private static function register_rest_query_params(): void {
        add_filter('rest_ctj_stop_collection_params', function($params) {
            $params['trip_id'] = [
                'description' => 'Filter stops by trip ID',
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ];
            return $params;
        });
    }

    public static function filterRestQuery($args, $request) {
        if (isset($request['trip_id'])) {
            $args['meta_query'] = [
                [
                    'key' => 'trip_id',
                    'value' => $request['trip_id'],
                    'compare' => '='
                ]
            ];
        }

        return $args;
    }
}
