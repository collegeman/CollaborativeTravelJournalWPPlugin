<?php
/**
 * Plugin Name: Collaborative Travel Journal
 * Plugin URI: https://github.com/collegeman/collaborative-travel-journal-wp-plugin
 * Description: A modern WordPress plugin for collaborative travel journaling
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.3
 * Author: Aaron Collegeman
 * Author URI: https://github.com/collegeman
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: collaborative-travel-journal
 * Domain Path: /languages
 */

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal;

if (!defined('ABSPATH')) {
    exit;
}

define('CTJ_VERSION', '1.0.0');
define('CTJ_PLUGIN_FILE', __FILE__);
define('CTJ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTJ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTJ_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once CTJ_PLUGIN_DIR . 'vendor/autoload.php';

function ctj(): Plugin {
    static $instance = null;

    if ($instance === null) {
        $instance = new Plugin();
    }

    return $instance;
}

register_activation_hook(__FILE__, [Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Deactivator::class, 'deactivate']);

add_action('plugins_loaded', function() {
    ctj()->init();
});
