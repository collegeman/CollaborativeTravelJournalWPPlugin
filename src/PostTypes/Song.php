<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\PostTypes;

final class Song {
    public const POST_TYPE = 'ctj_song';

    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Songs', 'collaborative-travel-journal'),
                'singular_name' => __('Song', 'collaborative-travel-journal'),
                'add_new' => __('Add New', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Song', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Song', 'collaborative-travel-journal'),
                'new_item' => __('New Song', 'collaborative-travel-journal'),
                'view_item' => __('View Song', 'collaborative-travel-journal'),
                'search_items' => __('Search Songs', 'collaborative-travel-journal'),
                'not_found' => __('No songs found', 'collaborative-travel-journal'),
                'not_found_in_trash' => __('No songs found in Trash', 'collaborative-travel-journal'),
                'all_items' => __('All Songs', 'collaborative-travel-journal'),
            ],
            'description' => __('Playlist songs for trips', 'collaborative-travel-journal'),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => ['title', 'author'],
            'show_in_rest' => true,
        ]);
    }
}
