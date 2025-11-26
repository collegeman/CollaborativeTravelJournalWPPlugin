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
        <?php echo $wp_config; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <script type="module" src="<?php echo esc_url($dev_server_url); ?>/@vite/client"></script>
        <script type="module" src="<?php echo esc_url($dev_server_url); ?>/src/main.ts"></script>
    </head>
    <body>
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

    // Insert the config script before </head>
    $html = str_replace('</head>', $wp_config . '</head>', $html);

    // Output the HTML
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $html;
}
