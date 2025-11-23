<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Taxonomies;

use Collegeman\CollaborativeTravelJournal\PostTypes\Trip;

final class TripStatus {
    public const TAXONOMY = 'ctj_trip_status';

    public static function register(): void {
        register_taxonomy(self::TAXONOMY, [Trip::POST_TYPE], [
            'labels' => [
                'name' => __('Trip Statuses', 'collaborative-travel-journal'),
                'singular_name' => __('Trip Status', 'collaborative-travel-journal'),
                'search_items' => __('Search Trip Statuses', 'collaborative-travel-journal'),
                'all_items' => __('All Trip Statuses', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Trip Status', 'collaborative-travel-journal'),
                'update_item' => __('Update Trip Status', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Trip Status', 'collaborative-travel-journal'),
                'new_item_name' => __('New Trip Status Name', 'collaborative-travel-journal'),
                'menu_name' => __('Trip Statuses', 'collaborative-travel-journal'),
            ],
            'description' => __('Status of trips (planning, active, completed)', 'collaborative-travel-journal'),
            'hierarchical' => false,
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'trip-status'],
        ]);

        self::insertDefaultTerms();
    }

    private static function insertDefaultTerms(): void {
        $default_terms = [
            'Planning',
            'Active',
            'Completed',
        ];

        foreach ($default_terms as $term) {
            if (!term_exists($term, self::TAXONOMY)) {
                wp_insert_term($term, self::TAXONOMY);
            }
        }
    }
}
