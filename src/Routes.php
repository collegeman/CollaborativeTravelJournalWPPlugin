<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal;

final class Routes {
    public static function register(): void {
        add_action('init', [self::class, 'addRewriteRules']);
        add_filter('template_include', [self::class, 'handleTemplate']);
        add_filter('query_vars', [self::class, 'addQueryVars']);
    }

    public static function addRewriteRules(): void {
        add_rewrite_rule(
            '^journal/?.*',
            'index.php?ctj_app=1',
            'top'
        );
    }

    public static function addQueryVars(array $vars): array {
        $vars[] = 'ctj_app';
        return $vars;
    }

    public static function handleTemplate(string $template): string {
        if (get_query_var('ctj_app')) {
            // Require user to be logged in
            if (!is_user_logged_in()) {
                $redirect_to = home_url('/journal');
                $login_url = wp_login_url($redirect_to);
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
