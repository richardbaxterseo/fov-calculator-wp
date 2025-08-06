<?php
/**
 * Plugin Name: FOV Calculator for Sim Racing
 * Plugin URI: https://simracingcockpit.gg/fov-calculator
 * Description: A professional field of view calculator for sim racing setups. Calculate the perfect FOV for your monitors.
 * Version: 1.0.1
 * Author: Richard Baxter
 * Author URI: https://simracingcockpit.gg
 * License: GPL v2 or later
 * Text Domain: fov-calculator
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FOV_CALC_VERSION', '1.0.1');
define('FOV_CALC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FOV_CALC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FOV_CALC_PLUGIN_FILE', __FILE__);

// Include the main plugin class
require_once FOV_CALC_PLUGIN_DIR . 'includes/class-fov-calculator.php';

// Initialize the plugin
function fov_calculator_init() {
    $plugin = new FOV_Calculator();
    $plugin->init();
}
add_action('plugins_loaded', 'fov_calculator_init');

// Activation hook
register_activation_hook(__FILE__, 'fov_calculator_activate');
function fov_calculator_activate() {
    // Add any activation tasks here
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'fov_calculator_deactivate');
function fov_calculator_deactivate() {
    // Add any deactivation tasks here
    flush_rewrite_rules();
}
