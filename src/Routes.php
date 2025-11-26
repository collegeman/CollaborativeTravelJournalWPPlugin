<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal;

final class Routes {
    public static function register(): void {
        add_action('init', [self::class, 'addRewriteRules']);
        add_action('init', [self::class, 'maybeFlushRules'], 99);
        add_filter('template_include', [self::class, 'handleTemplate']);
        add_filter('query_vars', [self::class, 'addQueryVars']);
    }

    public static function maybeFlushRules(): void {
        $current_path = self::getAppPath();
        // Use '__root__' as sentinel for empty path since WP handles empty strings poorly
        $store_value = $current_path === '' ? '__root__' : $current_path;
        $stored_path = get_option('ctj_app_path', '');

        if ($stored_path !== $store_value) {
            update_option('ctj_app_path', $store_value);
            flush_rewrite_rules();
        }
    }

    public static function addRewriteRules(): void {
        $path = self::getAppPath();

        if ($path === '') {
            // Root path - match everything except wp-admin, wp-json, etc.
            add_rewrite_rule(
                '^(?!wp-admin|wp-json|wp-login|wp-content).*',
                'index.php?ctj_app=1',
                'top'
            );
        } else {
            add_rewrite_rule(
                '^' . preg_quote($path, '/') . '/?.*',
                'index.php?ctj_app=1',
                'top'
            );
        }
    }

    public static function getAppPath(): string {
        $configured = defined('CTJ_APP_PATH') ? CTJ_APP_PATH : 'journal';
        if ($configured === null || $configured === '' || $configured === '/') {
            return '';
        }
        return trim($configured, '/');
    }

    public static function getAppUrl(): string {
        $path = self::getAppPath();
        return home_url($path === '' ? '/' : '/' . $path);
    }

    public static function addQueryVars(array $vars): array {
        $vars[] = 'ctj_app';
        return $vars;
    }

    public static function handleTemplate(string $template): string {
        $is_app = get_query_var('ctj_app');

        // For root path, also check if we're on the front page
        if (!$is_app && self::getAppPath() === '' && (is_front_page() || is_home())) {
            $is_app = true;
        }

        if ($is_app) {
            // Require user to be logged in
            if (!is_user_logged_in()) {
                $login_url = wp_login_url(self::getAppUrl());
                wp_redirect($login_url);
                exit;
            }

            $app_template = CTJ_PLUGIN_DIR . 'templates/app.php';

            if (file_exists($app_template)) {
                return $app_template;
            }
        }

        return $template;
    }

    public static function flush(): void {
        self::addRewriteRules();
        flush_rewrite_rules();
    }
}
