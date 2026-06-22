<?php
/**
 * Plugin Name: Mak8it Smart Image
 * Description: Auto-convert images to WebP and generate AI-powered SEO alt text using Google Gemini or OpenAI. Boost site speed and accessibility in one click.
 * Version: 1.0.0
 * Author: Mak8it
 * Author URI: https://Mak8it.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mak8it-smart-image
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 *
 * @package Mkit_Si
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('MKIT_SI_VERSION', '1.0.0');
define('MKIT_SI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MKIT_SI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MKIT_SI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function mkit_si_activate()
{
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-activator.php';
	Mkit_Si_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function mkit_si_deactivate()
{
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-activator.php';
	Mkit_Si_Activator::deactivate();
}

register_activation_hook(__FILE__, 'mkit_si_activate');
register_deactivation_hook(__FILE__, 'mkit_si_deactivate');

/**
 * Begin execution of the plugin.
 */
function mkit_si_run()
{
	// Core libraries
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-converter.php';
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-settings.php';
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-bulk-processor.php';
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-stats.php';
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-ai-handler.php';
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-ajax-handler.php';
	require_once MKIT_SI_PLUGIN_DIR . 'includes/class-frontend.php';

	// Initialize Components
	(new Mkit_Si_Settings())->init();
	(new Mkit_Si_Converter())->init();
	(new Mkit_Si_AI_Handler())->init();
	(new Mkit_Si_Ajax_Handler())->init();
	(new Mkit_Si_Frontend())->init();
}

// Kick off the engine
add_action('plugins_loaded', 'mkit_si_run');
