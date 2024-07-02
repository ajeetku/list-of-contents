<?php
/**
 * Plugin Name: List of Contents (LOCP)
 * Description: Automatically generate a table of contents for your posts, pages and custom post types by parsing its contents for headers.
 * Version: 1.0.1
 * Author: CodeInitiator
 * Text Domain: list-of-contents
 * Domain Path: /languages
 * License: GPLv2 or later
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('LOCP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LOCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LOCP_PLUGIN_VESION', '1.0.1');

// Include the main class.
require_once LOCP_PLUGIN_PATH . 'includes/class-loc.php';

// Include the settings class.
require_once LOCP_PLUGIN_PATH . 'includes/class-loc-settings.php';

// Initialize the plugin.
function locp_init() {
    $plugin = new LOCP_Plugin();
    $plugin->run();
}

add_action('plugins_loaded', 'locp_init');
