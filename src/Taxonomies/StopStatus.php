<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Taxonomies;

use Collegeman\CollaborativeTravelJournal\PostTypes\Stop;

final class StopStatus {
    public const TAXONOMY = 'ctj_stop_status';

    public static function register(): void {
        register_taxonomy(self::TAXONOMY, [Stop::POST_TYPE], [
            'labels' => [
                'name' => __('Stop Statuses', 'collaborative-travel-journal'),
                'singular_name' => __('Stop Status', 'collaborative-travel-journal'),
                'search_items' => __('Search Stop Statuses', 'collaborative-travel-journal'),
                'all_items' => __('All Stop Statuses', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Stop Status', 'collaborative-travel-journal'),
                'update_item' => __('Update Stop Status', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Stop Status', 'collaborative-travel-journal'),
                'new_item_name' => __('New Stop Status Name', 'collaborative-travel-journal'),
                'menu_name' => __('Stop Statuses', 'collaborative-travel-journal'),
            ],
            'description' => __('Status of stops (future, past, bookmark, suggestion)', 'collaborative-travel-journal'),
            'hierarchical' => false,
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'stop-status'],
        ]);

        self::insertDefaultTerms();
    }

    private static function insertDefaultTerms(): void {
        $default_terms = [
            'Future',
            'Past',
            'Bookmark',
            'Suggestion',
        ];

        foreach ($default_terms as $term) {
            if (!term_exists($term, self::TAXONOMY)) {
                wp_insert_term($term, self::TAXONOMY);
            }
        }
    }
}
