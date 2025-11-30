<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal;

final class LoginCustomization
{
    public static function register(): void
    {
        if (!defined('CTJ_BRAND') || !CTJ_BRAND) {
            return;
        }

        add_action('login_enqueue_scripts', [self::class, 'customLoginLogo']);
        add_filter('login_headerurl', [self::class, 'loginLogoUrl']);
        add_filter('login_headertext', [self::class, 'loginLogoText']);
        add_filter('get_site_icon_url', [self::class, 'customFavicon'], 10, 3);
        add_action('login_head', [self::class, 'loginFavicon']);
    }

    public static function customFavicon(string $url, int $size, int $blogId): string
    {
        return plugins_url('app/src/images/journ-favicon.png', CTJ_PLUGIN_FILE);
    }

    public static function loginFavicon(): void
    {
        $faviconUrl = plugins_url('app/src/images/journ-favicon.png', CTJ_PLUGIN_FILE);
        echo '<link rel="icon" href="' . esc_url($faviconUrl) . '" />';
    }

    public static function customLoginLogo(): void
    {
        $logoUrl = plugins_url('app/src/images/journ-logo.svg', CTJ_PLUGIN_FILE);
        ?>
        <style type="text/css">
            #login h1 a {
                background-image: url('<?php echo esc_url($logoUrl); ?>');
                background-size: contain;
                background-position: center;
                width: 100%;
                height: 40px;
            }
        </style>
        <?php
    }

    public static function loginLogoUrl(): string
    {
        return home_url();
    }

    public static function loginLogoText(): string
    {
        return get_bloginfo('name');
    }
}
