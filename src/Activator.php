<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal;

final class Activator {
    public static function activate(): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        self::checkRequirements();
        self::createDatabaseTables();
        self::setDefaultOptions();

        // Register and flush rewrite rules for the app path
        Routes::flush();
    }

    private static function checkRequirements(): void {
        if (version_compare(PHP_VERSION, '8.2', '<')) {
            deactivate_plugins(CTJ_PLUGIN_BASENAME);
            wp_die(
                esc_html__('This plugin requires PHP 8.2 or higher.', 'collaborative-travel-journal'),
                esc_html__('Plugin Activation Error', 'collaborative-travel-journal'),
                ['back_link' => true]
            );
        }
    }

    private static function createDatabaseTables(): void
    {
        Events\EventStore::createTable();
    }

    private static function setDefaultOptions(): void {
        $defaults = [
            'ctj_version' => CTJ_VERSION,
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
