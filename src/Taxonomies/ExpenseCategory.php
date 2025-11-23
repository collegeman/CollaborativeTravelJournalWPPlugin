<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal\Taxonomies;

use Collegeman\CollaborativeTravelJournal\PostTypes\Expense;

final class ExpenseCategory {
    public const TAXONOMY = 'ctj_expense_category';

    public static function register(): void {
        register_taxonomy(self::TAXONOMY, [Expense::POST_TYPE], [
            'labels' => [
                'name' => __('Expense Categories', 'collaborative-travel-journal'),
                'singular_name' => __('Expense Category', 'collaborative-travel-journal'),
                'search_items' => __('Search Expense Categories', 'collaborative-travel-journal'),
                'all_items' => __('All Expense Categories', 'collaborative-travel-journal'),
                'edit_item' => __('Edit Expense Category', 'collaborative-travel-journal'),
                'update_item' => __('Update Expense Category', 'collaborative-travel-journal'),
                'add_new_item' => __('Add New Expense Category', 'collaborative-travel-journal'),
                'new_item_name' => __('New Expense Category Name', 'collaborative-travel-journal'),
                'menu_name' => __('Expense Categories', 'collaborative-travel-journal'),
            ],
            'description' => __('Categories for expenses (food, lodging, etc.)', 'collaborative-travel-journal'),
            'hierarchical' => false,
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'expense-category'],
        ]);

        self::insertDefaultTerms();
    }

    private static function insertDefaultTerms(): void {
        $default_terms = [
            'Food',
            'Lodging',
            'Transport',
            'Activities',
            'Shopping',
            'Other',
        ];

        foreach ($default_terms as $term) {
            if (!term_exists($term, self::TAXONOMY)) {
                wp_insert_term($term, self::TAXONOMY);
            }
        }
    }
}
