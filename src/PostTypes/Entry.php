<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\PostTypes;

final class Entry {
    public const POST_TYPE = 'ctj_entry';

    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Entries', 'collaborative-travel-journal'),
                'singular_name' => __('Entry', 'collaborative-travel-journal'),
                'add_new' => __('Add New', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Entry', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Entry', 'collaborative-travel-journal'),
                'new_item' => __('New Entry', 'collaborative-travel-journal'),
                'view_item' => __('View Entry', 'collaborative-travel-journal'),
                'search_items' => __('Search Entries', 'collaborative-travel-journal'),
                'not_found' => __('No entries found', 'collaborative-travel-journal'),
                'not_found_in_trash' => __('No entries found in Trash', 'collaborative-travel-journal'),
                'all_items' => __('All Entries', 'collaborative-travel-journal'),
            ],
            'description' => __('Journal entries for stops', 'collaborative-travel-journal'),
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'author'],
            'show_in_rest' => true,
        ]);
    }
}
