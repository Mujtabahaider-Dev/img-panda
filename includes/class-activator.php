<?php
/**
 * Fired during plugin activation and deactivation.
 *
 * @package Img_Panda
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Plugin Activator Class.
 */
class Img_Panda_Activator
{

	/**
	 * Run on plugin activation.
	 *
	 * Sets default plugin options and checks server requirements.
	 *
	 * @since 1.0.0
	 */
	public static function activate()
	{
		// Check PHP version
		if (version_compare(PHP_VERSION, '7.4', '<')) {
			wp_die(
				esc_html__('Img Panda requires PHP 7.4 or higher. Please upgrade PHP.', 'img-panda'),
				esc_html__('Plugin Activation Error', 'img-panda'),
				array('back_link' => true)
			);
		}

		// Check WordPress version
		global $wp_version;
		if (version_compare($wp_version, '5.8', '<')) {
			wp_die(
				esc_html__('Img Panda requires WordPress 5.8 or higher. Please upgrade WordPress.', 'img-panda'),
				esc_html__('Plugin Activation Error', 'img-panda'),
				array('back_link' => true)
			);
		}

		// Run migration from old option names (v1.0.x to v1.1.0)
		self::migrate_options();

		// Set default options if they don't exist
		$default_options = array(
			'auto_convert' => '1',
			'quality' => '60',
			'replace_original' => 'keep_both',
			'show_webp_in_library' => '1',
			'enable_frontend_serving' => '1',
		);

		if (!get_option('img_panda_settings')) {
			add_option('img_panda_settings', $default_options);
		}

		// Set activation time
		add_option('img_panda_activated', time());
	}

	/**
	 * Migrate options from old prefix to new prefix.
	 *
	 * @since 1.1.0
	 */
	private static function migrate_options()
	{
		// Migrate main settings
		$old_settings = get_option('wp_webp_optimizer_settings');
		if ($old_settings && !get_option('img_panda_settings')) {
			update_option('img_panda_settings', $old_settings);
			delete_option('wp_webp_optimizer_settings');
		}

		// Migrate stats
		$old_stats = get_option('wp_webp_stats');
		if ($old_stats && !get_option('img_panda_stats')) {
			update_option('img_panda_stats', $old_stats);
			delete_option('wp_webp_stats');
		}

		// Migrate conversion progress
		$old_progress = get_option('wp_webp_conversion_progress');
		if ($old_progress) {
			update_option('img_panda_conversion_progress', $old_progress);
			delete_option('wp_webp_conversion_progress');
		}

		// Migrate conversion status
		$old_status = get_option('wp_webp_conversion_status');
		if ($old_status) {
			update_option('img_panda_conversion_status', $old_status);
			delete_option('wp_webp_conversion_status');
		}

		// Migrate conversion queue
		$old_queue = get_option('wp_webp_conversion_queue');
		if ($old_queue) {
			update_option('img_panda_conversion_queue', $old_queue);
			delete_option('wp_webp_conversion_queue');
		}

		// Migrate conversion logs
		$old_logs = get_option('wp_webp_conversion_logs');
		if ($old_logs) {
			update_option('img_panda_conversion_logs', $old_logs);
			delete_option('wp_webp_conversion_logs');
		}

		// Migrate post meta keys (batch update for performance)
		global $wpdb;
		$meta_mappings = array(
			'_webp_converted' => '_img_panda_converted',
			'_webp_path' => '_img_panda_path',
			'_webp_backup_path' => '_img_panda_backup_path',
			'_webp_original_size' => '_img_panda_original_size',
			'_webp_new_size' => '_img_panda_new_size',
			'_webp_conversion_date' => '_img_panda_conversion_date',
			'_webp_original_id' => '_img_panda_original_id',
		);

		foreach ($meta_mappings as $old_key => $new_key) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration script
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
					$new_key,
					$old_key
				)
			);
		}

		// Clear old cron hooks
		wp_clear_scheduled_hook('wp_webp_bulk_conversion_cron');
		wp_clear_scheduled_hook('webp_conversion_cron');
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * Clean up temporary data if needed.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate()
	{
		// Clean up any temporary data or scheduled events here
		// Note: We don't delete settings on deactivation, only on uninstall
		delete_option('img_panda_activated');
	}
}

