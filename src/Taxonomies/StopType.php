<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Taxonomies;

use Collegeman\CollaborativeTravelJournal\PostTypes\Stop;

final class StopType {
    public const TAXONOMY = 'ctj_stop_type';

    public static function register(): void {
        register_taxonomy(self::TAXONOMY, [Stop::POST_TYPE], [
            'labels' => [
                'name' => __('Stop Types', 'collaborative-travel-journal'),
                'singular_name' => __('Stop Type', 'collaborative-travel-journal'),
                'search_items' => __('Search Stop Types', 'collaborative-travel-journal'),
                'all_items' => __('All Stop Types', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Stop Type', 'collaborative-travel-journal'),
                'update_item' => __('Update Stop Type', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Stop Type', 'collaborative-travel-journal'),
                'new_item_name' => __('New Stop Type Name', 'collaborative-travel-journal'),
                'menu_name' => __('Stop Types', 'collaborative-travel-journal'),
            ],
            'description' => __('Types of stops (attraction, restaurant, etc.)', 'collaborative-travel-journal'),
            'hierarchical' => false,
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'stop-type'],
        ]);

        self::insertDefaultTerms();
    }

    private static function insertDefaultTerms(): void {
        $default_terms = [
            'Location',
            'Attraction',
            'Accommodation',
            'Restaurant',
            'Activity',
            'Transportation Hub',
        ];

        foreach ($default_terms as $term) {
            if (!term_exists($term, self::TAXONOMY)) {
                wp_insert_term($term, self::TAXONOMY);
            }
        }
    }
}
