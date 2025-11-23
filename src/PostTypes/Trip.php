<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\PostTypes;

final class Trip {
    public const POST_TYPE = 'ctj_trip';

    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Trips', 'collaborative-travel-journal'),
                'singular_name' => __('Trip', 'collaborative-travel-journal'),
                'add_new' => __('Add New', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Trip', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Trip', 'collaborative-travel-journal'),
                'new_item' => __('New Trip', 'collaborative-travel-journal'),
                'view_item' => __('View Trip', 'collaborative-travel-journal'),
                'search_items' => __('Search Trips', 'collaborative-travel-journal'),
                'not_found' => __('No trips found', 'collaborative-travel-journal'),
                'not_found_in_trash' => __('No trips found in Trash', 'collaborative-travel-journal'),
                'all_items' => __('All Trips', 'collaborative-travel-journal'),
                'menu_name' => __('Trips', 'collaborative-travel-journal'),
            ],
            'description' => __('Travel trips with stops, entries, and playlists', 'collaborative-travel-journal'),
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => true,
            'rewrite' => ['slug' => 'trips'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
        ]);
    }
}
