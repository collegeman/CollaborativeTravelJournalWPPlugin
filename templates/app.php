<?php
/**
 * Template for serving the Ionic Vue app
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Path to the built app
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

// Inject WordPress API configuration
$wp_config = sprintf(
    '<script>window.WP_API_URL = %s; window.WP_NONCE = %s;</script>',
    wp_json_encode(rest_url()),
    wp_json_encode(wp_create_nonce('wp_rest'))
);

// Insert the config script before </head>
$html = str_replace('</head>', $wp_config . '</head>', $html);

// Output the HTML
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $html;
