<?php
/**
 * Template for serving the Ionic Vue app
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if Vite dev server is running
$dev_server_url = 'http://localhost:5173';
$is_dev_mode = false;

// Try to detect dev server
$context = stream_context_create([
    'http' => [
        'timeout' => 1,
        'ignore_errors' => true
    ]
]);

if (@file_get_contents($dev_server_url, false, $context) !== false) {
    $is_dev_mode = true;
}

// Get Google Maps API key from environment or constant
$google_maps_key = defined('GOOGLE_MAPS_API_KEY') ? constant('GOOGLE_MAPS_API_KEY') : '';
if (empty($google_maps_key) && getenv('GOOGLE_MAPS_API_KEY')) {
    $google_maps_key = getenv('GOOGLE_MAPS_API_KEY');
}

// Get the app path
$app_path = \Collegeman\CollaborativeTravelJournal\Routes::getAppPath();

// Inject WordPress API configuration
$wp_config = sprintf(
    '<script>window.WP_API_URL = %s; window.WP_NONCE = %s; window.GOOGLE_MAPS_API_KEY = %s; window.CTJ_APP_PATH = %s;</script>',
    wp_json_encode(rest_url()),
    wp_json_encode(wp_create_nonce('wp_rest')),
    wp_json_encode($google_maps_key),
    wp_json_encode($app_path)
);

// Favicon and splash screen when CTJ_BRAND is enabled
$favicon_tag = '';
$splash_html = '';
if (defined('CTJ_BRAND') && CTJ_BRAND) {
    $favicon_url = plugins_url('app/src/images/journ-favicon.png', CTJ_PLUGIN_FILE);
    $favicon_tag = '<link rel="icon" href="' . esc_url($favicon_url) . '" />';

    $logo_url = plugins_url('app/src/images/journ-logo-reverse.svg', CTJ_PLUGIN_FILE);
    $splash_html = '
    <style>
        #ctj-splash {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #cb5a33;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.3s ease-out;
        }
        #ctj-splash.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        #ctj-splash img {
            width: 180px;
            max-width: 50%;
            height: auto;
        }
    </style>
    <div id="ctj-splash">
        <img src="' . esc_url($logo_url) . '" alt="Journ" />
    </div>';
}

if ($is_dev_mode) {
    // Serve from Vite dev server
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="viewport-fit=cover, width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta name="format-detection" content="telephone=no" />
        <meta name="msapplication-tap-highlight" content="no" />
        <title>Journ</title>
        <base href="/" />
        <?php echo $favicon_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php echo $wp_config; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <script type="module" src="<?php echo esc_url($dev_server_url); ?>/@vite/client"></script>
        <script type="module" src="<?php echo esc_url($dev_server_url); ?>/src/main.ts"></script>
    </head>
    <body>
        <?php echo $splash_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <div id="app"></div>
    </body>
    </html>
    <?php
} else {
    // Serve from built files
    $app_dir = CTJ_PLUGIN_DIR . 'app/dist/';
    $app_url = CTJ_PLUGIN_URL . 'app/dist/';
    $index_file = $app_dir . 'index.html';

    // Check if the app has been built
    if (!file_exists($index_file)) {
        wp_die(
            '<h1>App Not Built</h1>' .
            '<p>The Ionic app has not been built yet. Please run:</p>' .
            '<pre>cd ' . esc_html(CTJ_PLUGIN_DIR) . 'app && npm run build</pre>',
            'App Not Built',
            ['response' => 500]
        );
    }

    // Read the index.html file
    $html = file_get_contents($index_file);

    // Update base href to point to the dist directory
    $html = str_replace(
        '<base href="/" />',
        '<base href="' . $app_url . '" />',
        $html
    );

    // Insert the favicon and config script before </head>
    $html = str_replace('</head>', $favicon_tag . $wp_config . '</head>', $html);

    // Insert splash screen after <body>
    if ($splash_html) {
        $html = preg_replace('/<body[^>]*>/', '$0' . $splash_html, $html, 1);
    }

    // Output the HTML
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $html;
}
