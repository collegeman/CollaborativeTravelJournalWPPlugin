<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\PostTypes;

final class Expense {
    public const POST_TYPE = 'ctj_expense';

    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Expenses', 'collaborative-travel-journal'),
                'singular_name' => __('Expense', 'collaborative-travel-journal'),
                'add_new' => __('Add New', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Expense', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Expense', 'collaborative-travel-journal'),
                'new_item' => __('New Expense', 'collaborative-travel-journal'),
                'view_item' => __('View Expense', 'collaborative-travel-journal'),
                'search_items' => __('Search Expenses', 'collaborative-travel-journal'),
                'not_found' => __('No expenses found', 'collaborative-travel-journal'),
                'not_found_in_trash' => __('No expenses found in Trash', 'collaborative-travel-journal'),
                'all_items' => __('All Expenses', 'collaborative-travel-journal'),
            ],
            'description' => __('Budget tracking for stops', 'collaborative-travel-journal'),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => ['title', 'author', 'custom-fields'],
            'show_in_rest' => true,
        ]);
    }
}
