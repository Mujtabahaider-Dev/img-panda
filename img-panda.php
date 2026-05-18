<?php
/**
 * Plugin Name: Img Panda
 * Description: Automatically convert images to WebP format for better performance
 * Version: 1.0.0
 * Author: Mak8it
 * Author URI: https://Mak8it.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: img-panda
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('IMG_PANDA_VERSION', '1.0.0');
define('IMG_PANDA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMG_PANDA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IMG_PANDA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function img_panda_activate()
{
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-activator.php';
	Img_Panda_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function img_panda_deactivate()
{
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-activator.php';
	Img_Panda_Activator::deactivate();
}

register_activation_hook(__FILE__, 'img_panda_activate');
register_deactivation_hook(__FILE__, 'img_panda_deactivate');

/**
 * Begin execution of the plugin.
 */
function img_panda_run()
{
	// Core libraries
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-converter.php';
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-settings.php';
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-bulk-processor.php';
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-stats.php';
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-ai-handler.php';
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-ajax-handler.php';
	require_once IMG_PANDA_PLUGIN_DIR . 'includes/class-frontend.php';

	// Initialize Components
	(new Img_Panda_Settings())->init();
	(new Img_Panda_Converter())->init();
	(new Img_Panda_AI_Handler())->init();
	(new Img_Panda_Ajax_Handler())->init();
	(new Img_Panda_Frontend())->init();
}

// Kick off the engine
add_action('plugins_loaded', 'img_panda_run');
